<?php
/**
 * Ejecutar SOLO desde terminal:
 * C:\\xampp\\php\\php.exe scripts/crear_admin.php
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Este script solo se puede ejecutar desde la terminal.\n");
}
require_once __DIR__ . '/../config/database.php';

echo 'Correo del administrador: ';
$correo = strtolower(trim(fgets(STDIN)));
echo 'Nombre del administrador: ';
$nombre = trim(fgets(STDIN));
echo "Contraseña (mínimo 10 caracteres): ";
$password = trim(fgets(STDIN));

if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || $nombre === '' || strlen($password) < 10) {
    fwrite(STDERR, "Datos inválidos. No se creó el administrador.\n");
    exit(1);
}
$rol = $pdo->query("SELECT id_rol FROM roles WHERE nombre='administrador'")->fetchColumn();
if (!$rol) {
    fwrite(STDERR, "No existe el rol administrador. Ejecuta primero database/compra_venta.sql.\n");
    exit(1);
}
$stmt = $pdo->prepare('INSERT INTO usuarios (id_rol, nombres, correo, password_hash) VALUES (?, ?, ?, ?)');
try {
    $stmt->execute([$rol, $nombre, $correo, password_hash($password, PASSWORD_DEFAULT)]);
    echo "Administrador creado correctamente. Ya puedes iniciar sesión en login.php.\n";
} catch (PDOException $e) {
    fwrite(STDERR, $e->getCode() === '23000' ? "Ese correo ya está registrado.\n" : "No se pudo crear el administrador.\n");
    exit(1);
}
