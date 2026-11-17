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
                <img src="img/logo.png" alt="Logo COMPRAVENTA" class="logo-navbar">
                <span class="brand-text">
                    COMPRA<span>VENTA</span>
                </span>
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
                            <a href="admin/usuarios.php" class="btn btn-outline-dark fw-semibold">
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

            <!-- GRID DE PRODUCTOS (8 PRODUCTOS CON ENLACES DINÁMICOS) -->
            <div class="row g-4">

                <!-- PRODUCTO 1 -->
                <div class="col-lg-3 col-md-6">
                    <div class="product-card producto" data-nombre="smartphone premium huawei celular tecnologia">
                        <div class="product-image">
                            <span class="tag new">Nuevo</span>
                            <img src="img/huaweipura.png" alt="Smartphone Premium">
                            <button class="favorite" type="button" aria-label="Guardar en favoritos">
                                <i class="bi bi-heart"></i>
                            </button>
                        </div>
                        <div class="product-info">
                            <h5>Smartphone Premium</h5>
                            <p class="category">Tecnología</p>
                            <div class="price">$899.000</div>
                            <a href="producto.html?nombre=Smartphone%20Premium&precio=$899.000&categoria=Tecnología&img=img/huaweipura.png&desc=Disfruta%20de%20la%20última%20tecnología%20móvil%20con%20pantalla%20de%20alta%20definición,%20cámara%20profesional%20de%20múltiples%20lentes%20y%20batería%20de%20larga%20duración."
                                class="btn btn-warning w-100">Ver producto</a>
                        </div>
                    </div>
                </div>

                <!-- PRODUCTO 2 -->
                <div class="col-lg-3 col-md-6">
                    <div class="product-card producto" data-nombre="audifonos inalambricos bluetooth electronica">
                        <div class="product-image">
                            <span class="tag sale">Oferta</span>
                            <img src="img/audifonos_inalambricos.png" alt="Audífonos inalámbricos">
                            <button class="favorite" type="button" aria-label="Guardar en favoritos">
                                <i class="bi bi-heart"></i>
                            </button>
                        </div>
                        <div class="product-info">
                            <h5>Audífonos inalámbricos</h5>
                            <p class="category">Electrónica</p>
                            <div class="price">$120.000</div>
                            <a href="producto.html?nombre=Audífonos%20inalámbricos&precio=$120.000&categoria=Electrónica&img=img/audifonos_inalambricos.png&desc=Cancelación%20de%20ruido%20activa,%20conexión%20Bluetooth%205.3%20de%20baja%20latencia%20y%20hasta%2024%20horas%20de%20reproducción%20continua%20con%20estuche%20de%20carga."
                                class="btn btn-warning w-100">Ver producto</a>
                        </div>
                    </div>
                </div>

                <!-- PRODUCTO 3 -->
                <div class="col-lg-3 col-md-6">
                    <div class="product-card producto" data-nombre="reloj inteligente smartwatch accesorios">
                        <div class="product-image">
                            <span class="tag best">Más vendido</span>
                            <img src="img/reloj_inteligente.png" alt="Reloj inteligente">
                            <button class="favorite" type="button" aria-label="Guardar en favoritos">
                                <i class="bi bi-heart"></i>
                            </button>
                        </div>
                        <div class="product-info">
                            <h5>Reloj inteligente</h5>
                            <p class="category">Accesorios</p>
                            <div class="price">$250.000</div>
                            <a href="producto.html?nombre=Reloj%20inteligente&precio=$250.000&categoria=Accesorios&img=img/reloj_inteligente.png&desc=Monitoreo%20de%20ritmo%20cardíaco,%20seguimiento%20del%20sueño,%20GPS%20integrado%20y%20resistencia%20al%20agua.%20Compatible%20con%20Android%20y%20iOS."
                                class="btn btn-warning w-100">Ver producto</a>
                        </div>
                    </div>
                </div>

                <!-- PRODUCTO 4 -->
                <div class="col-lg-3 col-md-6">
                    <div class="product-card producto" data-nombre="laptop portatil hp computador tecnologia">
                        <div class="product-image">
                            <span class="tag new">Nuevo</span>
                            <img src="img/portatilhp.png" alt="Laptop profesional">
                            <button class="favorite" type="button" aria-label="Guardar en favoritos">
                                <i class="bi bi-heart"></i>
                            </button>
                        </div>
                        <div class="product-info">
                            <h5>Laptop profesional</h5>
                            <p class="category">Computadores</p>
                            <div class="price">$2.500.000</div>
                            <a href="producto.html?nombre=Laptop%20profesional&precio=$2.500.000&categoria=Computadores&img=img/portatilhp.png&desc=Procesador%20de%20última%20generación,%2016GB%20RAM%20y%20SSD%20ultrarrápido.%20Ideal%20para%20trabajo%20pesado,%20diseño%20y%20multitarea."
                                class="btn btn-warning w-100">Ver producto</a>
                        </div>
                    </div>
                </div>


            </div>

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
                    $urlDetalle = 'producto.html?' . http_build_query([
                        'id_producto' => (int)$productoPublicado['id_producto'],
                        'nombre' => $productoPublicado['nombre'],
                        'precio' => number_format((float)$productoPublicado['precio'], 0, ',', '.'),
                        'categoria' => $productoPublicado['categoria'],
                        'img' => $imagenPublicada,
                        'desc' => $productoPublicado['descripcion']
                    ], '', '&', PHP_QUERY_RFC3986);
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
                            <a href="<?= e($urlDetalle) ?>" class="btn btn-warning w-100">Ver producto</a>
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