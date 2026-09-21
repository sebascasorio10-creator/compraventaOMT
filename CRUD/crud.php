<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
    <title>CRUD MYQLI</title>
</head>
<body>
    <h1 class="bg-warning p-2 text-white text-center"> INFORMACION DE LOS CLIENTES </h1>

    <div class="my-3">
        <a href="forms/AgregarCliente.php" class="btn btn-danger">Agregar clientes</a>
    </div>

    <div class="container bg-light p-3 border border-dark rounded">
        <h2>Lista de clientes</h2>
        <table class="table">
            <thead class="table-dark">
                <tr>
                    <th scope="col">Id</th>
                    <th scope="col">Nombre completo</th>
                    <th scope="col">Telefono</th>
                    <th scope="col">Casa</th>
                    <th scope="col">Fecha de la cita</th>
                    <th scope="col">Hora</th>
                    <th scope="col">comentario</th>
                </tr>
            </thead>
            <tbody>
                <?php
                include("../config/conexion.php");

                $sql = "SELECT * FROM crud";
                $query = mysqli_query($conexion, $sql);

                while ($fila = mysqli_fetch_array($query)) {
                    ?>
                    <tr>
                        <th><?php echo $fila['id']; ?></td>
                        <th><?php echo $fila['nombre']; ?></td>
                        <th><?php echo $fila['telefono']; ?></td>
                        <th><?php echo $fila['casa']; ?></td>
                        <th><?php echo $fila['fecha_cita']; ?></td>
                        <th><?php echo $fila['hora']; ?></td>
                        <th><?php echo $fila['comentario']; ?></td>
                        <th>
                            <a href="forms/EditarDato.php?id=<?php echo $fila['id']; ?>" class="btn btn-warning">Editar</a>
                            <a href="crud/eliminarDato.php?id=<?php echo $fila['id']; ?>" class="btn btn-danger">Eliminar Datos</a>
                        </th>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>