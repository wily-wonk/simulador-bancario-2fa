<?php
session_start();

// Verificar si existe la sesión de usuario
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #f3f7fb;
            color: #1b2a41;
            display: grid;
            place-items: center;
            padding: 20px;
        }

        .panel {
            background: #fff;
            border: 1px solid #d9e2ec;
            border-radius: 14px;
            padding: 26px;
            max-width: 520px;
            width: 100%;
            text-align: center;
            box-shadow: 0 12px 30px rgba(27, 42, 65, 0.08);
        }

        a {
            display: inline-block;
            margin-top: 16px;
            text-decoration: none;
            color: #0f766e;
            font-weight: 700;
        }

        .admin-link {
            color: #991b1b;
            /* Color distinto para resaltar que es zona admin */
        }
    </style>
</head>

<body>
    <section class="panel">
        <h1>Bienvenido, <?php echo htmlspecialchars($_SESSION["usuario"]); ?></h1>
        <p>Rol actual: <strong><?php echo htmlspecialchars($_SESSION["rol"] ?? 'Desconocido'); ?></strong></p>

        <!-- Lógica de control de acceso a nivel de vista -->
        <?php if (isset($_SESSION["rol"]) && $_SESSION["rol"] === 'admin'): ?>
            <a href="usuarios.php" class="admin-link"> Gestionar usuarios (ABM)</a>
            <br>
            <a href="auditoria.php" class="admin-link"> Ver registros de auditoría</a>
            <br>
        <?php endif; ?>

        <a href="transferencias.php"> Módulo de transferencias bancarias</a>
        <br>
        <a href="logout.php" style="color: #5f6c80;">Cerrar sesión</a>
    </section>
</body>

</html>