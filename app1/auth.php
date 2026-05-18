<?php
session_start();
require 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($usuario === '' || $password === '') {
        header("Location: login.php?error=2");
        exit;
    }

    $password_hashed = sha1($password);

    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ? AND password = ?");
    $stmt->bind_param("ss", $usuario, $password_hashed);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Credenciales correctas. Pasamos a la Fase 2 (OTP)
        $_SESSION['pre_auth_user'] = $usuario;
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
