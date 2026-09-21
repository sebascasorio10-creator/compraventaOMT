<?php
include_once("../../config/conexion.php");

// Validar que el ID venga por GET o REQUEST
$id = $_REQUEST['id'] ?? null;

if ($id === null) {
    die("No se especificó el ID.");
}

if (!is_numeric($id)) {
    die("ID inválido.");
}

// Preparar consulta segura
$sql = "SELECT * FROM crud WHERE id = ?";
$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$query = mysqli_stmt_get_result($stmt);

if (!$fila = mysqli_fetch_array($query)) {
    die("No se encontró el cliente con ID $id");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Editar Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous" />
</head>
<body>
    <h1 class="bg-warning p-2 text-white text-center">Editar Cliente</h1>
    <div class="container">
        <form action="../crud/editar.php" method="POST">

            <input type="hidden" name="id" value="<?php echo htmlspecialchars($fila['id']); ?>">

            <div class="mb-3">
                <label class="form-label">Nombre</label>
                <input type="text" class="form-control" name="nombre" value="<?php echo htmlspecialchars($fila['nombre']); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Teléfono</label>
                <input type="text" class="form-control" name="telefono" value="<?php echo htmlspecialchars($fila['telefono']); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Casa</label>
                <input type="text" class="form-control" name="casa" value="<?php echo htmlspecialchars($fila['casa']); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Fecha de la cita</label>
                <input type="date" class="form-control" name="fecha_cita" value="<?php echo htmlspecialchars($fila['fecha_cita']); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Hora de la cita</label>
                <input type="time" class="form-control" name="hora" value="<?php echo htmlspecialchars($fila['hora']); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Comentario</label>
                <input type="text" class="form-control" name="comentario" value="<?php echo htmlspecialchars($fila['comentario']); ?>">
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-primary">Editar Cliente</button>
                <a href="../index2.php" class="btn btn-dark">Regresar</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
</body>
</html>