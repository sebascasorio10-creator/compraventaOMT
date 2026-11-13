<?php
require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

function responderPedido(int $codigo, array $datos): void {
    http_response_code($codigo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

function columnasTabla(PDO $pdo, string $tabla): array {
    // Los nombres de tabla son constantes internas, no datos enviados por el usuario.
    $resultado = $pdo->query("SHOW COLUMNS FROM `{$tabla}`")->fetchAll();
    $columnas = [];
    foreach ($resultado as $columna) {
        $columnas[$columna['Field']] = $columna;
    }
    return $columnas;
}

function insertarRegistro(PDO $pdo, string $tabla, array $datos, array $columnas): int {
    $datosValidos = [];
    foreach ($datos as $campo => $valor) {
        if (isset($columnas[$campo])) {
            $datosValidos[$campo] = $valor;
        }
    }

    foreach ($columnas as $campo => $meta) {
        $extra = strtolower((string)($meta['Extra'] ?? ''));
        $esAutoincremental = strpos($extra, 'auto_increment') !== false;
        $esGenerada = strpos($extra, 'generated') !== false;
        $esObligatoria = ($meta['Null'] ?? 'YES') === 'NO' && ($meta['Default'] ?? null) === null;
        if ($esObligatoria && !$esAutoincremental && !$esGenerada && !array_key_exists($campo, $datosValidos)) {
            throw new RuntimeException("La tabla {$tabla} requiere el campo {$campo}; revisa la estructura de la base de datos.");
        }
    }

    if (!$datosValidos) {
        throw new RuntimeException("No se encontraron columnas compatibles en la tabla {$tabla}.");
    }

    $nombres = array_keys($datosValidos);
    $sql = 'INSERT INTO `' . $tabla . '` (' .
        implode(', ', array_map(static fn($nombre) => '`' . $nombre . '`', $nombres)) .
        ') VALUES (' . implode(', ', array_fill(0, count($nombres), '?')) . ')';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_values($datosValidos));
    return (int)$pdo->lastInsertId();
}

function estadoPendiente(array $columna): ?string {
    $tipo = (string)($columna['Type'] ?? '');
    if (preg_match('/^enum\((.*)\)$/i', $tipo, $coincidencia)) {
        $opciones = str_getcsv($coincidencia[1], ',', "'", "\\");
        foreach (['pendiente', 'pendiente_pago', 'pendiente de pago'] as $opcion) {
            if (in_array($opcion, $opciones, true)) {
                return $opcion;
            }
        }
        return null; // Se conserva el valor predeterminado de la tabla si no existe un estado pendiente.
    }
    return 'pendiente';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderPedido(405, ['success' => false, 'mensaje' => 'Método no permitido.']);
}

if (empty($_SESSION['usuario'])) {
    $_SESSION['redirect_after_login'] = 'checkout.php';
    responderPedido(401, ['success' => false, 'login_required' => true, 'mensaje' => 'Inicia sesión para continuar.']);
}
if (($_SESSION['usuario']['rol'] ?? '') !== 'comprador') {
    responderPedido(403, ['success' => false, 'mensaje' => 'Solo una cuenta compradora puede crear pedidos.']);
}

$token = $_POST['csrf_token'] ?? '';
if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    responderPedido(419, ['success' => false, 'mensaje' => 'La sesión venció. Recarga el proceso de compra e inténtalo nuevamente.']);
}

$carritoJson = $_POST['carrito_json'] ?? '';
if (!is_string($carritoJson) || strlen($carritoJson) > 20000) {
    responderPedido(422, ['success' => false, 'mensaje' => 'El carrito enviado no es válido.']);
}
$carrito = json_decode($carritoJson, true);
if (!is_array($carrito) || count($carrito) < 1 || count($carrito) > 50) {
    responderPedido(422, ['success' => false, 'mensaje' => 'El carrito está vacío o contiene demasiados productos.']);
}

// Solo se reciben identificadores y cantidades. Nombre, imagen y precio del navegador se ignoran.
$cantidades = [];
foreach ($carrito as $item) {
    if (!is_array($item)) {
        responderPedido(422, ['success' => false, 'mensaje' => 'Hay un artículo inválido en el carrito.']);
    }
    $idProducto = filter_var($item['id_producto'] ?? null, FILTER_VALIDATE_INT);
    $cantidad = filter_var($item['cantidad'] ?? null, FILTER_VALIDATE_INT);
    if (!$idProducto || $idProducto < 1 || !$cantidad || $cantidad < 1 || $cantidad > 99) {
        responderPedido(422, ['success' => false, 'mensaje' => 'Todos los artículos deben corresponder a productos publicados y tener una cantidad entre 1 y 99.']);
    }
    $cantidades[$idProducto] = ($cantidades[$idProducto] ?? 0) + $cantidad;
    if ($cantidades[$idProducto] > 99) {
        responderPedido(422, ['success' => false, 'mensaje' => 'La cantidad solicitada de un producto supera el límite permitido.']);
    }
}

try {
    $pdo->beginTransaction();

    $ids = array_keys($cantidades);
    $marcadores = implode(',', array_fill(0, count($ids), '?'));
    $productosStmt = $pdo->prepare(
        "SELECT id_producto, id_vendedor, nombre, precio, cantidad_disponible, estado
         FROM productos
         WHERE id_producto IN ({$marcadores})
         FOR UPDATE"
    );
    $productosStmt->execute($ids);
    $productosEncontrados = [];
    foreach ($productosStmt->fetchAll() as $producto) {
        $productosEncontrados[(int)$producto['id_producto']] = $producto;
    }

    $lineas = [];
    $total = 0.0;
    foreach ($cantidades as $idProducto => $cantidad) {
        if (!isset($productosEncontrados[$idProducto])) {
            throw new RuntimeException("El producto #{$idProducto} ya no está disponible. Retíralo del carrito.");
        }
        $producto = $productosEncontrados[$idProducto];
        if (($producto['estado'] ?? '') !== 'publicado') {
            throw new RuntimeException('Uno de los productos ya no está publicado. Actualiza tu carrito.');
        }
        if ($cantidad > (int)$producto['cantidad_disponible']) {
            throw new RuntimeException('No hay unidades suficientes de "' . $producto['nombre'] . '". Disponibles: ' . (int)$producto['cantidad_disponible'] . '.');
        }

        $precio = round((float)$producto['precio'], 2);
        if (!is_finite($precio) || $precio <= 0) {
            throw new RuntimeException('Uno de los productos tiene un precio inválido. Contacta al vendedor.');
        }
        $subtotal = round($precio * $cantidad, 2);
        $total = round($total + $subtotal, 2);
        $lineas[] = [
            'id_producto' => (int)$producto['id_producto'],
            'id_vendedor' => (int)$producto['id_vendedor'],
            'nombre_producto' => (string)$producto['nombre'],
            'cantidad' => $cantidad,
            'precio_unitario' => $precio,
            'subtotal' => $subtotal,
        ];
    }

    $columnasPedidos = columnasTabla($pdo, 'pedidos');
    $columnaComprador = isset($columnasPedidos['id_comprador']) ? 'id_comprador' : (isset($columnasPedidos['id_usuario']) ? 'id_usuario' : null);
    $columnaTotal = isset($columnasPedidos['total']) ? 'total' : (isset($columnasPedidos['total_pedido']) ? 'total_pedido' : (isset($columnasPedidos['monto_total']) ? 'monto_total' : null));
    if (!$columnaComprador || !$columnaTotal) {
        throw new RuntimeException('La tabla pedidos no tiene las columnas de comprador y total esperadas. Se requiere revisar el esquema antes de guardar compras.');
    }

    $datosPedido = [
        $columnaComprador => (int)$_SESSION['usuario']['id'],
        $columnaTotal => number_format($total, 2, '.', ''),
    ];
    if (isset($columnasPedidos['estado'])) {
        $estado = estadoPendiente($columnasPedidos['estado']);
        if ($estado !== null) {
            $datosPedido['estado'] = $estado;
        }
    }
    foreach (['fecha_pedido', 'creado_en', 'created_at'] as $campoFecha) {
        if (isset($columnasPedidos[$campoFecha])) {
            $datosPedido[$campoFecha] = date('Y-m-d H:i:s');
            break;
        }
    }

    $idPedido = insertarRegistro($pdo, 'pedidos', $datosPedido, $columnasPedidos);
    $columnasDetalles = columnasTabla($pdo, 'pedido_detalles');

    foreach ($lineas as $linea) {
        $datosDetalle = [
            'id_pedido' => $idPedido,
            'id_producto' => $linea['id_producto'],
            'id_vendedor' => $linea['id_vendedor'],
            'nombre_producto' => $linea['nombre_producto'],
            'cantidad' => $linea['cantidad'],
            'precio_unitario' => number_format($linea['precio_unitario'], 2, '.', ''),
            'precio' => number_format($linea['precio_unitario'], 2, '.', ''),
            'subtotal' => number_format($linea['subtotal'], 2, '.', ''),
        ];
        foreach (['creado_en', 'created_at'] as $campoFecha) {
            if (isset($columnasDetalles[$campoFecha])) {
                $datosDetalle[$campoFecha] = date('Y-m-d H:i:s');
                break;
            }
        }
        insertarRegistro($pdo, 'pedido_detalles', $datosDetalle, $columnasDetalles);
    }

    $pdo->commit();
    responderPedido(201, [
        'success' => true,
        'pedido_id' => $idPedido,
        'total' => number_format($total, 2, '.', ''),
        'estado' => 'pendiente',
        'mensaje' => 'El pedido quedó registrado y está pendiente de pago o confirmación.',
    ]);
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    responderPedido(422, ['success' => false, 'mensaje' => $e->getMessage()]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error al crear pedido: ' . $e->getMessage());
    responderPedido(500, ['success' => false, 'mensaje' => 'No fue posible guardar el pedido. No se vació el carrito. Revisa la estructura de pedidos y vuelve a intentarlo.']);
}
