<?php
require_once __DIR__ . '/includes/bootstrap.php';

$idProducto = filter_var($_GET['id_producto'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
$producto = null;
$noEncontrado = false;

if ($idProducto > 0) {
    $stmt = $pdo->prepare("
        SELECT p.id_producto, p.nombre, p.descripcion, p.precio,
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
            <img src="img/logo.png" alt="Logo COMPRAVENTA" class="logo-navbar">
            <span class="brand-text">COMPRA<span>VENTA</span></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu">
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
                <button class="btn btn-warning" type="button"><i class="bi bi-search"></i></button>
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
                    <li class="breadcrumb-item active" aria-current="page" id="det-categoria-top"><?= e($producto['categoria']) ?></li>
                </ol>
            </nav>
            <div class="row g-5 bg-white p-4 rounded-4 shadow-sm align-items-center">
                <div class="col-lg-6">
                    <div class="main-product-image-container position-relative bg-light rounded-4 p-4 text-center">
                        <span class="tag new position-absolute top-0 start-0 m-3" id="det-tag"><?= e(ucfirst($producto['condicion'] ?? 'Producto')) ?></span>
                        <img id="det-imagen" src="<?= e($imagenProducto) ?>" alt="<?= e($nombreProducto) ?>" class="img-fluid" style="max-height:420px;object-fit:contain;">
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="product-detail-info">
                        <span class="text-muted fw-semibold text-uppercase" id="det-categoria"><?= e($producto['categoria']) ?></span>
                        <h1 class="fw-bold display-6 mt-1 mb-2" id="det-nombre"><?= e($nombreProducto) ?></h1>
                        <div class="price display-6 fw-bold text-dark mb-3" id="det-precio">$<?= number_format($precioProducto, 0, ',', '.') ?></div>
                        <p class="text-secondary mb-4" id="det-descripcion"><?= nl2br(e($producto['descripcion'] ?? '')) ?></p>
                        <div class="small text-muted mb-2">
                            <i class="bi bi-shop me-2 text-warning"></i>Vendido por: <strong><?= e($producto['nombre_tienda'] ?: 'Vendedor') ?></strong>
                        </div>
                        <?php if (!empty($producto['ciudad'])): ?>
                            <div class="small text-muted mb-2"><i class="bi bi-geo-alt me-2 text-warning"></i><?= e($producto['ciudad']) ?></div>
                        <?php endif; ?>
                        <div class="small text-muted mb-3">
                            <i class="bi bi-box-seam me-2 text-warning"></i><span id="det-stock"><?= $stockProducto ?></span> unidades disponibles
                            · <?= e(ucfirst($producto['condicion'] ?? '')) ?>
                        </div>
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
                            <button class="btn btn-warning btn-lg fw-bold flex-fill" id="btn-add-cart" data-stock="<?= $stockProducto ?>">
                                <i class="bi bi-cart-plus me-2"></i>Añadir al carrito
                            </button>
                            <a href="<?= $usuarioSesion ? 'carrito.html' : 'login.php' ?>" class="btn btn-dark btn-lg fw-bold flex-fill text-center">Comprar ahora</a>
                        </div>
                        <div class="mt-4 pt-3 border-top">
                            <div class="d-flex align-items-center mb-2 text-muted small"><i class="bi bi-truck fs-5 text-warning me-2"></i>Envíos a todo el país con seguimiento.</div>
                            <div class="d-flex align-items-center text-muted small"><i class="bi bi-shield-check fs-5 text-warning me-2"></i>Compra protegida por COMPRAVENTA.</div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
<footer class="footer"><div class="container"><div class="footer-bottom"><p>© 2026 COMPRAVENTA. Todos los derechos reservados por OMT.</p></div></div></footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/carrito.js"></script>
</body>
</html>
