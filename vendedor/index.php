<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (empty($_SESSION['usuario']) || ($_SESSION['usuario']['rol'] ?? '') !== 'vendedor') {
    header('Location: ../login.php');
    exit;
}

$idVendedor = (int) $_SESSION['usuario']['id'];
$tiendaStmt = $pdo->prepare('SELECT nombre_tienda, ciudad, estado_verificacion FROM tiendas WHERE id_vendedor = ? LIMIT 1');
$tiendaStmt->execute([$idVendedor]);
$tienda = $tiendaStmt->fetch();
if (!$tienda) {
    http_response_code(403);
    exit('No se encontró una tienda asociada a esta cuenta de vendedor.');
}

function volverPanel(): void {
    header('Location: index.php');
    exit;
}

function guardarImagen(?array $archivo): ?string {
    if (!$archivo || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    if ($archivo['error'] !== UPLOAD_ERR_OK || $archivo['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('La imagen supera 5 MB o no se pudo cargar.');
    }
    $info = new finfo(FILEINFO_MIME_TYPE);
    $mime = $info->file($archivo['tmp_name']);
    $extensiones = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensiones[$mime]) || @getimagesize($archivo['tmp_name']) === false) {
        throw new RuntimeException('Usa una imagen JPG, PNG o WEBP válida.');
    }
    $directorio = __DIR__ . '/../uploads/productos';
    if (!is_dir($directorio) && !mkdir($directorio, 0755, true) && !is_dir($directorio)) {
        throw new RuntimeException('No fue posible preparar la carpeta de imágenes.');
    }
    $nombre = bin2hex(random_bytes(16)) . '.' . $extensiones[$mime];
    if (!move_uploaded_file($archivo['tmp_name'], $directorio . '/' . $nombre)) {
        throw new RuntimeException('No fue posible guardar la imagen.');
    }
    return 'uploads/productos/' . $nombre;
}

$error = $_SESSION['flash_error'] ?? '';
$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validar_csrf();
    $accion = $_POST['accion'] ?? '';
    try {
        if ($accion === 'guardar') {
            $idProducto = filter_var($_POST['id_producto'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
            $nombre = trim($_POST['nombre'] ?? '');
            $descripcion = trim($_POST['descripcion'] ?? '');
            $precio = filter_var($_POST['precio'] ?? null, FILTER_VALIDATE_FLOAT);
            $cantidad = filter_var($_POST['cantidad_disponible'] ?? null, FILTER_VALIDATE_INT);
            $categoria = filter_var($_POST['id_categoria'] ?? null, FILTER_VALIDATE_INT);
            $condicion = $_POST['condicion'] ?? 'nuevo';
            $ciudad = trim($_POST['ciudad'] ?? '');
            $estado = $_POST['estado'] ?? 'publicado';
            if ($nombre === '' || mb_strlen($nombre) > 150 || $descripcion === '' || $precio === false || $precio <= 0 || $cantidad === false || $cantidad < 0 || !$categoria) {
                throw new RuntimeException('Revisa el nombre, descripción, categoría, precio y cantidad.');
            }
            if (!in_array($condicion, ['nuevo', 'usado', 'reacondicionado'], true) || !in_array($estado, ['borrador', 'publicado'], true)) {
                throw new RuntimeException('Los datos seleccionados no son válidos.');
            }
            $cat = $pdo->prepare('SELECT COUNT(*) FROM categorias WHERE id_categoria = ? AND activa = 1');
            $cat->execute([$categoria]);
            if (!(int)$cat->fetchColumn()) throw new RuntimeException('Selecciona una categoría disponible.');

            $imagen = guardarImagen($_FILES['imagen'] ?? null);
            if ($idProducto) {
                $propio = $pdo->prepare('SELECT id_producto FROM productos WHERE id_producto = ? AND id_vendedor = ? AND estado <> "eliminado"');
                $propio->execute([$idProducto, $idVendedor]);
                if (!$propio->fetchColumn()) throw new RuntimeException('No puedes modificar este producto.');
                $stmt = $pdo->prepare('UPDATE productos SET id_categoria=?, nombre=?, descripcion=?, precio=?, cantidad_disponible=?, condicion=?, ciudad=?, estado=? WHERE id_producto=? AND id_vendedor=?');
                $stmt->execute([$categoria, $nombre, $descripcion, $precio, $cantidad, $condicion, $ciudad, $estado, $idProducto, $idVendedor]);
                if ($imagen) {
                    $img = $pdo->prepare('SELECT id_imagen, ruta_imagen FROM producto_imagenes WHERE id_producto=? ORDER BY es_principal DESC, orden ASC LIMIT 1');
                    $img->execute([$idProducto]);
                    $anterior = $img->fetch();
                    if ($anterior) {
                        $pdo->prepare('UPDATE producto_imagenes SET ruta_imagen=?, es_principal=1 WHERE id_imagen=?')->execute([$imagen, $anterior['id_imagen']]);
                        $rutaAnterior = realpath(__DIR__ . '/../' . $anterior['ruta_imagen']);
                        $baseUploads = realpath(__DIR__ . '/../uploads/productos');
                        if ($rutaAnterior && $baseUploads && str_starts_with($rutaAnterior, $baseUploads . DIRECTORY_SEPARATOR)) @unlink($rutaAnterior);
                    } else {
                        $pdo->prepare('INSERT INTO producto_imagenes (id_producto,ruta_imagen,es_principal) VALUES (?,?,1)')->execute([$idProducto, $imagen]);
                    }
                }
                $_SESSION['flash_success'] = 'Producto actualizado correctamente.';
            } else {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('INSERT INTO productos (id_vendedor,id_categoria,nombre,descripcion,precio,cantidad_disponible,condicion,ciudad,estado) VALUES (?,?,?,?,?,?,?,?,?)');
                $stmt->execute([$idVendedor, $categoria, $nombre, $descripcion, $precio, $cantidad, $condicion, $ciudad, $estado]);
                $nuevoId = (int)$pdo->lastInsertId();
                if ($imagen) $pdo->prepare('INSERT INTO producto_imagenes (id_producto,ruta_imagen,es_principal) VALUES (?,?,1)')->execute([$nuevoId, $imagen]);
                $pdo->commit();
                $_SESSION['flash_success'] = 'Producto creado correctamente.';
            }
            volverPanel();
        } elseif ($accion === 'estado') {
            $idProducto = (int)($_POST['id_producto'] ?? 0);
            $nuevoEstado = $_POST['estado'] ?? '';
            if (!in_array($nuevoEstado, ['publicado','pausado','borrador'], true)) throw new RuntimeException('Estado no válido.');
            $stmt = $pdo->prepare('UPDATE productos SET estado=? WHERE id_producto=? AND id_vendedor=? AND estado NOT IN ("vendido","eliminado")');
            $stmt->execute([$nuevoEstado, $idProducto, $idVendedor]);
            $_SESSION['flash_success'] = 'Estado del producto actualizado.';
            volverPanel();
        } elseif ($accion === 'eliminar') {
            $stmt = $pdo->prepare('UPDATE productos SET estado="eliminado" WHERE id_producto=? AND id_vendedor=? AND estado NOT IN ("eliminado","vendido")');
            $stmt->execute([(int)($_POST['id_producto'] ?? 0), $idVendedor]);
            $_SESSION['flash_success'] = 'Producto retirado de tus publicaciones.';
            volverPanel();
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['flash_error'] = $e instanceof RuntimeException ? $e->getMessage() : 'No se pudo completar la operación. Inténtalo nuevamente.';
        volverPanel();
    }
}

$categorias = $pdo->query('SELECT id_categoria, nombre FROM categorias WHERE activa=1 ORDER BY nombre')->fetchAll();
$vista = $_GET['vista'] ?? 'inicio';
$idEditar = filter_var($_GET['editar'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
$productoEditar = null;
if ($idEditar) {
    $stmt = $pdo->prepare('SELECT p.*, (SELECT pi.ruta_imagen FROM producto_imagenes pi WHERE pi.id_producto=p.id_producto ORDER BY pi.es_principal DESC, pi.orden ASC LIMIT 1) AS imagen FROM productos p WHERE p.id_producto=? AND p.id_vendedor=? AND p.estado <> "eliminado"');
    $stmt->execute([$idEditar, $idVendedor]);
    $productoEditar = $stmt->fetch();
    if (!$productoEditar) { $_SESSION['flash_error'] = 'No se encontró el producto solicitado.'; volverPanel(); }
}
$statsStmt = $pdo->prepare("SELECT COUNT(*) AS total, SUM(estado='publicado') AS publicados, SUM(estado='borrador') AS borradores, SUM(estado='pausado') AS pausados FROM productos WHERE id_vendedor=? AND estado <> 'eliminado'");
$statsStmt->execute([$idVendedor]);
$stats = $statsStmt->fetch();
$productosStmt = $pdo->prepare('SELECT p.*, c.nombre AS categoria, (SELECT pi.ruta_imagen FROM producto_imagenes pi WHERE pi.id_producto=p.id_producto ORDER BY pi.es_principal DESC, pi.orden ASC LIMIT 1) AS imagen FROM productos p INNER JOIN categorias c ON c.id_categoria=p.id_categoria WHERE p.id_vendedor=? AND p.estado <> "eliminado" ORDER BY p.creado_en DESC');
$productosStmt->execute([$idVendedor]);
$productos = $productosStmt->fetchAll();
$ventasStmt = $pdo->prepare('SELECT pd.id_detalle, pd.nombre_producto, pd.cantidad, pd.subtotal, pe.id_pedido, pe.fecha_pedido, pe.estado FROM pedido_detalles pd INNER JOIN pedidos pe ON pe.id_pedido=pd.id_pedido WHERE pd.id_vendedor=? ORDER BY pe.fecha_pedido DESC LIMIT 100');
$ventasStmt->execute([$idVendedor]);
$ventas = $ventasStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Panel vendedor | CompraVenta</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
body{background:#f5f6f8;color:#292929}.navbar-brand{font-weight:800;letter-spacing:.3px}.navbar-brand span{color:#f0ad00}.seller-layout{min-height:calc(100vh - 62px)}.seller-sidebar{background:#fff;border-right:1px solid #e8e8e8}.seller-sidebar .nav-link{color:#444;border-radius:8px;padding:11px 13px}.seller-sidebar .nav-link.active,.seller-sidebar .nav-link:hover{background:#fff3cd;color:#765400}.seller-main{padding:30px}.stat-card,.content-card{background:#fff;border:1px solid #ececec;border-radius:12px;padding:20px;height:100%}.stat-number{font-size:28px;font-weight:750}.muted{color:#777}.product-thumb{width:64px;height:64px;object-fit:cover;border-radius:8px;background:#f1f1f1}.table td{vertical-align:middle}.btn-warning{font-weight:600}.form-label{font-weight:600}@media(max-width:767px){.seller-main{padding:18px}.seller-sidebar{border-right:0;border-bottom:1px solid #eee}.seller-sidebar .nav{flex-direction:row;flex-wrap:wrap}.seller-sidebar .nav-link{padding:8px}}
</style></head><body>
<nav class="navbar navbar-expand-lg bg-white border-bottom px-3 px-lg-4"><a class="navbar-brand" href="index.php">COMPRA<span>VENTA</span> <small class="text-secondary fw-normal">/ Vendedor</small></a><div class="ms-auto d-flex align-items-center gap-3"><span class="small text-secondary d-none d-sm-inline"><?= e($_SESSION['usuario']['nombre']) ?></span><a class="btn btn-outline-dark btn-sm" href="../auth/logout.php">Cerrar sesión</a></div></nav>
<div class="container-fluid seller-layout"><div class="row min-vh-100">
<aside class="col-md-3 col-lg-2 seller-sidebar p-3"><div class="mb-3"><div class="fw-bold"><?= e($tienda['nombre_tienda']) ?></div><small class="text-secondary"><?= e($tienda['ciudad'] ?? '') ?></small></div><nav class="nav nav-pills flex-column gap-1">
<a class="nav-link <?= $vista==='inicio'?'active':'' ?>" href="index.php"><i class="bi bi-grid me-2"></i>Panel principal</a>
<a class="nav-link <?= $vista==='productos'?'active':'' ?>" href="index.php?vista=productos"><i class="bi bi-box-seam me-2"></i>Mis productos</a>
<a class="nav-link <?= $vista==='nuevo'?'active':'' ?>" href="index.php?vista=nuevo"><i class="bi bi-plus-circle me-2"></i>Publicar producto</a>
<a class="nav-link <?= $vista==='ventas'?'active':'' ?>" href="index.php?vista=ventas"><i class="bi bi-receipt me-2"></i>Ventas</a>
</nav><hr><div class="small text-secondary">Estado de la tienda</div><span class="badge <?= $tienda['estado_verificacion']==='verificada'?'text-bg-success':'text-bg-warning' ?>"><?= e(ucfirst($tienda['estado_verificacion'])) ?></span></aside>
<main class="col-md-9 col-lg-10 seller-main">
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
<?php if ($vista==='nuevo' || $idEditar): ?>
<div class="d-flex justify-content-between align-items-center mb-4"><div><h3 class="fw-bold mb-1"><?= $idEditar?'Editar producto':'Publicar producto' ?></h3><p class="muted mb-0">Completa la información de tu publicación.</p></div><a href="index.php?vista=productos" class="btn btn-outline-secondary">Volver</a></div>
<form class="content-card" method="post" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="accion" value="guardar"><input type="hidden" name="id_producto" value="<?= (int)($productoEditar['id_producto']??0) ?>">
<div class="row g-3"><div class="col-md-8"><label class="form-label">Nombre del producto *</label><input class="form-control" name="nombre" maxlength="150" required value="<?= e($productoEditar['nombre']??'') ?>"></div>
<div class="col-md-4"><label class="form-label">Categoría *</label><select class="form-select" name="id_categoria" required><option value="">Selecciona</option><?php foreach($categorias as $cat): ?><option value="<?= (int)$cat['id_categoria'] ?>" <?= (int)($productoEditar['id_categoria']??0)===(int)$cat['id_categoria']?'selected':'' ?>><?= e($cat['nombre']) ?></option><?php endforeach; ?></select></div>
<div class="col-12"><label class="form-label">Descripción *</label><textarea class="form-control" name="descripcion" rows="4" required><?= e($productoEditar['descripcion']??'') ?></textarea></div>
<div class="col-md-4"><label class="form-label">Precio (COP) *</label><input class="form-control" type="number" name="precio" min="1" step="0.01" required value="<?= e($productoEditar['precio']??'') ?>"></div>
<div class="col-md-4"><label class="form-label">Cantidad disponible *</label><input class="form-control" type="number" name="cantidad_disponible" min="0" step="1" required value="<?= e($productoEditar['cantidad_disponible']??1) ?>"></div>
<div class="col-md-4"><label class="form-label">Condición</label><select class="form-select" name="condicion"><?php foreach(['nuevo'=>'Nuevo','usado'=>'Usado','reacondicionado'=>'Reacondicionado'] as $v=>$label): ?><option value="<?= $v ?>" <?= ($productoEditar['condicion']??'nuevo')===$v?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></div>
<div class="col-md-6"><label class="form-label">Ciudad</label><input class="form-control" name="ciudad" maxlength="100" value="<?= e($productoEditar['ciudad']??($tienda['ciudad']??'')) ?>"></div>
<div class="col-md-6"><label class="form-label">Estado de publicación</label><select class="form-select" name="estado"><option value="publicado" <?= ($productoEditar['estado']??'publicado')==='publicado'?'selected':'' ?>>Publicado</option><option value="borrador" <?= ($productoEditar['estado']??'')==='borrador'?'selected':'' ?>>Borrador</option></select></div>
<div class="col-12"><label class="form-label">Imagen del producto (JPG, PNG o WEBP; máximo 5 MB)</label><input class="form-control" type="file" name="imagen" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"><?php if(!empty($productoEditar['imagen'])): ?><div class="mt-2"><img src="../<?= e($productoEditar['imagen']) ?>" alt="Imagen actual" class="product-thumb"></div><?php endif; ?></div>
<div class="col-12"><button class="btn btn-warning px-4" type="submit"><?= $idEditar?'Guardar cambios':'Publicar producto' ?></button></div></div></form>
<?php elseif ($vista==='ventas'): ?>
<h3 class="fw-bold mb-1">Ventas</h3><p class="muted mb-4">Pedidos que incluyen productos de tu tienda.</p><div class="content-card"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Pedido</th><th>Fecha</th><th>Producto</th><th>Cantidad</th><th>Subtotal</th><th>Estado</th></tr></thead><tbody><?php foreach($ventas as $v): ?><tr><td>#<?= (int)$v['id_pedido'] ?></td><td><?= e(date('d/m/Y',strtotime($v['fecha_pedido']))) ?></td><td><?= e($v['nombre_producto']) ?></td><td><?= (int)$v['cantidad'] ?></td><td>$<?= number_format((float)$v['subtotal'],0,',','.') ?></td><td><span class="badge text-bg-light"><?= e(ucfirst($v['estado'])) ?></span></td></tr><?php endforeach; ?><?php if(!$ventas): ?><tr><td colspan="6" class="text-center muted py-4">Todavía no tienes ventas registradas.</td></tr><?php endif; ?></tbody></table></div></div>
<?php elseif ($vista==='productos'): ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div><h3 class="fw-bold mb-1">Mis productos</h3><p class="muted mb-0">Administra tus publicaciones.</p></div><a href="index.php?vista=nuevo" class="btn btn-warning"><i class="bi bi-plus-lg me-1"></i>Publicar producto</a></div>
<div class="content-card"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Producto</th><th>Precio</th><th>Stock</th><th>Estado</th><th>Acciones</th></tr></thead><tbody><?php foreach($productos as $p): ?><tr><td><div class="d-flex align-items-center gap-3"><?php if($p['imagen']): ?><img class="product-thumb" src="../<?= e($p['imagen']) ?>" alt=""><?php else: ?><div class="product-thumb d-flex align-items-center justify-content-center"><i class="bi bi-image text-secondary fs-4"></i></div><?php endif; ?><div><div class="fw-semibold"><?= e($p['nombre']) ?></div><small class="muted"><?= e($p['categoria']) ?></small></div></div></td><td>$<?= number_format((float)$p['precio'],0,',','.') ?></td><td><?= (int)$p['cantidad_disponible'] ?></td><td><span class="badge <?= $p['estado']==='publicado'?'text-bg-success':($p['estado']==='pausado'?'text-bg-secondary':'text-bg-warning') ?>"><?= e(ucfirst($p['estado'])) ?></span></td><td><div class="d-flex flex-wrap gap-1"><a class="btn btn-sm btn-outline-dark" href="index.php?editar=<?= (int)$p['id_producto'] ?>">Editar</a><?php if(!in_array($p['estado'],['vendido','eliminado'],true)): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="accion" value="estado"><input type="hidden" name="id_producto" value="<?= (int)$p['id_producto'] ?>"><input type="hidden" name="estado" value="<?= $p['estado']==='publicado'?'pausado':'publicado' ?>"><button class="btn btn-sm btn-outline-warning" type="submit"><?= $p['estado']==='publicado'?'Pausar':'Publicar' ?></button></form><form method="post" onsubmit="return confirm('¿Retirar este producto de tus publicaciones?')"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id_producto" value="<?= (int)$p['id_producto'] ?>"><button class="btn btn-sm btn-outline-danger" type="submit">Retirar</button></form><?php endif; ?></div></td></tr><?php endforeach; ?><?php if(!$productos): ?><tr><td colspan="5" class="text-center muted py-4">Aún no has publicado productos.</td></tr><?php endif; ?></tbody></table></div></div>
<?php else: ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div><h3 class="fw-bold mb-1">Hola, <?= e(explode(' ',trim($_SESSION['usuario']['nombre']))[0]) ?></h3><p class="muted mb-0">Este es el resumen de tu tienda.</p></div><a href="index.php?vista=nuevo" class="btn btn-warning"><i class="bi bi-plus-lg me-1"></i>Publicar producto</a></div>
<div class="row g-3 mb-4"><div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="muted">Productos activos y guardados</div><div class="stat-number"><?= (int)$stats['total'] ?></div></div></div><div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="muted">Publicados</div><div class="stat-number"><?= (int)$stats['publicados'] ?></div></div></div><div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="muted">Borradores</div><div class="stat-number"><?= (int)$stats['borradores'] ?></div></div></div><div class="col-sm-6 col-xl-3"><div class="stat-card"><div class="muted">Pausados</div><div class="stat-number"><?= (int)$stats['pausados'] ?></div></div></div></div>
<div class="content-card"><div class="d-flex justify-content-between align-items-center mb-3"><h5 class="fw-bold mb-0">Tus publicaciones recientes</h5><a href="index.php?vista=productos" class="text-decoration-none">Ver todos</a></div><?php if(!$productos): ?><p class="muted mb-0">Todavía no tienes productos. Publica el primero para comenzar.</p><?php else: ?><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Producto</th><th>Precio</th><th>Estado</th><th></th></tr></thead><tbody><?php foreach(array_slice($productos,0,5) as $p): ?><tr><td><?= e($p['nombre']) ?></td><td>$<?= number_format((float)$p['precio'],0,',','.') ?></td><td><?= e(ucfirst($p['estado'])) ?></td><td><a href="index.php?editar=<?= (int)$p['id_producto'] ?>" class="btn btn-sm btn-outline-dark">Editar</a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div>
<?php endif; ?>
</main></div></div></body></html>