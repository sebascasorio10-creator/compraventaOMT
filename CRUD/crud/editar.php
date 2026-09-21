<?php
include_once(__DIR__ . "/../../config/conexion.php");

// Recibir datos POST y sanitizar (puedes mejorar sanitización si quieres)
$id = $_POST['id'] ?? null;
$nombre = $_POST['nombre'] ?? '';
$telefono = $_POST['telefono'] ?? '';
$casa = $_POST['casa'] ?? '';
$fecha_cita = $_POST['fecha_cita'] ?? '';
$hora = $_POST['hora'] ?? '';
$comentario = $_POST['comentario'] ?? '';

if ($id === null || !is_numeric($id)) {
    die("ID inválido");
}

// Preparar consulta segura UPDATE
$sql = "UPDATE crud SET nombre=?, telefono=?, casa=?, fecha_cita=?, hora=?, comentario=? WHERE id=?";
$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, "ssssssi", $nombre, $telefono, $casa, $fecha_cita, $hora, $comentario, $id);

if (mysqli_stmt_execute($stmt)) {
    header('Location: ../crud.php');
    exit;
} else {
    echo "Error al actualizar: " . mysqli_error($conexion);
}
?>