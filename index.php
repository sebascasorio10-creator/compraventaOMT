<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (strtolower(trim((string)($_SESSION['usuario']['rol'] ?? ''))) === 'vendedor') {
    header('Location: vendedor/index.php');
    exit;
}
$usuarioSesion = $_SESSION['usuario'] ?? null;

// Productos publicados por vendedores: se consultan desde MySQL y no desde tarjetas de demostración.
$productosPublicadosStmt = $pdo->query("
    SELECT p.id_producto, p.nombre, p.descripcion, p.precio, p.cantidad_disponible,
           c.nombre AS categoria,
           t.nombre_tienda,
           (SELECT pi.ruta_imagen
              FROM producto_imagenes pi
             WHERE pi.id_producto = p.id_producto
             ORDER BY pi.es_principal DESC, pi.orden ASC
             LIMIT 1) AS imagen
      FROM productos p
      INNER JOIN categorias c ON c.id_categoria = p.id_categoria
      LEFT JOIN tiendas t ON t.id_vendedor = p.id_vendedor
     WHERE p.estado = 'publicado'
       AND p.cantidad_disponible > 0
     ORDER BY p.creado_en DESC
     LIMIT 8
");
$productosPublicados = $productosPublicadosStmt->fetchAll();

$productosDemostracion = [
    ['nombre' => 'Smartphone Premium', 'categoria' => 'Tecnología', 'precio' => 899000, 'imagen' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=900&q=85', 'etiqueta' => 'Nuevo', 'descripcion' => 'Celular de diseño moderno, pantalla de alta definición, cámara de gran calidad y batería de larga duración.'],
    ['nombre' => 'Audífonos Bluetooth', 'categoria' => 'Electrónica', 'precio' => 189000, 'precio_anterior' => 229000, 'imagen' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=900&q=85', 'etiqueta' => 'Oferta', 'descripcion' => 'Audífonos inalámbricos cómodos, con sonido envolvente y estuche de protección.'],
    ['nombre' => 'Reloj inteligente', 'categoria' => 'Accesorios', 'precio' => 249000, 'precio_anterior' => 279000, 'imagen' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=900&q=85', 'etiqueta' => 'Popular', 'descripcion' => 'Reloj inteligente con pantalla táctil y funciones para organizar tu día.'],
    ['nombre' => 'Portátil para trabajo', 'categoria' => 'Computadores', 'precio' => 2499000, 'imagen' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=900&q=85', 'etiqueta' => 'Nuevo', 'descripcion' => 'Computador portátil ideal para estudiar, trabajar y disfrutar contenido multimedia.'],
    ['nombre' => 'Cámara digital', 'categoria' => 'Fotografía', 'precio' => 1799000, 'imagen' => 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&w=900&q=85', 'etiqueta' => '', 'descripcion' => 'Cámara digital compacta para capturar tus mejores momentos con gran detalle.'],
    ['nombre' => 'Parlante portátil', 'categoria' => 'Electrónica', 'precio' => 159000, 'precio_anterior' => 189000, 'imagen' => 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?auto=format&fit=crop&w=900&q=85', 'etiqueta' => 'Oferta', 'descripcion' => 'Parlante portátil con sonido potente y diseño fácil de llevar.'],
    ['nombre' => 'Silla moderna', 'categoria' => 'Hogar y muebles', 'precio' => 389000, 'imagen' => 'https://images.unsplash.com/photo-1503602642458-232111445657?auto=format&fit=crop&w=900&q=85', 'etiqueta' => '', 'descripcion' => 'Silla de estilo moderno para complementar tu espacio de trabajo o estudio.'],
    ['nombre' => 'Lámpara decorativa', 'categoria' => 'Hogar y muebles', 'precio' => 119000, 'imagen' => 'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?auto=format&fit=crop&w=900&q=85', 'etiqueta' => '', 'descripcion' => 'Lámpara decorativa que aporta iluminación cálida y estilo a tus espacios.'],
    ['nombre' => 'Tenis deportivos', 'categoria' => 'Moda', 'precio' => 279000, 'imagen' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=900&q=85', 'etiqueta' => 'Popular', 'descripcion' => 'Tenis deportivos cómodos para tus actividades diarias y caminatas.'],
    ['nombre' => 'Mochila urbana', 'categoria' => 'Accesorios', 'precio' => 139000, 'imagen' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=900&q=85', 'etiqueta' => '', 'descripcion' => 'Mochila práctica con espacio para tus objetos personales y accesorios.'],
    ['nombre' => 'Tableta digital', 'categoria' => 'Tecnología', 'precio' => 1099000, 'imagen' => 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?auto=format&fit=crop&w=900&q=85', 'etiqueta' => 'Nuevo', 'descripcion' => 'Tableta ligera para estudiar, leer, dibujar y disfrutar entretenimiento.'],
    ['nombre' => 'Teclado mecánico', 'categoria' => 'Computadores', 'precio' => 219000, 'imagen' => 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?auto=format&fit=crop&w=900&q=85', 'etiqueta' => '', 'descripcion' => 'Teclado mecánico para mejorar tu experiencia de escritura y juego.'],
];

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>COMPRAVENTA | Marketplace</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- CSS propio -->
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <!-- ================= NAVBAR ================= -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
        <div class="container">

            <!-- LOGO A LA IZQUIERDA -->
            <a class="navbar-brand" href="index.php">
                <img src="img/logo.png" alt="Logo original CompraVenta OMT" class="logo-navbar">
            </a>

            <!-- BOTÓN MOBILE -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="menu">

                <!-- MENÚ PRINCIPAL -->
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0 py-2">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#productos">Productos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#descuentos">Descuentos</a>
                    </li>
                </ul>

                <!-- BUSCADOR -->
                <form class="search-box d-flex me-3" onsubmit="return false;">
                    <input id="buscador" class="form-control" type="search" placeholder="Buscar productos...">
                    <button class="btn btn-warning" type="button">
                        <i class="bi bi-search"></i>
                    </button>
                </form>

                <!-- CARRITO -->
                <!-- CARRITO CORREGIDO -->
                <a href="carrito.html" class="cart-icon me-3 position-relative" title="Ver carrito">
                    <i class="bi bi-cart3"></i>
                    <span id="cart-count"
                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                        style="font-size: 10px;">
                        0
                    </span>
                </a>

                <!-- BOTONES DE AUTENTICACIÓN -->
                <?php if ($usuarioSesion): ?>
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <span class="fw-semibold text-dark">
                            ¡Bienvenido, <?= e(explode(' ', trim($usuarioSesion['nombre']))[0]) ?>!
                        </span>
                        <?php if (strtolower(trim((string)($usuarioSesion['rol'] ?? ''))) === 'vendedor'): ?>
                            <a href="vendedor/index.php" class="btn btn-outline-dark fw-semibold">Panel vendedor</a>
                        <?php elseif (strtolower(trim((string)($usuarioSesion['rol'] ?? ''))) === 'administrador'): ?>
                            <a href="admin/index.php" class="btn btn-outline-dark fw-semibold">
                                Panel administrador
                            </a>
                        <?php endif; ?>
                        <a href="auth/logout.php" class="btn btn-warning fw-semibold">
                            Cerrar sesión
                        </a>
                    </div>
                <?php else: ?>
                    <div class="d-flex gap-2">
                        <a href="login.php" class="btn btn-warning fw-semibold">
                            Iniciar sesión
                        </a>
                        <a href="elegir_registro.php" class="btn btn-warning fw-semibold">
                            Registrarse
                        </a>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </nav>


    <!-- ================= HERO / CARRUSEL ================= -->
    <section class="hero-section">

        <div class="hero-overlay"></div>

        <div class="container hero-content">
            <div class="hero-card">
                <span class="badge bg-warning text-dark mb-3">
                    Bodega Virtual & Envíos Seguros
                </span>

                <h1>
                    Compra y vende <br> productos increíbles
                </h1>

                <p>
                    Encuentra ofertas únicas con la mejor garantía de almacenamiento y logística en Colombia.
                </p>

                <a href="#productos" class="btn btn-warning btn-lg fw-bold">
                    Explorar Bodega <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>

        <!-- FLECHAS SLIDER -->
        <button class="hero-control left" type="button" aria-label="Anterior">
            <i class="bi bi-chevron-left"></i>
        </button>

        <button class="hero-control right" type="button" aria-label="Siguiente">
            <i class="bi bi-chevron-right"></i>
        </button>

        <!-- PUNTOS SLIDER -->
        <div class="slider-dots">
            <span class="dot active"></span>
            <span class="dot"></span>
            <span class="dot"></span>
        </div>

    </section>


    <!-- ================= ALERTA UBICACIÓN / CONFIANZA ================= -->
    <section class="location-alert">
        <div class="container">
            <div class="alert-box">
                <div class="alert-icon">
                    <i class="bi bi-shield-check"></i>
                </div>

                <div>
                    <h6>Compra con total tranquilidad</h6>
                    <p>Todos los envíos y transacciones están garantizados. Miles de productos disponibles en Colombia.
                    </p>
                </div>

                <span class="status">
                    <i class="bi bi-circle-fill"></i> Actualizado ahora
                </span>
            </div>
        </div>
    </section>


    <!-- ================= PRODUCTOS POPULARES ================= -->
    <section class="products-section" id="productos">
        <div class="container">

            <div class="section-header">
                <div>
                    <h2>Productos populares</h2>
                    <p>Los artículos más buscados por nuestros compradores.</p>
                </div>

                <button class="btn btn-outline-dark">
                    Ver todos <i class="bi bi-arrow-right"></i>
                </button>
            </div>

            <!-- ALERTA SI NO HAY RESULTADOS DE BÚSQUEDA -->
            <div id="sinResultados" class="text-center my-4" style="display:none;">
                <h4>No se encontraron productos</h4>
                <p>Intenta buscar otra palabra.</p>
            </div>

            <!-- CATÁLOGO DE DEMOSTRACIÓN: no se guarda en MySQL -->
            <div class="row g-4">
                <?php foreach ($productosDemostracion as $productoDemo):
                    $parametrosDetalle = [
                        'nombre' => $productoDemo['nombre'],
                        'precio' => (string)$productoDemo['precio'],
                        'categoria' => $productoDemo['categoria'],
                        'img' => $productoDemo['imagen'],
                        'desc' => $productoDemo['descripcion']
                    ];
                    $enlaceDetalle = 'producto.php?' . http_build_query($parametrosDetalle, '', '&', PHP_QUERY_RFC3986);
                    $busqueda = $productoDemo['nombre'] . ' ' . $productoDemo['categoria'];
                ?>
                <div class="col-lg-3 col-md-6">
                    <article class="product-card producto h-100" data-nombre="<?= e(strtolower($busqueda)) ?>">
                        <div class="product-image">
                            <?php if ($productoDemo['etiqueta'] !== ''): ?>
                                <span class="tag <?= $productoDemo['etiqueta'] === 'Oferta' ? 'sale' : ($productoDemo['etiqueta'] === 'Popular' ? 'best' : 'new') ?>"><?= e($productoDemo['etiqueta']) ?></span>
                            <?php endif; ?>
                            <img src="<?= e($productoDemo['imagen']) ?>" alt="<?= e($productoDemo['nombre']) ?>" loading="lazy" onerror="this.onerror=null;this.src='img/logo.png';">
                            <button class="favorite" type="button" aria-label="Guardar en favoritos"><i class="bi bi-heart"></i></button>
                        </div>
                        <div class="product-info">
                            <h5><?= e($productoDemo['nombre']) ?></h5>
                            <p class="category"><?= e($productoDemo['categoria']) ?></p>
                            <div class="price">$<?= number_format((int)$productoDemo['precio'], 0, ',', '.') ?></div>
                            <a href="<?= e($enlaceDetalle) ?>" class="btn btn-warning w-100 mb-2">Ver producto</a>
                            <button type="button" class="btn btn-outline-dark w-100 btn-add-producto"
                                data-product-id=""
                                data-product-name="<?= e($productoDemo['nombre']) ?>"
                                data-product-price="<?= (int)$productoDemo['precio'] ?>"
                                data-product-image="<?= e($productoDemo['imagen']) ?>">
                                <i class="bi bi-cart-plus me-1"></i>Añadir al carrito
                            </button>
                        </div>
                    </article>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>



    <!-- ================= DESCUENTOS ================= -->
    <section class="discounts-section" id="descuentos">
        <div class="container">
            <div class="discounts-heading">
                <div>
                    <span class="eyebrow"><i class="bi bi-stars"></i> Oportunidades especiales</span>
                    <h2>Descuentos de la semana</h2>
                    <p>Encuentra productos seleccionados a un precio especial por tiempo limitado.</p>
                </div>
                <span class="discounts-mark"><i class="bi bi-tag-fill"></i> Ofertas destacadas</span>
            </div>
            <div class="row g-4">
                <?php foreach ($productosDemostracion as $productoOferta):
                    $precioAnterior = (int)($productoOferta['precio_anterior'] ?? 0);
                    $precioActual = (int)$productoOferta['precio'];
                    if ($precioAnterior <= $precioActual) continue;
                    $porcentajeDescuento = (int)round((1 - ($precioActual / $precioAnterior)) * 100);
                    $parametrosOferta = [
                        'nombre' => $productoOferta['nombre'],
                        'precio' => (string)$precioActual,
                        'categoria' => $productoOferta['categoria'],
                        'img' => $productoOferta['imagen'],
                        'desc' => $productoOferta['descripcion']
                    ];
                    $enlaceOferta = 'producto.php?' . http_build_query($parametrosOferta, '', '&', PHP_QUERY_RFC3986);
                ?>
                <div class="col-lg-4 col-md-6">
                    <article class="offer-card h-100">
                        <div class="offer-image">
                            <span class="tag sale">-<?= $porcentajeDescuento ?>%</span>
                            <img src="<?= e($productoOferta['imagen']) ?>" alt="<?= e($productoOferta['nombre']) ?>" loading="lazy" onerror="this.onerror=null;this.src='img/logo.png';">
                        </div>
                        <div class="offer-info">
                            <span class="offer-kicker">Precio especial</span>
                            <h3><?= e($productoOferta['nombre']) ?></h3>
                            <p><?= e($productoOferta['categoria']) ?></p>
                            <div class="offer-prices">
                                <span class="old-price">$<?= number_format($precioAnterior, 0, ',', '.') ?></span>
                                <strong>$<?= number_format($precioActual, 0, ',', '.') ?></strong>
                                <span class="save-label">Ahorras $<?= number_format($precioAnterior - $precioActual, 0, ',', '.') ?></span>
                            </div>
                            <div class="offer-actions">
                                <a href="<?= e($enlaceOferta) ?>" class="btn btn-warning">Ver oferta <i class="bi bi-arrow-right"></i></a>
                                <button type="button" class="btn btn-outline-dark btn-add-producto"
                                    data-product-id=""
                                    data-product-name="<?= e($productoOferta['nombre']) ?>"
                                    data-product-price="<?= $precioActual ?>"
                                    data-product-image="<?= e($productoOferta['imagen']) ?>">
                                    <i class="bi bi-cart-plus"></i>
                                </button>
                            </div>
                        </div>
                    </article>
                </div>
                <?php endforeach; ?>
            </div>
            <p class="discounts-note"><i class="bi bi-info-circle"></i> Estas ofertas son parte del catálogo de demostración.</p>
        </div>
    </section>

    <!-- ============ PUBLICACIONES RECIENTES DE VENDEDORES ============ -->
    <?php if ($productosPublicados): ?>
    <section class="products-section bg-light" id="publicaciones-vendedores">
        <div class="container">
            <div class="section-header">
                <div>
                    <h2>Productos publicados recientemente</h2>
                    <p>Artículos reales publicados por nuestros vendedores.</p>
                </div>
            </div>
            <div class="row g-4">
                <?php foreach ($productosPublicados as $productoPublicado):
                    $imagenPublicada = $productoPublicado['imagen'] ?: 'img/logo.png';
                    $urlDetalle = 'producto.php?' . http_build_query(['id_producto' => (int)$productoPublicado['id_producto']], '', '&', PHP_QUERY_RFC3986);
                ?>
                <div class="col-lg-3 col-md-6">
                    <article class="product-card h-100">
                        <div class="product-image">
                            <img src="<?= e($imagenPublicada) ?>" alt="<?= e($productoPublicado['nombre']) ?>" loading="lazy">
                        </div>
                        <div class="product-info">
                            <h5><?= e($productoPublicado['nombre']) ?></h5>
                            <p class="category"><?= e($productoPublicado['categoria']) ?></p>
                            <div class="price">$<?= number_format((float)$productoPublicado['precio'], 0, ',', '.') ?></div>
                            <small class="text-muted d-block mb-2">
                                <?= e($productoPublicado['nombre_tienda'] ?: 'Vendedor') ?>
                                · <?= (int)$productoPublicado['cantidad_disponible'] ?> disponibles
                            </small>
                            <a href="<?= e($urlDetalle) ?>" class="btn btn-warning w-100 mb-2">Ver producto</a>
                            <button type="button" class="btn btn-outline-dark w-100 btn-add-producto"
                                data-product-id="<?= (int)$productoPublicado['id_producto'] ?>"
                                data-product-name="<?= e($productoPublicado['nombre']) ?>"
                                data-product-price="<?= e((string)$productoPublicado['precio']) ?>"
                                data-product-image="<?= e($imagenPublicada) ?>"
                                data-product-stock="<?= (int)$productoPublicado['cantidad_disponible'] ?>">
                                <i class="bi bi-cart-plus me-1"></i>Añadir al carrito
                            </button>
                        </div>
                    </article>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>


    <!-- ================= SECCIÓN VENDER ================= -->
    <?php if (!$usuarioSesion): ?>
    <section class="sell-section">
        <div class="container">
            <div class="sell-box">
                <div class="sell-content">
                    <h2>¿Quieres vender tus artículos?</h2>
                    <p>Publica gratis tus productos y llega a miles de compradores interesados.</p>
                    <a href="registro_vendedor.php" class="btn btn-warning btn-lg">
                        Publicar gratis <i class="bi bi-plus-circle ms-1"></i>
                    </a>
                </div>
                <div class="sell-image">
                    <i class="bi bi-shop"></i>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>


    <!-- ================= FOOTER ================= -->
    <footer class="footer">
        <div class="container">
            <div class="row g-4">

                <!-- MARCA -->
                <div class="col-lg-4">
                    <h3 class="footer-logo">
                        COMPRA<span>VENTA</span>
                    </h3>
                    <p>
                        El marketplace donde compras y vendes productos de forma fácil, rápida y segura.
                    </p>
                    <div class="social-icons">
                        <a href="#"><i class="bi bi-facebook"></i></a>
                        <a href="#"><i class="bi bi-instagram"></i></a>
                        <a href="#"><i class="bi bi-twitter-x"></i></a>
                        <a href="#"><i class="bi bi-youtube"></i></a>
                    </div>
                </div>

                <!-- SOPORTE -->
                <div class="col-lg-2 col-md-4">
                    <h5>Soporte</h5>
                    <ul>
                        <li><a href="#">Centro de ayuda</a></li>
                        <li><a href="#">Seguridad</a></li>
                        <li><a href="#">Contacto</a></li>
                        <li><a href="#">Preguntas frecuentes</a></li>
                    </ul>
                </div>

                <!-- EMPRESA -->
                <div class="col-lg-2 col-md-4">
                    <h5>Empresa</h5>
                    <ul>
                        <li><a href="#">Nosotros</a></li>
                        <li><a href="#">Blog</a></li>
                        <?php if (!$usuarioSesion): ?><li><a href="elegir_registro.php">Crear cuenta</a></li><?php endif; ?>
                    </ul>
                </div>

                <!-- NEWSLETTER -->
                <div class="col-lg-4 col-md-4">
                    <h5>Recibe ofertas</h5>
                    <p>Suscríbete para recibir promociones y novedades.</p>
                    <div class="newsletter">
                        <input type="email" placeholder="Tu correo electrónico">
                        <button type="button"><i class="bi bi-arrow-right"></i></button>
                    </div>
                </div>

            </div>

            <hr class="mt-5 mb-4 text-secondary">

            <div class="footer-bottom">
                <p>© 2026 COMPRAVENTA. Todos los derechos reservados por OMT.</p>
                <div>
                    <a href="#" class="me-3">Términos</a>
                    <a href="#">Privacidad</a>
                </div>
            </div>
        </div>
    </footer>


    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- JS propio -->
    <script src="js/app.js"></script>
    <script src="js/carrito.js"></script>

</body>

</html>