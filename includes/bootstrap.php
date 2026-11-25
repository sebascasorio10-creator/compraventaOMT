<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/../config/database.php';

function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validar_csrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        exit('La sesión del formulario venció. Regresa e inténtalo nuevamente.');
    }
}

/**
 * La entrega del código está en modo de pruebas local hasta configurar SMTP.
 * Nunca se muestra el código en un dominio público.
 */
function entorno_local(): bool {
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    $host = preg_replace('/:\\d+$/', '', $host);
    return in_array($host, ['localhost', '127.0.0.1'], true);
}

/**
 * Genera un código de seis dígitos. En la base de datos solo se guarda su hash.
 */
function crear_codigo_verificacion(PDO $pdo, int $idUsuario): string {
    $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $hash = password_hash($codigo, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        'INSERT INTO verificaciones_correo
            (id_usuario, codigo_hash, expira_en, intentos, ultimo_envio_en, usado_en)
         VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE), 0, NOW(), NULL)
         ON DUPLICATE KEY UPDATE
            codigo_hash = VALUES(codigo_hash),
            expira_en = DATE_ADD(NOW(), INTERVAL 10 MINUTE),
            intentos = 0,
            ultimo_envio_en = NOW(),
            usado_en = NULL'
    );
    $stmt->execute([$idUsuario, $hash]);

    return $codigo;
}

function redirigir_con_error(string $mensaje, string $destino): void {
    $_SESSION['flash_error'] = $mensaje;
    header('Location: ' . $destino);
    exit;
}
