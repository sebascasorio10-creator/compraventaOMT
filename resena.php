<?php
require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}
validar_csrf();

$usuario = $_SESSION['usuario'] ?? null;
if (!$usuario || strtolower((string)($usuario['rol'] ?? '')) !== 'comprador') {
    redirigir_con_error('Inicia sesión con una cuenta de comprador para publicar una reseña.', 'login.php');
}

$idProducto = filter_var($_POST['id_producto'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
$calificacion = filter_var($_POST['calificacion'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
$comentario = trim((string)($_POST['comentario'] ?? ''));

if ($idProducto < 1 || $calificacion < 1 || $calificacion > 5 || $comentario === '' || mb_strlen($comentario) > 1000) {
    redirigir_con_error('Revisa la calificación y escribe una opinión de máximo 1000 caracteres.', 'index.php#productos');
}

$productoStmt = $pdo->prepare("SELECT id_producto FROM productos WHERE id_producto = ? AND estado = 'publicado' LIMIT 1");
$productoStmt->execute([$idProducto]);
if (!$productoStmt->fetchColumn()) {
    redirigir_con_error('El producto ya no está disponible para reseñas.', 'index.php#productos');
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO resenas_productos (id_producto, id_usuario, calificacion, comentario)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            calificacion = VALUES(calificacion),
            comentario = VALUES(comentario),
            actualizado_en = CURRENT_TIMESTAMP
    ");
    $stmt->execute([$idProducto, (int)$usuario['id'], $calificacion, $comentario]);
    $_SESSION['flash_success'] = '¡Gracias! Tu reseña quedó guardada.';
} catch (PDOException $e) {
    error_log('Error guardando reseña: ' . $e->getMessage());
    $_SESSION['flash_error'] = 'No fue posible guardar la reseña. Verifica que se haya ejecutado la migración de reseñas.';
}

header('Location: producto.php?id_producto=' . $idProducto . '#resenas');
exit;
