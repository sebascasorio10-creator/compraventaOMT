<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (empty($_SESSION['usuario'])) {
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header('Location: login.php');
    exit;
}

$esComprador = ($_SESSION['usuario']['rol'] ?? '') === 'comprador';
$error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);
if (!$esComprador) {
    http_response_code(403);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar pedido | CompraVenta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="bg-light">
<nav class="navbar bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">COMPRA<span class="text-warning">VENTA</span></a>
        <a href="carrito.html" class="btn btn-outline-dark btn-sm">Volver al carrito</a>
    </div>
</nav>

<main class="container py-5" style="margin-top: 55px; max-width: 850px;">
    <?php if (!$esComprador): ?>
        <div class="alert alert-warning">
            Esta etapa está habilitada para cuentas compradoras. Cierra sesión e ingresa con una cuenta de comprador para continuar.
        </div>
    <?php else: ?>
        <div class="bg-white rounded-4 shadow-sm p-4 p-md-5">
            <h1 class="h3 fw-bold mb-2">Revisar tu pedido</h1>
            <p class="text-secondary">Verifica los artículos antes de registrar la solicitud de compra.</p>

            <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
            <div id="checkout-mensaje" class="alert d-none" role="alert"></div>
            <div id="checkout-articulos" class="list-group list-group-flush mb-4"></div>

            <div class="d-flex justify-content-between fs-5 fw-bold border-top pt-3">
                <span>Total provisional</span>
                <span id="checkout-total">$0</span>
            </div>
            <p class="small text-secondary mt-2 mb-4">
                El precio y la disponibilidad definitivos se consultan nuevamente en MySQL al registrar el pedido.
                Esta versión no procesa pagos ni confirma una compra pagada.
            </p>

            <form id="checkout-form">
                <input type="hidden" id="csrf-token" value="<?= e(csrf_token()) ?>">
                <button id="checkout-confirmar" type="submit" class="btn btn-warning btn-lg w-100 fw-bold">
                    Registrar pedido pendiente
                </button>
            </form>
        </div>
    <?php endif; ?>
</main>

<script src="js/carrito.js"></script>
<?php if ($esComprador): ?>
<script>
(() => {
    'use strict';
    const mensaje = document.getElementById('checkout-mensaje');
    const lista = document.getElementById('checkout-articulos');
    const totalTexto = document.getElementById('checkout-total');
    const boton = document.getElementById('checkout-confirmar');
    const form = document.getElementById('checkout-form');
    const dinero = valor => new Intl.NumberFormat('es-CO', {
        style: 'currency', currency: 'COP', maximumFractionDigits: 0
    }).format(valor);

    const carrito = window.CarritoOMT ? window.CarritoOMT.obtener() : [];
    const mostrarMensaje = (texto, tipo = 'danger') => {
        mensaje.textContent = texto;
        mensaje.className = 'alert alert-' + tipo;
    };

    if (!carrito.length) {
        mostrarMensaje('Tu carrito está vacío. Agrega productos publicados antes de continuar.', 'warning');
        boton.disabled = true;
    } else {
        let total = 0;
        carrito.forEach(producto => {
            const precio = Number(String(producto.precio).replace(/[^0-9]/g, '')) || 0;
            const subtotal = precio * producto.cantidad;
            total += subtotal;
            const fila = document.createElement('div');
            fila.className = 'list-group-item d-flex justify-content-between align-items-center gap-3 px-0';
            const nombre = document.createElement('div');
            nombre.className = 'fw-semibold';
            nombre.textContent = producto.nombre + ' × ' + producto.cantidad;
            const importe = document.createElement('span');
            importe.className = 'text-nowrap';
            importe.textContent = dinero(subtotal);
            fila.append(nombre, importe);
            lista.appendChild(fila);
        });
        totalTexto.textContent = dinero(total);

        const sinIdentificador = carrito.some(producto => !Number.isInteger(Number(producto.id_producto)) || Number(producto.id_producto) < 1);
        if (sinIdentificador) {
            mostrarMensaje('Hay artículos antiguos o de demostración que no están vinculados a un producto de MySQL. Vuelve al carrito, retíralos y agrega productos publicados desde el catálogo de vendedores.', 'warning');
            boton.disabled = true;
        }
    }

    form?.addEventListener('submit', async evento => {
        evento.preventDefault();
        if (boton.disabled) return;
        boton.disabled = true;
        boton.textContent = 'Registrando pedido...';
        mensaje.className = 'alert d-none';

        const datos = new FormData();
        datos.append('csrf_token', document.getElementById('csrf-token').value);
        datos.append('carrito_json', JSON.stringify(carrito.map(producto => ({
            id_producto: Number(producto.id_producto),
            cantidad: Number(producto.cantidad)
        }))));

        try {
            const respuesta = await fetch('crear_pedido.php', {
                method: 'POST',
                body: datos,
                credentials: 'same-origin'
            });
            const resultado = await respuesta.json();
            if (respuesta.status === 401 && resultado.login_required) {
                window.location.href = 'login.php';
                return;
            }
            if (!respuesta.ok || !resultado.success) {
                throw new Error(resultado.mensaje || 'No fue posible registrar el pedido.');
            }

            // El carrito solo se elimina después de recibir confirmación exitosa del servidor.
            localStorage.removeItem('carrito');
            window.dispatchEvent(new CustomEvent('carrito:actualizado'));
            window.location.href = 'pedido_confirmacion.php?id=' + encodeURIComponent(resultado.pedido_id);
        } catch (error) {
            mostrarMensaje(error.message || 'Ocurrió un error. El carrito se conservó.');
            boton.disabled = false;
            boton.textContent = 'Registrar pedido pendiente';
        }
    });
})();
</script>
<?php endif; ?>
</body>
</html>
