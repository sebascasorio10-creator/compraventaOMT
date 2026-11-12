<?php
/**
 * Configuración local de MySQL para XAMPP.
 * En producción, mueve estas credenciales a variables de entorno.
 */
$host = '127.0.0.1';
$dbname = 'compraventa_omt';
$username = 'root';
$password = '';

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
    exit('No fue posible conectar con la base de datos. Verifica XAMPP y config/database.php.');
}
