<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['usuario'] ?? '';
    $otp = $_POST['otp'] ?? '';

    // Prevención de Bypass
    if (!isset($_SESSION['pre_auth_user']) || $user !== $_SESSION['pre_auth_user']) {
        header("Location: login.php");
        exit;
    }

    $exe_path = realpath(__DIR__ . '/../multiotp_5.10.2.2/windows/multiotp.exe');
    $command = escapeshellarg($exe_path) . ' ' . escapeshellarg($user) . ' ' . escapeshellarg($otp);

    $output = [];
    $exitCode = 1;
    exec($command . ' 2>&1', $output, $exitCode);

    if ($exitCode === 0) {
        // ¡Doble factor superado! Damos acceso total
        $_SESSION['autenticado'] = true;
        $_SESSION['usuario'] = $user;

        // TRASPASO DEL ROL: De la sesión temporal a la definitiva
        $_SESSION['rol'] = $_SESSION['pre_auth_rol'];

        // Limpiamos las variables temporales por seguridad
        unset($_SESSION['pre_auth_user']);
        unset($_SESSION['pre_auth_rol']);

        header("Location: dashboard.php");
        exit;
    } else {
        // Falló el OTP
        header("Location: 2fa.php?error=1");
        exit;
    }
}
