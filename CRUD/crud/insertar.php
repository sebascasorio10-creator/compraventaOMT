<?php
include_once("../../config/conexion.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $telefono = $_POST['telefono'];
    $casa = $_POST['casa'];
    $fecha_cita = $_POST['fecha_cita'];
    $hora = $_POST['hora'];
    $comentario = $_POST['comentario'];

    $sql = "INSERT INTO crud (nombre, telefono, casa, fecha_cita, hora, comentario)
            VALUES ('$nombre', '$telefono', '$casa', '$fecha_cita', '$hora', '$comentario')";

  if (mysqli_query($conexion, $sql)) {
      echo "<script>
              alert('✅ Cita agendada con éxito, pronto nos pondremos en contacto contigo');
              setTimeout(function(){
                window.location.href = '../../inmobiliaria_t/inicio.php';
              }, 500);
            </script>";
  } else {
      echo "<script>
              alert('❌ Error al registrar la cita');
             window.history.back();
           </script>";
}

}
?>