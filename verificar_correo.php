<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (!entorno_local()) {
    http_response_code(503);
    exit('La entrega de códigos por correo todavía no está configurada. Esta función está habilitada únicamente para pruebas en localhost.');
}

$idUsuario = (int)($_SESSION['pending_verification_user_id'] ?? 0);
if ($idUsuario <= 0) {
    redirigir_con_error('Primero debes registrarte para verificar tu correo.', 'elegir_registro.php');
}

$stmtUsuario = $pdo->prepare('SELECT id_usuario, correo, correo_verificado FROM usuarios WHERE id_usuario = ? LIMIT 1');
$stmtUsuario->execute([$idUsuario]);
$usuarioPendiente = $stmtUsuario->fetch();

if (!$usuarioPendiente) {
    unset($_SESSION['pending_verification_user_id'], $_SESSION['pending_verification_email'], $_SESSION['verification_test_code']);
    redirigir_con_error('No encontramos la cuenta que debes verificar. Regístrate nuevamente.', 'elegir_registro.php');
}

if ((int)$usuarioPendiente['correo_verificado'] === 1) {
    unset($_SESSION['pending_verification_user_id'], $_SESSION['pending_verification_email'], $_SESSION['verification_test_code']);
    $_SESSION['flash_success'] = 'Tu correo ya está verificado. Ahora puedes iniciar sesión.';
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $accion = $_POST['accion'] ?? 'verificar';

    if ($accion === 'reenviar') {
        $stmtEnvio = $pdo->prepare('SELECT TIMESTAMPDIFF(SECOND, ultimo_envio_en, NOW()) AS segundos FROM verificaciones_correo WHERE id_usuario = ? LIMIT 1');
        $stmtEnvio->execute([$idUsuario]);
        $envio = $stmtEnvio->fetch();

        if ($envio && (int)$envio['segundos'] < 60) {
            $restantes = 60 - (int)$envio['segundos'];
            redirigir_con_error('Espera ' . $restantes . ' segundos antes de solicitar otro código.', 'verificar_correo.php');
        }

        try {
            $codigo = crear_codigo_verificacion($pdo, $idUsuario);
            $_SESSION['verification_test_code'] = $codigo;
            $_SESSION['flash_success'] = 'Generamos un código nuevo. Úsalo antes de que pasen 10 minutos.';
        } catch (Throwable $e) {
            error_log($e->getMessage());
            redirigir_con_error('No fue posible generar otro código. Inténtalo nuevamente.', 'verificar_correo.php');
        }

        header('Location: verificar_correo.php');
        exit;
    }

    $codigoIngresado = trim((string)($_POST['codigo'] ?? ''));
    if (!preg_match('/^\d{6}$/', $codigoIngresado)) {
        redirigir_con_error('Escribe los 6 números del código.', 'verificar_correo.php');
    }

    $stmtCodigo = $pdo->prepare(
        'SELECT codigo_hash, intentos, (expira_en > NOW()) AS vigente, usado_en
         FROM verificaciones_correo WHERE id_usuario = ? LIMIT 1'
    );
    $stmtCodigo->execute([$idUsuario]);
    $registroCodigo = $stmtCodigo->fetch();

    if (!$registroCodigo || $registroCodigo['usado_en'] !== null) {
        redirigir_con_error('No hay un código activo. Solicita uno nuevo.', 'verificar_correo.php');
    }
    if ((int)$registroCodigo['vigente'] !== 1) {
        redirigir_con_error('El código venció. Solicita uno nuevo.', 'verificar_correo.php');
    }
    if ((int)$registroCodigo['intentos'] >= 5) {
        redirigir_con_error('Alcanzaste el máximo de 5 intentos. Solicita un código nuevo.', 'verificar_correo.php');
    }

    if (!password_verify($codigoIngresado, $registroCodigo['codigo_hash'])) {
        $pdo->prepare('UPDATE verificaciones_correo SET intentos = intentos + 1 WHERE id_usuario = ?')->execute([$idUsuario]);
        redirigir_con_error('El código no coincide. Revisa e inténtalo nuevamente.', 'verificar_correo.php');
    }

    try {
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE usuarios SET correo_verificado = 1 WHERE id_usuario = ?')->execute([$idUsuario]);
        $pdo->prepare('UPDATE verificaciones_correo SET usado_en = NOW() WHERE id_usuario = ?')->execute([$idUsuario]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log($e->getMessage());
        redirigir_con_error('No se pudo confirmar el correo. Inténtalo nuevamente.', 'verificar_correo.php');
    }

    unset($_SESSION['pending_verification_user_id'], $_SESSION['pending_verification_email'], $_SESSION['verification_test_code']);
    $_SESSION['flash_success'] = '¡Correo verificado! Ya puedes iniciar sesión.';
    header('Location: login.php');
    exit;
}

$error = $_SESSION['flash_error'] ?? '';
$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);
$codigoPrueba = $_SESSION['verification_test_code'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Verificar correo | CompraVenta</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-7 col-lg-5">
      <div class="card border-0 shadow rounded-4">
        <div class="card-body p-4 p-md-5">
          <h2 class="text-center mb-2">Verifica tu correo</h2>
          <p class="text-center text-muted mb-4">Enviamos un código de prueba para <strong><?= e($usuarioPendiente['correo']) ?></strong>.</p>

          <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
          <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

          <div class="alert alert-info">
            <strong>Modo de pruebas local:</strong> todavía no está conectado el envío SMTP. Usa el código que aparece aquí para probar el proceso.
            <?php if ($codigoPrueba !== ''): ?>
              <div class="text-center fs-2 fw-bold my-2" style="letter-spacing: .25em;"><?= e($codigoPrueba) ?></div>
              <small>Este código vence en 10 minutos. No compartas este código.</small>
            <?php else: ?>
              <div class="small mt-2">Si no tienes el código, solicita uno nuevo.</div>
            <?php endif; ?>
          </div>

          <form method="POST" action="verificar_correo.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <div class="mb-3">
              <label for="codigo" class="form-label">Código de 6 dígitos</label>
              <input id="codigo" name="codigo" class="form-control form-control-lg text-center" type="text" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="one-time-code" placeholder="000000" required>
            </div>
            <button class="btn btn-warning w-100" type="submit" name="accion" value="verificar">Verificar correo</button>
          </form>

          <form method="POST" action="verificar_correo.php" class="mt-3">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <button class="btn btn-outline-secondary w-100" type="submit" name="accion" value="reenviar">Generar otro código</button>
          </form>

          <p class="text-center text-muted small mt-4 mb-0">El código dura 10 minutos y permite máximo 5 intentos. Debes verificar tu correo antes de iniciar sesión.</p>
        </div>
      </div>
    </div>
  </div>
</main>
</body>
</html>
