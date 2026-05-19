<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($usuario === '' || $password === '') {
        header("Location: login.php?error=2");
        exit;
    }

    $password_hashed = sha1($password);

    // 1. Añadimos 'rol' a la consulta
    $stmt = $conn->prepare("SELECT id, rol FROM usuarios WHERE usuario = ? AND password = ?");
    $stmt->bind_param("ss", $usuario, $password_hashed);
    $stmt->execute();

    // 2. Vinculamos las variables de resultado
    $stmt->bind_result($id_usuario, $rol_usuario);
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->fetch(); // Extraemos los datos de la BD

        // 3. Credenciales correctas. Guardamos en variables temporales (Fase 1)
        $_SESSION['pre_auth_user'] = $usuario;
        $_SESSION['pre_auth_rol'] = $rol_usuario;

        // Lo mandamos a que ponga su código del celular
        header("Location: 2fa.php");
        exit;
    } else {
        // Fallo en credenciales
        header("Location: login.php?error=1");
        exit;
    }
    $stmt->close();
} else {
    header("Location: login.php");
    exit;
}
