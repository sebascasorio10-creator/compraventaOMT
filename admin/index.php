<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (empty($_SESSION['usuario']) || strtolower(trim((string)($_SESSION['usuario']['rol'] ?? ''))) !== 'administrador') {
    http_response_code(403);
    exit('Acceso denegado. Inicia sesión con una cuenta administradora.');
}

$vista = $_GET['vista'] ?? 'inicio';
$vistasPermitidas = ['inicio', 'usuarios', 'productos', 'tiendas'];
if (!in_array($vista, $vistasPermitidas, true)) $vista = 'inicio';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $accion = $_POST['accion'] ?? '';
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
    try {
        if ($id < 1) throw new RuntimeException('Registro no válido.');

        if ($accion === 'estado_usuario') {
            $nuevoEstado = $_POST['estado'] ?? '';
            if (!in_array($nuevoEstado, ['activo', 'suspendido'], true)) throw new RuntimeException('Estado de usuario no válido.');
            if ($id === (int)$_SESSION['usuario']['id']) throw new RuntimeException('No puedes cambiar el estado de tu propia cuenta.');
            $stmt = $pdo->prepare("UPDATE usuarios u INNER JOIN roles r ON r.id_rol=u.id_rol SET u.estado=? WHERE u.id_usuario=? AND r.nombre <> 'administrador'");
            $stmt->execute([$nuevoEstado, $id]);
            $_SESSION['flash_success'] = $stmt->rowCount() ? 'Estado del usuario actualizado.' : 'No se modificó el usuario. Verifica que exista y no sea administrador.';
            $vista = 'usuarios';
        } elseif ($accion === 'estado_producto') {
            $nuevoEstado = $_POST['estado'] ?? '';
            if (!in_array($nuevoEstado, ['publicado', 'pausado'], true)) throw new RuntimeException('Estado de producto no válido.');
            $stmt = $pdo->prepare("UPDATE productos SET estado=? WHERE id_producto=? AND estado NOT IN ('eliminado','vendido')");
            $stmt->execute([$nuevoEstado, $id]);
            $_SESSION['flash_success'] = 'Estado de la publicación actualizado.';
            $vista = 'productos';
        } elseif ($accion === 'verificar_tienda') {
            $nuevoEstado = $_POST['estado'] ?? '';
            if (!in_array($nuevoEstado, ['verificada', 'rechazada', 'pendiente'], true)) throw new RuntimeException('Estado de tienda no válido.');
            $stmt = $pdo->prepare('UPDATE tiendas SET estado_verificacion=? WHERE id_tienda=?');
            $stmt->execute([$nuevoEstado, $id]);
            $_SESSION['flash_success'] = 'Estado de verificación de la tienda actualizado.';
            $vista = 'tiendas';
        } else {
            throw new RuntimeException('Acción no reconocida.');
        }
    } catch (Throwable $e) {
        error_log($e->getMessage());
        $_SESSION['flash_error'] = $e instanceof RuntimeException ? $e->getMessage() : 'No se pudo completar la operación. Revisa los registros de PHP.';
    }
    header('Location: index.php?vista=' . $vista);
    exit;
}

$error = $_SESSION['flash_error'] ?? '';
$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

$stats = [
    'usuarios' => (int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn(),
    'compradores' => (int)$pdo->query("SELECT COUNT(*) FROM usuarios u INNER JOIN roles r ON r.id_rol=u.id_rol WHERE r.nombre='comprador'")->fetchColumn(),
    'vendedores' => (int)$pdo->query("SELECT COUNT(*) FROM usuarios u INNER JOIN roles r ON r.id_rol=u.id_rol WHERE r.nombre='vendedor'")->fetchColumn(),
    'productos' => (int)$pdo->query("SELECT COUNT(*) FROM productos WHERE estado NOT IN ('eliminado','vendido')")->fetchColumn(),
    'tiendas_pendientes' => (int)$pdo->query("SELECT COUNT(*) FROM tiendas WHERE estado_verificacion='pendiente'")->fetchColumn(),
    'pedidos' => (int)$pdo->query('SELECT COUNT(*) FROM pedidos')->fetchColumn(),
];

$usuarios = $productos = $tiendas = [];
if ($vista === 'usuarios') {
    $usuarios = $pdo->query("SELECT u.id_usuario,u.nombres,u.apellidos,u.correo,u.estado,u.creado_en,r.nombre AS rol FROM usuarios u INNER JOIN roles r ON r.id_rol=u.id_rol ORDER BY u.creado_en DESC LIMIT 200")->fetchAll();
} elseif ($vista === 'productos') {
    $productos = $pdo->query("SELECT p.id_producto,p.nombre,p.precio,p.estado,p.cantidad_disponible,p.creado_en,u.nombres,u.apellidos,t.nombre_tienda FROM productos p INNER JOIN usuarios u ON u.id_usuario=p.id_vendedor LEFT JOIN tiendas t ON t.id_vendedor=p.id_vendedor WHERE p.estado <> 'eliminado' ORDER BY p.creado_en DESC LIMIT 200")->fetchAll();
} elseif ($vista === 'tiendas') {
    $tiendas = $pdo->query("SELECT t.id_tienda,t.nombre_tienda,t.ciudad,t.estado_verificacion,t.creado_en,u.nombres,u.apellidos,u.correo FROM tiendas t INNER JOIN usuarios u ON u.id_usuario=t.id_vendedor ORDER BY t.creado_en DESC LIMIT 200")->fetchAll();
}
?>
<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Panel administrador | CompraVenta OMT</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root{--omt-yellow:#ffc107;--omt-dark:#171717}body{background:#f5f6f8;color:#252525}.topbar{background:var(--omt-dark)}.brand{color:#fff;text-decoration:none;font-weight:800;letter-spacing:.2px}.brand span{color:var(--omt-yellow)}.admin-shell{min-height:calc(100vh - 64px)}.sidebar{background:#fff;border-right:1px solid #e8e8e8}.sidebar .nav-link{color:#454545;border-radius:9px;padding:11px 13px}.sidebar .nav-link.active,.sidebar .nav-link:hover{background:#fff3cd;color:#765400}.main{padding:28px}.stat-card,.panel-card{border:0;border-radius:14px;box-shadow:0 3px 14px rgba(0,0,0,.045)}.stat-icon{width:42px;height:42px;border-radius:12px;background:#fff3cd;color:#765400;display:grid;place-items:center;font-size:20px}.table td{vertical-align:middle}.table th{white-space:nowrap}.muted{color:#777}@media(max-width:767px){.sidebar{border-right:0;border-bottom:1px solid #eee}.sidebar .nav{flex-direction:row;flex-wrap:wrap}.main{padding:18px}}
</style></head><body>
<nav class="navbar topbar navbar-dark px-3 px-lg-4"><a class="brand" href="index.php">COMPRA<span>VENTA</span> <small class="text-white-50 fw-normal">/ Administración</small></a><div class="ms-auto d-flex align-items-center gap-3 text-white"><span class="small d-none d-sm-inline"><?= e($_SESSION['usuario']['nombre']) ?></span><a class="btn btn-outline-light btn-sm" href="../index.php">Ver sitio</a><a class="btn btn-warning btn-sm fw-semibold" href="../auth/logout.php">Cerrar sesión</a></div></nav>
<div class="container-fluid admin-shell"><div class="row min-vh-100">
<aside class="col-md-3 col-lg-2 sidebar p-3"><div class="fw-bold mb-3">Administración</div><nav class="nav nav-pills flex-column gap-1">
<a class="nav-link <?= $vista==='inicio'?'active':'' ?>" href="index.php"><i class="bi bi-speedometer2 me-2"></i>Resumen</a>
<a class="nav-link <?= $vista==='usuarios'?'active':'' ?>" href="index.php?vista=usuarios"><i class="bi bi-people me-2"></i>Usuarios</a>
<a class="nav-link <?= $vista==='productos'?'active':'' ?>" href="index.php?vista=productos"><i class="bi bi-box-seam me-2"></i>Publicaciones</a>
<a class="nav-link <?= $vista==='tiendas'?'active':'' ?>" href="index.php?vista=tiendas"><i class="bi bi-shop me-2"></i>Tiendas</a>
</nav><hr><div class="small text-secondary">Panel de control de CompraVenta OMT</div></aside>
<main class="col-md-9 col-lg-10 main">
<?php if($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><?php if($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if($vista==='inicio'): ?>
<div class="mb-4"><h1 class="h3 fw-bold mb-1">Panel de administrador</h1><p class="muted mb-0">Resumen general y herramientas para supervisar la plataforma.</p></div>
<div class="row g-3 mb-4">
<?php foreach([['Usuarios',$stats['usuarios'],'bi-people'],['Compradores',$stats['compradores'],'bi-person'],['Vendedores',$stats['vendedores'],'bi-shop'],['Publicaciones',$stats['productos'],'bi-box-seam'],['Tiendas por verificar',$stats['tiendas_pendientes'],'bi-shield-check'],['Pedidos',$stats['pedidos'],'bi-receipt']] as $s): ?>
<div class="col-sm-6 col-xl-4"><div class="card stat-card h-100"><div class="card-body d-flex align-items-center gap-3"><div class="stat-icon"><i class="bi <?= e($s[2]) ?>"></i></div><div><div class="text-secondary small"><?= e($s[0]) ?></div><div class="fs-3 fw-bold"><?= (int)$s[1] ?></div></div></div></div></div>
<?php endforeach; ?>
</div>
<div class="row g-3"><div class="col-lg-4"><div class="card panel-card h-100"><div class="card-body"><i class="bi bi-people fs-2 text-warning"></i><h2 class="h5 mt-3">Gestión de usuarios</h2><p class="text-secondary small">Consulta cuentas y activa o suspende compradores y vendedores.</p><a class="btn btn-outline-dark btn-sm" href="index.php?vista=usuarios">Administrar usuarios</a></div></div></div>
<div class="col-lg-4"><div class="card panel-card h-100"><div class="card-body"><i class="bi bi-box-seam fs-2 text-warning"></i><h2 class="h5 mt-3">Publicaciones</h2><p class="text-secondary small">Revisa los productos publicados y pausa o reactiva publicaciones.</p><a class="btn btn-outline-dark btn-sm" href="index.php?vista=productos">Revisar productos</a></div></div></div>
<div class="col-lg-4"><div class="card panel-card h-100"><div class="card-body"><i class="bi bi-shop fs-2 text-warning"></i><h2 class="h5 mt-3">Verificación de tiendas</h2><p class="text-secondary small">Consulta las tiendas y gestiona su estado de verificación.</p><a class="btn btn-outline-dark btn-sm" href="index.php?vista=tiendas">Gestionar tiendas</a></div></div></div></div>
<?php elseif($vista==='usuarios'): ?>
<div class="mb-3"><h1 class="h3 fw-bold">Usuarios</h1><p class="muted">Últimos 200 registros. No puedes suspender cuentas administradoras ni tu propia cuenta.</p></div>
<div class="card panel-card"><div class="card-body"><div class="table-responsive"><table class="table table-hover align-middle"><thead class="table-light"><tr><th>Usuario</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Registro</th><th>Acción</th></tr></thead><tbody>
<?php foreach($usuarios as $u): ?><tr><td class="fw-semibold"><?= e(trim($u['nombres'].' '.$u['apellidos'])) ?></td><td><?= e($u['correo']) ?></td><td><?= e(ucfirst($u['rol'])) ?></td><td><span class="badge <?= $u['estado']==='activo'?'text-bg-success':($u['estado']==='suspendido'?'text-bg-danger':'text-bg-warning') ?>"><?= e(ucfirst($u['estado'])) ?></span></td><td><?= e(date('d/m/Y',strtotime($u['creado_en']))) ?></td><td><?php if($u['rol']!=='administrador' && (int)$u['id_usuario']!==(int)$_SESSION['usuario']['id']): ?><form method="post" class="d-flex gap-1"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="accion" value="estado_usuario"><input type="hidden" name="id" value="<?= (int)$u['id_usuario'] ?>"><input type="hidden" name="estado" value="<?= $u['estado']==='suspendido'?'activo':'suspendido' ?>"><button class="btn btn-sm <?= $u['estado']==='suspendido'?'btn-success':'btn-outline-danger' ?>"><?= $u['estado']==='suspendido'?'Activar':'Suspender' ?></button></form><?php else: ?><span class="text-secondary small">Protegido</span><?php endif; ?></td></tr><?php endforeach; ?>
<?php if(!$usuarios): ?><tr><td colspan="6" class="text-center text-secondary">No hay usuarios registrados.</td></tr><?php endif; ?></tbody></table></div></div></div>
<?php elseif($vista==='productos'): ?>
<div class="mb-3"><h1 class="h3 fw-bold">Publicaciones</h1><p class="muted">Puedes pausar publicaciones que necesiten revisión o volver a publicarlas.</p></div>
<div class="card panel-card"><div class="card-body"><div class="table-responsive"><table class="table table-hover align-middle"><thead class="table-light"><tr><th>Producto</th><th>Vendedor / tienda</th><th>Precio</th><th>Stock</th><th>Estado</th><th>Acción</th></tr></thead><tbody>
<?php foreach($productos as $p): ?><tr><td class="fw-semibold"><?= e($p['nombre']) ?><div class="small text-secondary">#<?= (int)$p['id_producto'] ?></div></td><td><?= e(trim($p['nombres'].' '.$p['apellidos'])) ?><div class="small text-secondary"><?= e($p['nombre_tienda']??'') ?></div></td><td>$<?= number_format((float)$p['precio'],0,',','.') ?></td><td><?= (int)$p['cantidad_disponible'] ?></td><td><span class="badge <?= $p['estado']==='publicado'?'text-bg-success':'text-bg-secondary' ?>"><?= e(ucfirst($p['estado'])) ?></span></td><td><?php if(in_array($p['estado'],['publicado','pausado'],true)): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="accion" value="estado_producto"><input type="hidden" name="id" value="<?= (int)$p['id_producto'] ?>"><input type="hidden" name="estado" value="<?= $p['estado']==='publicado'?'pausado':'publicado' ?>"><button class="btn btn-sm <?= $p['estado']==='publicado'?'btn-outline-danger':'btn-success' ?>"><?= $p['estado']==='publicado'?'Pausar':'Publicar' ?></button></form><?php else: ?><span class="text-secondary small">Sin acción</span><?php endif; ?></td></tr><?php endforeach; ?>
<?php if(!$productos): ?><tr><td colspan="6" class="text-center text-secondary">No hay publicaciones.</td></tr><?php endif; ?></tbody></table></div></div></div>
<?php elseif($vista==='tiendas'): ?>
<div class="mb-3"><h1 class="h3 fw-bold">Tiendas</h1><p class="muted">Verifica las tiendas de vendedores y consulta sus datos principales.</p></div>
<div class="card panel-card"><div class="card-body"><div class="table-responsive"><table class="table table-hover align-middle"><thead class="table-light"><tr><th>Tienda</th><th>Vendedor</th><th>Correo</th><th>Ciudad</th><th>Verificación</th><th>Acción</th></tr></thead><tbody>
<?php foreach($tiendas as $t): ?><tr><td class="fw-semibold"><?= e($t['nombre_tienda']) ?></td><td><?= e(trim($t['nombres'].' '.$t['apellidos'])) ?></td><td><?= e($t['correo']) ?></td><td><?= e($t['ciudad']) ?></td><td><span class="badge <?= $t['estado_verificacion']==='verificada'?'text-bg-success':($t['estado_verificacion']==='rechazada'?'text-bg-danger':'text-bg-warning') ?>"><?= e(ucfirst($t['estado_verificacion'])) ?></span></td><td><form method="post" class="d-flex gap-1 flex-wrap"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="accion" value="verificar_tienda"><input type="hidden" name="id" value="<?= (int)$t['id_tienda'] ?>"><button class="btn btn-sm btn-success" name="estado" value="verificada">Verificar</button><button class="btn btn-sm btn-outline-danger" name="estado" value="rechazada">Rechazar</button><button class="btn btn-sm btn-outline-secondary" name="estado" value="pendiente">Pendiente</button></form></td></tr><?php endforeach; ?>
<?php if(!$tiendas): ?><tr><td colspan="6" class="text-center text-secondary">No hay tiendas registradas.</td></tr><?php endif; ?></tbody></table></div></div></div>
<?php endif; ?>
</main></div></div></body></html>