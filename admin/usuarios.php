<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (empty($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'administrador') {
    http_response_code(403);
    exit('Acceso denegado. Inicia sesión con una cuenta administradora.');
}

$busqueda = trim($_GET['buscar'] ?? '');
$rol = trim($_GET['rol'] ?? '');
$estado = trim($_GET['estado'] ?? '');
$sql = 'SELECT u.id_usuario, u.nombres, u.apellidos, u.correo, u.celular, u.estado, u.creado_en, r.nombre AS rol FROM usuarios u INNER JOIN roles r ON r.id_rol = u.id_rol WHERE 1=1';
$params = [];
if ($busqueda !== '') {
    $sql .= ' AND (u.nombres LIKE ? OR u.apellidos LIKE ? OR u.correo LIKE ? OR u.numero_documento LIKE ?)';
    $term = '%' . $busqueda . '%';
    array_push($params, $term, $term, $term, $term);
}
if (in_array($rol, ['comprador', 'vendedor', 'administrador'], true)) {
    $sql .= ' AND r.nombre = ?';
    $params[] = $rol;
}
if (in_array($estado, ['activo', 'pendiente', 'suspendido'], true)) {
    $sql .= ' AND u.estado = ?';
    $params[] = $estado;
}
$sql .= ' ORDER BY u.creado_en DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll();
$total = (int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
$totalCompradores = (int)$pdo->query("SELECT COUNT(*) FROM usuarios u JOIN roles r ON r.id_rol=u.id_rol WHERE r.nombre='comprador'")->fetchColumn();
$totalVendedores = (int)$pdo->query("SELECT COUNT(*) FROM usuarios u JOIN roles r ON r.id_rol=u.id_rol WHERE r.nombre='vendedor'")->fetchColumn();
?>
<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Usuarios | Administración CompraVenta</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><style>body{background:#f5f6f8}.navbar{background:#111}.brand{font-weight:800;color:#fff;text-decoration:none;font-size:1.35rem}.brand span{color:#ffc107}.stat{border:0;border-radius:16px}.table thead th{white-space:nowrap}.badge-role{background:#fff3cd;color:#664d03}</style></head><body>
<nav class="navbar navbar-dark"><div class="container-fluid px-4"><a class="brand" href="../index.html">COMPRA<span>VENTA</span> <small class="text-white-50 fs-6">/ Administración</small></a><div class="d-flex align-items-center gap-3 text-white"><span><?= e($_SESSION['usuario']['nombre']) ?></span><a class="btn btn-outline-light btn-sm" href="../auth/logout.php">Cerrar sesión</a></div></div></nav>
<main class="container-fluid px-4 py-4"><div class="d-flex flex-wrap justify-content-between align-items-center mb-4"><div><h1 class="h3 mb-1">Usuarios registrados</h1><p class="text-secondary mb-0">Consulta y filtra las cuentas creadas en CompraVenta OMT.</p></div><span class="badge text-bg-dark fs-6"><?= count($usuarios) ?> resultados</span></div>
<div class="row g-3 mb-4"><div class="col-md-4"><div class="card stat shadow-sm"><div class="card-body"><div class="text-secondary">Total de usuarios</div><div class="display-6 fw-bold"><?= $total ?></div></div></div></div><div class="col-md-4"><div class="card stat shadow-sm"><div class="card-body"><div class="text-secondary">Compradores</div><div class="display-6 fw-bold"><?= $totalCompradores ?></div></div></div></div><div class="col-md-4"><div class="card stat shadow-sm"><div class="card-body"><div class="text-secondary">Vendedores</div><div class="display-6 fw-bold"><?= $totalVendedores ?></div></div></div></div></div>
<div class="card border-0 shadow-sm"><div class="card-body"><form class="row g-2 mb-3" method="GET"><div class="col-lg-5"><input class="form-control" name="buscar" value="<?= e($busqueda) ?>" placeholder="Buscar por nombre, correo o documento"></div><div class="col-lg-3"><select class="form-select" name="rol"><option value="">Todos los roles</option><?php foreach(['comprador','vendedor','administrador'] as $op): ?><option value="<?= $op ?>" <?= $rol===$op?'selected':'' ?>><?= ucfirst($op) ?></option><?php endforeach; ?></select></div><div class="col-lg-2"><select class="form-select" name="estado"><option value="">Todos los estados</option><?php foreach(['activo','pendiente','suspendido'] as $op): ?><option value="<?= $op ?>" <?= $estado===$op?'selected':'' ?>><?= ucfirst($op) ?></option><?php endforeach; ?></select></div><div class="col-lg-2 d-flex gap-2"><button class="btn btn-warning flex-grow-1">Filtrar</button><a href="usuarios.php" class="btn btn-outline-secondary">Limpiar</a></div></form>
<div class="table-responsive"><table class="table table-hover align-middle"><thead class="table-light"><tr><th>ID</th><th>Nombre</th><th>Correo</th><th>Celular</th><th>Rol</th><th>Estado</th><th>Registro</th></tr></thead><tbody>
<?php if (!$usuarios): ?><tr><td colspan="7" class="text-center text-secondary py-4">No se encontraron usuarios.</td></tr><?php endif; ?>
<?php foreach($usuarios as $u): ?><tr><td><?= (int)$u['id_usuario'] ?></td><td class="fw-semibold"><?= e(trim($u['nombres'].' '.$u['apellidos'])) ?></td><td><?= e($u['correo']) ?></td><td><?= e($u['celular'] ?: '—') ?></td><td><span class="badge badge-role"><?= e(ucfirst($u['rol'])) ?></span></td><td><?= e(ucfirst($u['estado'])) ?></td><td><?= e(date('d/m/Y H:i', strtotime($u['creado_en']))) ?></td></tr><?php endforeach; ?>
</tbody></table></div></div></div></main></body></html>
