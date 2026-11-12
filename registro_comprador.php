<?php
require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $nombres = trim($_POST['nombres'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $correo = strtolower(trim($_POST['correo'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirmacion = $_POST['confirmacion'] ?? '';

    if ($nombres === '' || $apellidos === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        redirigir_con_error('Completa todos los campos con información válida.', 'registro_comprador.php');
    }
    if (strlen($password) < 8 || $password !== $confirmacion) {
        redirigir_con_error('La contraseña debe tener mínimo 8 caracteres y coincidir con la confirmación.', 'registro_comprador.php');
    }

    try {
        $rol = $pdo->prepare("SELECT id_rol FROM roles WHERE nombre = 'comprador'");
        $rol->execute();
        $idRol = $rol->fetchColumn();
        $stmt = $pdo->prepare('INSERT INTO usuarios (id_rol, nombres, apellidos, correo, password_hash) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$idRol, $nombres, $apellidos, $correo, password_hash($password, PASSWORD_DEFAULT)]);
        $_SESSION['flash_success'] = 'Tu cuenta de comprador fue creada. Ya puedes iniciar sesión.';
        header('Location: login.php');
        exit;
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            redirigir_con_error('Ese correo ya está registrado.', 'registro_comprador.php');
        }
        error_log($e->getMessage());
        redirigir_con_error('No se pudo crear la cuenta. Inténtalo nuevamente.', 'registro_comprador.php');
    }
}
$error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="es"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Registro de comprador | CompraVenta</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="css/registro_comprador.css">
</head><body class="bg-light">
<nav class="navbar bg-white shadow-sm"><div class="container"><a class="navbar-brand fw-bold fs-3" href="index.html">COMPRA<span class="text-warning">VENTA</span></a></div></nav>
<main class="container py-5"><div class="row justify-content-center"><div class="col-lg-6"><div class="card shadow border-0 rounded-4"><div class="card-body p-5">
<h2 class="text-center mb-4">Crear cuenta de comprador</h2>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<form method="POST" action="registro_comprador.php">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<div class="mb-3"><label for="nombres" class="form-label">Nombre</label><input id="nombres" name="nombres" type="text" class="form-control" maxlength="80" autocomplete="given-name" required></div>
<div class="mb-3"><label for="apellidos" class="form-label">Apellido</label><input id="apellidos" name="apellidos" type="text" class="form-control" maxlength="80" autocomplete="family-name" required></div>
<div class="mb-3"><label for="correo" class="form-label">Correo electrónico</label><input id="correo" name="correo" type="email" class="form-control" maxlength="150" autocomplete="email" required></div>
<div class="mb-3"><label for="password" class="form-label">Contraseña</label><input id="password" name="password" type="password" class="form-control" minlength="8" autocomplete="new-password" required></div>
<div class="mb-4"><label for="confirmacion" class="form-label">Confirmar contraseña</label><input id="confirmacion" name="confirmacion" type="password" class="form-control" minlength="8" autocomplete="new-password" required></div>
<button class="btn btn-warning w-100" type="submit">Crear cuenta</button></form>
<div class="text-center mt-4">¿Ya tienes cuenta? <a href="login.php" class="text-warning fw-bold">Inicia sesión</a></div>
</div></div></div></div></main></body></html>
