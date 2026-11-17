<?php
require_once __DIR__ . '/includes/bootstrap.php';

$idProducto = filter_var($_GET['id_producto'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
$producto = null;
$noEncontrado = false;
$flashError = $_SESSION['flash_error'] ?? '';
$flashSuccess = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

if ($idProducto > 0) {
    $stmt = $pdo->prepare("
        SELECT p.id_producto, p.id_categoria, p.nombre, p.descripcion, p.precio,
               p.cantidad_disponible, p.condicion, p.ciudad,
               c.nombre AS categoria, t.nombre_tienda,
               (SELECT pi.ruta_imagen
                  FROM producto_imagenes pi
                 WHERE pi.id_producto = p.id_producto
                 ORDER BY pi.es_principal DESC, pi.orden ASC
                 LIMIT 1) AS imagen
          FROM productos p
          INNER JOIN categorias c ON c.id_categoria = p.id_categoria
          LEFT JOIN tiendas t ON t.id_vendedor = p.id_vendedor
         WHERE p.id_producto = ?
           AND p.estado = 'publicado'
           AND p.cantidad_disponible > 0
         LIMIT 1
    ");
    $stmt->execute([$idProducto]);
    $producto = $stmt->fetch();
    if (!$producto) {
        http_response_code(404);
        $noEncontrado = true;
    }
} else {
    // Compatibilidad con las cuatro tarjetas de demostración del catálogo.
    $nombre = trim((string)($_GET['nombre'] ?? ''));
    $precio = trim((string)($_GET['precio'] ?? ''));
    $categoria = trim((string)($_GET['categoria'] ?? ''));
    $imagen = trim((string)($_GET['img'] ?? 'img/logo.png'));
    $descripcion = trim((string)($_GET['desc'] ?? ''));
    if ($nombre !== '' && $precio !== '') {
        $producto = [
            'id_producto' => null,
            'id_categoria' => null,
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'precio' => preg_replace('/[^0-9]/', '', $precio),
            'categoria' => $categoria,
            'imagen' => $imagen,
            'cantidad_disponible' => 99,
            'condicion' => 'nuevo',
            'ciudad' => '',
            'nombre_tienda' => 'CompraVenta OMT'
        ];
    } else {
        http_response_code(404);
        $noEncontrado = true;
    }
}

$usuarioSesion = $_SESSION['usuario'] ?? null;
$nombreProducto = $producto['nombre'] ?? 'Producto no disponible';
$imagenProducto = $producto['imagen'] ?: 'img/logo.png';
$precioProducto = (float)($producto['precio'] ?? 0);
$stockProducto = (int)($producto['cantidad_disponible'] ?? 0);
$resenas = [];
$promedioResenas = 0;
$totalResenas = 0;
$recomendaciones = [];
$resenasDisponibles = false;

if (!$noEncontrado && $producto) {
    // La tabla se crea con database/migracion_resenas.sql. Si aún no se ha aplicado,
    // el detalle y el carrito siguen funcionando normalmente.
    try {
        $resenasStmt = $pdo->prepare("
            SELECT r.calificacion, r.comentario, r.creado_en,
                   CONCAT(u.nombres, ' ', COALESCE(u.apellidos, '')) AS comprador
              FROM resenas_productos r
              INNER JOIN usuarios u ON u.id_usuario = r.id_usuario
             WHERE r.id_producto = ?
             ORDER BY r.creado_en DESC
             LIMIT 8
        ");
        $resenasStmt->execute([(int)$producto['id_producto']]);
        $resenas = $resenasStmt->fetchAll();
        $resumenStmt = $pdo->prepare("SELECT COUNT(*) AS total, COALESCE(AVG(calificacion), 0) AS promedio FROM resenas_productos WHERE id_producto = ?");
        $resumenStmt->execute([(int)$producto['id_producto']]);
        $resumen = $resumenStmt->fetch();
        $totalResenas = (int)($resumen['total'] ?? 0);
        $promedioResenas = (float)($resumen['promedio'] ?? 0);
        $resenasDisponibles = true;
    } catch (PDOException $e) {
        error_log('Tabla de reseñas no disponible: ' . $e->getMessage());
    }

    try {
        if (!empty($producto['id_categoria'])) {
            $recomendacionesStmt = $pdo->prepare("
                SELECT p.id_producto, p.nombre, p.precio, p.cantidad_disponible,
                       (SELECT pi.ruta_imagen FROM producto_imagenes pi
                         WHERE pi.id_producto = p.id_producto
                         ORDER BY pi.es_principal DESC, pi.orden ASC LIMIT 1) AS imagen
                  FROM productos p
                 WHERE p.estado = 'publicado'
                   AND p.cantidad_disponible > 0
                   AND p.id_categoria = ?
                   AND p.id_producto <> ?
                 ORDER BY p.creado_en DESC
                 LIMIT 4
            ");
            $recomendacionesStmt->execute([(int)$producto['id_categoria'], (int)$producto['id_producto']]);
        } else {
            $recomendacionesStmt = $pdo->prepare("
                SELECT p.id_producto, p.nombre, p.precio, p.cantidad_disponible,
                       (SELECT pi.ruta_imagen FROM producto_imagenes pi
                         WHERE pi.id_producto = p.id_producto
                         ORDER BY pi.es_principal DESC, pi.orden ASC LIMIT 1) AS imagen
                  FROM productos p
                 INNER JOIN categorias c ON c.id_categoria = p.id_categoria
                 WHERE p.estado = 'publicado' AND p.cantidad_disponible > 0
                   AND c.nombre = ? 
                 ORDER BY p.creado_en DESC LIMIT 4
            ");
            $recomendacionesStmt->execute([(string)$producto['categoria']]);
        }
        $recomendaciones = $recomendacionesStmt->fetchAll();
    } catch (PDOException $e) {
        error_log('No fue posible cargar recomendaciones: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($nombreProducto) ?> | CompraVenta OMT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="img/logo.png" alt="Logo original CompraVenta OMT" class="logo-navbar">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu" aria-label="Abrir menú">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0 py-2">
                <li class="nav-item"><a class="nav-link" href="index.php">Inicio</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php#productos">Productos</a></li>
                <li class="nav-item"><a class="nav-link" href="index.php#descuentos">Descuentos</a></li>
            </ul>
            <form class="search-box d-flex me-3" onsubmit="return false;">
                <input id="buscador" class="form-control" type="search" placeholder="Buscar productos...">
                <button class="btn btn-warning" type="button" aria-label="Buscar"><i class="bi bi-search"></i></button>
            </form>
            <a href="carrito.html" class="cart-icon me-3 position-relative" title="Ver tu carrito">
                <i class="bi bi-cart3"></i>
                <span id="cart-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:11px;">0</span>
            </a>
            <?php if ($usuarioSesion): ?>
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <span class="fw-semibold text-dark">¡Bienvenido, <?= e(explode(' ', trim($usuarioSesion['nombre']))[0]) ?>!</span>
                    <?php if (strtolower(trim((string)($usuarioSesion['rol'] ?? ''))) === 'vendedor'): ?>
                        <a href="vendedor/index.php" class="btn btn-outline-dark fw-semibold">Panel vendedor</a>
                    <?php elseif (strtolower(trim((string)($usuarioSesion['rol'] ?? ''))) === 'administrador'): ?>
                        <a href="admin/usuarios.php" class="btn btn-outline-dark fw-semibold">Panel administrador</a>
                    <?php endif; ?>
                    <a href="auth/logout.php" class="btn btn-warning fw-semibold">Cerrar sesión</a>
                </div>
            <?php else: ?>
                <div class="d-flex gap-2">
                    <a href="login.php" class="btn btn-warning fw-semibold">Iniciar sesión</a>
                    <a href="elegir_registro.php" class="btn btn-warning fw-semibold">Registrarse</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<section class="product-detail-section py-5" style="margin-top:80px;">
    <div class="container py-4">
        <?php if ($flashError): ?><div class="alert alert-danger"><?= e($flashError) ?></div><?php endif; ?>
        <?php if ($flashSuccess): ?><div class="alert alert-success"><?= e($flashSuccess) ?></div><?php endif; ?>
        <?php if ($noEncontrado): ?>
            <div class="bg-white rounded-4 shadow-sm text-center p-5">
                <i class="bi bi-box-seam display-3 text-secondary"></i>
                <h1 class="h3 fw-bold mt-3">Este producto no está disponible</h1>
                <p class="text-secondary">Puede que haya sido retirado, pausado o que ya no tenga unidades.</p>
                <a href="index.php#productos" class="btn btn-warning">Volver a los productos</a>
            </div>
        <?php else: ?>
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
                    <li class="breadcrumb-item"><a href="index.php#productos">Productos</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= e($producto['categoria']) ?></li>
                </ol>
            </nav>
            <div class="row g-5 bg-white p-4 rounded-4 shadow-sm align-items-center">
                <div class="col-lg-6">
                    <div class="main-product-image-container position-relative bg-light rounded-4 p-4 text-center">
                        <span class="tag new position-absolute top-0 start-0 m-3"><?= e(ucfirst($producto['condicion'] ?? 'Producto')) ?></span>
                        <img id="det-imagen" src="<?= e($imagenProducto) ?>" alt="<?= e($nombreProducto) ?>" class="img-fluid" style="max-height:420px;object-fit:contain;">
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="product-detail-info">
                        <span class="text-muted fw-semibold text-uppercase"><?= e($producto['categoria']) ?></span>
                        <h1 class="fw-bold display-6 mt-1 mb-2" id="det-nombre"><?= e($nombreProducto) ?></h1>
                        <?php if ($totalResenas > 0): ?>
                            <a href="#resenas" class="d-inline-flex align-items-center gap-2 mb-3 text-decoration-none">
                                <span class="text-warning"><?= str_repeat('★', (int)round($promedioResenas)) ?></span>
                                <strong><?= number_format($promedioResenas, 1, ',', '.') ?>/5</strong>
                                <span class="text-muted small">(<?= $totalResenas ?> reseñas)</span>
                            </a>
                        <?php else: ?>
                            <div class="text-muted small mb-3">Aún no hay reseñas para este producto.</div>
                        <?php endif; ?>
                        <div class="price display-6 fw-bold text-dark mb-3" id="det-precio">$<?= number_format($precioProducto, 0, ',', '.') ?></div>
                        <p class="text-secondary mb-4" id="det-descripcion"><?= nl2br(e($producto['descripcion'] ?? '')) ?></p>
                        <div class="small text-muted mb-2"><i class="bi bi-shop me-2 text-warning"></i>Vendido por: <strong><?= e($producto['nombre_tienda'] ?: 'Vendedor') ?></strong></div>
                        <?php if (!empty($producto['ciudad'])): ?>
                            <div class="small text-muted mb-2"><i class="bi bi-geo-alt me-2 text-warning"></i><?= e($producto['ciudad']) ?></div>
                        <?php endif; ?>
                        <div class="small text-muted mb-3"><i class="bi bi-box-seam me-2 text-warning"></i><span id="det-stock"><?= $stockProducto ?></span> unidades disponibles · <?= e(ucfirst($producto['condicion'] ?? '')) ?></div>
                        <hr class="my-4 text-secondary">
                        <div class="d-flex align-items-center mb-4">
                            <label class="fw-bold me-3" for="input-cantidad">Cantidad:</label>
                            <div class="input-group" style="width:145px;">
                                <button class="btn btn-outline-secondary" type="button" id="btn-menos" aria-label="Restar cantidad">−</button>
                                <input type="number" min="1" max="<?= max(1, $stockProducto) ?>" class="form-control text-center" id="input-cantidad" value="1" readonly>
                                <button class="btn btn-outline-secondary" type="button" id="btn-mas" aria-label="Aumentar cantidad">+</button>
                            </div>
                        </div>
                        <div class="d-grid gap-3 d-sm-flex">
                            <button class="btn btn-warning btn-lg fw-bold flex-fill" id="btn-add-cart"
                                data-stock="<?= $stockProducto ?>"
                                data-product-id="<?= (int)($producto['id_producto'] ?? 0) ?>"
                                data-product-name="<?= e($nombreProducto) ?>"
                                data-product-price="<?= e((string)$precioProducto) ?>"
                                data-product-image="<?= e($imagenProducto) ?>">
                                <i class="bi bi-cart-plus me-2"></i>Añadir al carrito
                            </button>
                            <a href="carrito.html" class="btn btn-dark btn-lg fw-bold flex-fill text-center">Ver carrito</a>
                        </div>
                        <div class="mt-4 pt-3 border-top">
                            <div class="d-flex align-items-center mb-2 text-muted small"><i class="bi bi-truck fs-5 text-warning me-2"></i>Envíos a todo el país con seguimiento.</div>
                            <div class="d-flex align-items-center text-muted small"><i class="bi bi-shield-check fs-5 text-warning me-2"></i>Compra protegida por COMPRAVENTA.</div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($recomendaciones): ?>
            <section class="mt-5" aria-labelledby="recomendaciones-titulo">
                <div class="section-header mb-4">
                    <div><h2 id="recomendaciones-titulo" class="h3 fw-bold">También te puede interesar</h2><p class="mb-0">Más opciones de esta categoría.</p></div>
                </div>
                <div class="row g-4">
                    <?php foreach ($recomendaciones as $recomendado):
                        $imagenRecomendada = $recomendado['imagen'] ?: 'img/logo.png';
                        $urlRecomendado = 'producto.php?' . http_build_query(['id_producto' => (int)$recomendado['id_producto']], '', '&', PHP_QUERY_RFC3986);
                    ?>
                    <div class="col-lg-3 col-md-6">
                        <article class="product-card h-100">
                            <a class="product-image" href="<?= e($urlRecomendado) ?>"><img src="<?= e($imagenRecomendada) ?>" alt="<?= e($recomendado['nombre']) ?>" loading="lazy"></a>
                            <div class="product-info">
                                <h3 class="h6 fw-bold"><?= e($recomendado['nombre']) ?></h3>
                                <div class="price">$<?= number_format((float)$recomendado['precio'], 0, ',', '.') ?></div>
                                <button type="button" class="btn btn-warning w-100 btn-add-producto"
                                    data-product-id="<?= (int)$recomendado['id_producto'] ?>"
                                    data-product-name="<?= e($recomendado['nombre']) ?>"
                                    data-product-price="<?= e((string)$recomendado['precio']) ?>"
                                    data-product-image="<?= e($imagenRecomendada) ?>">Añadir al carrito</button>
                            </div>
                        </article>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <section class="mt-5" id="resenas" aria-labelledby="resenas-titulo">
                <div class="section-header mb-4">
                    <div><h2 id="resenas-titulo" class="h3 fw-bold">Opiniones y reseñas</h2><p class="mb-0">Conoce lo que opinan otros compradores.</p></div>
                    <?php if ($totalResenas > 0): ?><div class="text-end"><div class="h3 fw-bold mb-0"><?= number_format($promedioResenas, 1, ',', '.') ?>/5</div><div class="text-warning"><?= str_repeat('★', (int)round($promedioResenas)) ?></div><small class="text-muted"><?= $totalResenas ?> opiniones</small></div><?php endif; ?>
                </div>
                <?php if (!$resenasDisponibles): ?>
                    <div class="alert alert-info">La sección de reseñas está lista. Para activarla en tu base de datos, ejecuta una sola vez el archivo <code>database/migracion_resenas.sql</code>.</div>
                <?php elseif (!$resenas): ?>
                    <div class="bg-white rounded-4 p-4 shadow-sm text-muted mb-4">Este producto todavía no tiene reseñas. ¡Puedes ser el primero en opinar!</div>
                <?php else: ?>
                    <div class="row g-3 mb-4">
                        <?php foreach ($resenas as $resena): ?>
                        <div class="col-md-6">
                            <article class="review-card bg-white rounded-4 p-4 h-100 shadow-sm">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong><?= e(trim($resena['comprador'])) ?></strong>
                                    <span class="text-warning"><?= str_repeat('★', (int)$resena['calificacion']) ?><?= str_repeat('☆', 5 - (int)$resena['calificacion']) ?></span>
                                </div>
                                <p class="mb-2"><?= nl2br(e($resena['comentario'])) ?></p>
                                <small class="text-muted"><?= e(date('d/m/Y', strtotime($resena['creado_en']))) ?></small>
                            </article>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($resenasDisponibles && $idProducto > 0): ?>
                    <div class="bg-white rounded-4 shadow-sm p-4">
                        <?php if ($usuarioSesion && strtolower((string)($usuarioSesion['rol'] ?? '')) === 'comprador'): ?>
                            <h3 class="h5 fw-bold mb-3">Comparte tu opinión</h3>
                            <form action="resena.php" method="post" class="row g-3">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="id_producto" value="<?= (int)$idProducto ?>">
                                <div class="col-md-4">
                                    <label for="calificacion" class="form-label fw-semibold">Tu calificación</label>
                                    <select class="form-select" name="calificacion" id="calificacion" required>
                                        <option value="">Selecciona estrellas</option>
                                        <option value="5">★★★★★ - Excelente</option>
                                        <option value="4">★★★★ - Muy bueno</option>
                                        <option value="3">★★★ - Bueno</option>
                                        <option value="2">★★ - Regular</option>
                                        <option value="1">★ - Malo</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="comentario" class="form-label fw-semibold">Tu reseña</label>
                                    <textarea class="form-control" name="comentario" id="comentario" rows="3" maxlength="1000" placeholder="Cuéntales a otros compradores tu experiencia..." required></textarea>
                                </div>
                                <div class="col-12"><button type="submit" class="btn btn-warning fw-bold">Publicar reseña</button><small class="text-muted ms-2">Puedes actualizar tu reseña más adelante.</small></div>
                            </form>
                        <?php elseif (!$usuarioSesion): ?>
                            <p class="mb-0">¿Quieres dejar tu opinión? <a href="login.php" class="fw-semibold">Inicia sesión como comprador</a>.</p>
                        <?php else: ?>
                            <p class="mb-0 text-muted">Las reseñas están disponibles para cuentas de comprador.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>
</section>
<footer class="footer"><div class="container"><div class="footer-bottom"><p>© 2026 COMPRAVENTA. Todos los derechos reservados por OMT.</p></div></div></footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/carrito.js"></script>
</body>
</html>
