<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (empty($_SESSION['usuario']) || ($_SESSION['usuario']['rol'] ?? '') !== 'comprador') {
    header('Location: login.php');
    exit;
}

$idPedido = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$idPedido || $idPedido < 1) {
    http_response_code(404);
    exit('No se encontró el pedido solicitado.');
}

$columnasPedidos = $pdo->query('SHOW COLUMNS FROM pedidos')->fetchAll();
$nombresColumnas = array_column($columnasPedidos, 'Field');
$columnaComprador = in_array('id_comprador', $nombresColumnas, true) ? 'id_comprador' : (in_array('id_usuario', $nombresColumnas, true) ? 'id_usuario' : null);
if (!$columnaComprador) {
    http_response_code(500);
    exit('No fue posible verificar el propietario del pedido.');
}

$sqlPedido = 'SELECT * FROM pedidos WHERE id_pedido = ? AND `' . $columnaComprador . '` = ? LIMIT 1';
$stmtPedido = $pdo->prepare($sqlPedido);
$stmtPedido->execute([$idPedido, (int)$_SESSION['usuario']['id']]);
$pedido = $stmtPedido->fetch();
if (!$pedido) {
    http_response_code(404);
    exit('No se encontró el pedido solicitado.');
}

$stmtDetalles = $pdo->prepare('SELECT * FROM pedido_detalles WHERE id_pedido = ? ORDER BY id_detalle ASC');
$stmtDetalles->execute([$idPedido]);
$detalles = $stmtDetalles->fetchAll();
$totalPedido = $pedido['total'] ?? $pedido['total_pedido'] ?? $pedido['monto_total'] ?? 0;
$estadoPedido = $pedido['estado'] ?? 'pendiente';
$fechaPedido = $pedido['fecha_pedido'] ?? $pedido['creado_en'] ?? $pedido['created_at'] ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedido registrado | CompraVenta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container py-5" style="max-width: 850px;">
    <div class="bg-white rounded-4 shadow-sm p-4 p-md-5">
        <div class="text-center mb-4">
            <div class="text-success display-4">✓</div>
            <h1 class="h3 fw-bold">Tu pedido fue registrado</h1>
            <p class="text-secondary mb-0">Número de pedido: #<?= (int)$idPedido ?></p>
        </div>

        <div class="alert alert-warning">
            <strong>Estado: <?= e(ucfirst((string)$estadoPedido)) ?>.</strong>
            El pedido está pendiente. No se ha realizado ningún cobro ni se ha confirmado un pago.
        </div>

        <?php if ($fechaPedido): ?><p class="text-secondary small">Fecha: <?= e(date('d/m/Y H:i', strtotime((string)$fechaPedido))) ?></p><?php endif; ?>

        <div class="table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Producto</th><th>Cantidad</th><th>Precio unitario</th><th>Subtotal</th></tr></thead>
                <tbody>
                <?php foreach ($detalles as $detalle): ?>
                    <tr>
                        <td><?= e($detalle['nombre_producto'] ?? 'Producto') ?></td>
                        <td><?= (int)($detalle['cantidad'] ?? 0) ?></td>
                        <td>$<?= number_format((float)($detalle['precio_unitario'] ?? $detalle['precio'] ?? 0), 0, ',', '.') ?></td>
                        <td>$<?= number_format((float)($detalle['subtotal'] ?? 0), 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot><tr><th colspan="3" class="text-end">Total registrado</th><th>$<?= number_format((float)$totalPedido, 0, ',', '.') ?></th></tr></tfoot>
            </table>
        </div>

        <a href="index.php" class="btn btn-warning w-100 mt-3">Volver al catálogo</a>
    </div>
</main>
</body>
</html>
