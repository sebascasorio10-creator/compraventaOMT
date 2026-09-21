<?php
    include_once('../../config/Conexion.php');

    $id = $_REQUEST['id'];
    $sql = "DELETE FROM crud WHERE id = '$id'";
    $query = mysqli_query($conexion, $sql);

    if ($query) {
        header('location: ../crud.php');
    }
?>