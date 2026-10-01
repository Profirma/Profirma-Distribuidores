<?php
session_start();

if (isset($_SESSION['distribuidor_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profirma Distribuidores</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            color: #1f2937;
        }

        .contenedor {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .card {
            width: 100%;
            max-width: 450px;
            background: white;
            padding: 40px;
            border-radius: 18px;
            box-shadow: 0 10px 35px rgba(0,0,0,.08);
            text-align: center;
        }

        h1 {
            margin-bottom: 10px;
        }

        p {
            color: #6b7280;
        }

        .boton {
            display: inline-block;
            margin-top: 20px;
            padding: 14px 25px;
            background: #111827;
            color: white;
            text-decoration: none;
            border-radius: 10px;
        }

        .boton:hover {
            background: #374151;
        }
    </style>
</head>

<body>

<div class="contenedor">
    <div class="card">

        <h1>Profirma</h1>

        <p>
            Portal exclusivo para distribuidores
        </p>

        <a class="boton" href="login.php">
            Iniciar sesión
        </a>

    </div>
</div>

</body>
</html>
