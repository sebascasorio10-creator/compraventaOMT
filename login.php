<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!empty($_SESSION['usuario'])) {
    header('Location: ' . ($_SESSION['usuario']['rol'] === 'administrador' ? 'admin/usuarios.php' : 'index.php'));
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $correo = strtolower(trim($_POST['correo'] ?? ''));
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare('SELECT u.id_usuario, u.nombres, u.apellidos, u.correo, u.password_hash, u.estado, r.nombre AS rol FROM usuarios u INNER JOIN roles r ON r.id_rol = u.id_rol WHERE u.correo = ? LIMIT 1');
    $stmt->execute([$correo]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($password, $usuario['password_hash']) || $usuario['estado'] !== 'activo') {
        redirigir_con_error('Correo o contraseña incorrectos, o cuenta inactiva.', 'login.php');
    }
    session_regenerate_id(true);
    $_SESSION['usuario'] = [
        'id' => (int)$usuario['id_usuario'],
        'nombre' => $usuario['nombres'] . ' ' . $usuario['apellidos'],
        'correo' => $usuario['correo'],
        'rol' => $usuario['rol'],
    ];
    header('Location: ' . ($usuario['rol'] === 'administrador' ? 'admin/usuarios.php' : 'index.php'));
    exit;
}
$error = $_SESSION['flash_error'] ?? '';
$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Iniciar sesión | CompraVenta</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="css/login.css"></head><body>
<header class="navbar"><div class="logo"><span class="negro">COMPRA</span><span class="amarillo">VENTA</span></div><nav><a href="index.php">Inicio</a><a href="index.html#productos">Productos</a></nav></header>
<section class="login-container"><div class="login-card"><h2>Bienvenido</h2><p>Inicia sesión para continuar</p>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<form method="POST" action="login.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<div class="input-group"><i class="bi bi-envelope"></i><input type="email" name="correo" placeholder="Correo electrónico" autocomplete="email" required></div>
<div class="input-group"><i class="bi bi-lock"></i><input type="password" name="password" placeholder="Contraseña" autocomplete="current-password" required></div>
<button type="submit">Iniciar sesión</button></form>
<div class="registro">¿No tienes una cuenta? <a href="elegir_registro.php">Regístrate</a></div></div></section>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"></body></html>
