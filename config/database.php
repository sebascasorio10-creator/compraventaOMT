<?php
/**
 * Configuración local de MySQL para XAMPP.
 * Puedes crear config/database.local.php (ignorado por Git) para tus credenciales.
 */
$host = '127.0.0.1';
$dbname = 'compraventa_omt';
$username = 'root';
$password = '';

$localConfig = __DIR__ . '/database.local.php';
if (file_exists($localConfig)) {
    require $localConfig; // Debe definir $host, $dbname, $username y $password.
}

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('No fue posible conectar con MySQL. Verifica XAMPP y config/database.php.');
}
