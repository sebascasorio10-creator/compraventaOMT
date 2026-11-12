<?php
require_once __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $nombreCompleto = preg_replace('/\s+/', ' ', trim($_POST['nombre_completo'] ?? ''));
    $partes = explode(' ', $nombreCompleto, 2);
    $nombres = $partes[0] ?? '';
    $apellidos = $partes[1] ?? '';
    $documento = trim($_POST['documento'] ?? '');
    $correo = strtolower(trim($_POST['correo'] ?? ''));
    $celular = trim($_POST['celular'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmacion = $_POST['confirmacion'] ?? '';
    $pais = trim($_POST['pais'] ?? 'Colombia');
    $departamento = trim($_POST['departamento'] ?? '');
    $ciudad = trim($_POST['ciudad'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $tienda = trim($_POST['nombre_tienda'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    if ($nombres === '' || $documento === '' || $correo === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL) || $celular === '' || $tienda === '' || $ciudad === '') {
        redirigir_con_error('Completa los campos obligatorios con información válida.', 'registro_vendedor.php');
    }
    if (strlen($password) < 8 || $password !== $confirmacion) {
        redirigir_con_error('La contraseña debe tener mínimo 8 caracteres y coincidir con la confirmación.', 'registro_vendedor.php');
    }
    if (empty($_POST['acepta_terminos'])) {
        redirigir_con_error('Debes aceptar los términos y condiciones.', 'registro_vendedor.php');
    }

    try {
        $pdo->beginTransaction();
        $rol = $pdo->query("SELECT id_rol FROM roles WHERE nombre = 'vendedor'")->fetchColumn();
        $usuario = $pdo->prepare("INSERT INTO usuarios (id_rol, nombres, apellidos, tipo_documento, numero_documento, correo, celular, password_hash) VALUES (?, ?, ?, 'CC', ?, ?, ?, ?)");
        $usuario->execute([$rol, $nombres, $apellidos, $documento, $correo, $celular, password_hash($password, PASSWORD_DEFAULT)]);
        $idVendedor = (int)$pdo->lastInsertId();

        $tiendaStmt = $pdo->prepare('INSERT INTO tiendas (id_vendedor, nombre_tienda, descripcion, pais, departamento, ciudad, direccion) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $tiendaStmt->execute([$idVendedor, $tienda, $descripcion, $pais, $departamento, $ciudad, $direccion]);
        $pdo->commit();

        $_SESSION['flash_success'] = 'Tu cuenta de vendedor fue creada. La tienda queda pendiente de verificación.';
        header('Location: login.php');
        exit;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if ($e->getCode() === '23000') {
            redirigir_con_error('El correo o documento ya está registrado.', 'registro_vendedor.php');
        }
        error_log($e->getMessage());
        redirigir_con_error('No se pudo crear la cuenta. Verifica los datos e inténtalo nuevamente.', 'registro_vendedor.php');
    }
}
$error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);
?>
<!DOCTYPE html>
<html lang="es"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Registro de vendedor | CompraVenta</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="css/registro_vendedor.css">
</head><body class="bg-light">
<nav class="navbar bg-white shadow-sm"><div class="container"><a class="navbar-brand fw-bold fs-3" href="index.html">COMPRA<span class="text-warning">VENTA</span></a></div></nav>
<main class="container py-5"><div class="row justify-content-center"><div class="col-lg-8"><div class="card shadow border-0 rounded-4"><div class="card-body p-5">
<h2 class="text-center mb-4">Registro de vendedor</h2>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<form method="POST" action="registro_vendedor.php">
<input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
<h5 class="text-warning mb-3">Datos personales</h5><div class="row">
<div class="col-md-6 mb-3"><label class="form-label" for="nombre_completo">Nombre completo *</label><input id="nombre_completo" name="nombre_completo" class="form-control" maxlength="160" required></div>
<div class="col-md-6 mb-3"><label class="form-label" for="documento">Documento (CC) *</label><input id="documento" name="documento" class="form-control" maxlength="30" required></div>
<div class="col-md-6 mb-3"><label class="form-label" for="correo">Correo *</label><input id="correo" name="correo" type="email" class="form-control" maxlength="150" required></div>
<div class="col-md-6 mb-3"><label class="form-label" for="celular">Celular *</label><input id="celular" name="celular" type="tel" class="form-control" maxlength="25" required></div>
<div class="col-md-6 mb-3"><label class="form-label" for="password">Contraseña *</label><input id="password" name="password" type="password" class="form-control" minlength="8" required></div>
<div class="col-md-6 mb-3"><label class="form-label" for="confirmacion">Confirmar contraseña *</label><input id="confirmacion" name="confirmacion" type="password" class="form-control" minlength="8" required></div>
</div><hr><h5 class="text-warning mb-3">Dirección</h5><div class="row">
<div class="col-md-4 mb-3"><label class="form-label" for="pais">País</label><input id="pais" name="pais" class="form-control" value="Colombia"></div>
<div class="col-md-4 mb-3"><label class="form-label" for="departamento">Departamento</label><input id="departamento" name="departamento" class="form-control"></div>
<div class="col-md-4 mb-3"><label class="form-label" for="ciudad">Ciudad *</label><input id="ciudad" name="ciudad" class="form-control" required></div>
<div class="col-12 mb-3"><label class="form-label" for="direccion">Dirección</label><input id="direccion" name="direccion" class="form-control"></div>
</div><hr><h5 class="text-warning mb-3">Información de la tienda</h5>
<div class="mb-3"><label class="form-label" for="nombre_tienda">Nombre de la tienda *</label><input id="nombre_tienda" name="nombre_tienda" class="form-control" maxlength="120" required></div>
<div class="mb-3"><label class="form-label" for="descripcion">Descripción</label><textarea id="descripcion" name="descripcion" class="form-control" rows="3"></textarea></div>
<div class="form-check mb-4"><input class="form-check-input" type="checkbox" id="acepta_terminos" name="acepta_terminos" value="1" required><label class="form-check-label" for="acepta_terminos">Acepto los términos y condiciones.</label></div>
<button class="btn btn-warning w-100" type="submit">Crear cuenta de vendedor</button></form>
<div class="text-center mt-4">¿Ya tienes cuenta? <a href="login.php" class="text-warning fw-bold">Inicia sesión</a></div>
</div></div></div></div></main></body></html>
