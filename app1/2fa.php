<?php
session_start();
// Si intentan entrar aquí sin haber pasado el login.php primero
if (!isset($_SESSION['pre_auth_user'])) {
    header("Location: login.php");
    exit;
}
$usuario_actual = $_SESSION['pre_auth_user'];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Verificación 2FA</title>
    <!-- Usa la misma etiqueta <style> que tienes en login.php -->
</head>

<body>
    <main class="card">
        <h1>Verificación de Seguridad</h1>
        <p class="subtitle">Ingresa el código generado por tu aplicación OTP.</p>

        <?php if (isset($_GET['error'])): ?>
            <div class="error">Código OTP inválido o expirado.</div>
        <?php endif; ?>

        <form method="POST" action="verify_otp.php" autocomplete="off">
            <label for="usuario">Usuario</label>
            <input id="usuario" type="text" name="usuario" value="<?php echo htmlspecialchars($usuario_actual); ?>" readonly style="background-color: #e9ecef; cursor: not-allowed;">

            <label for="otp">Código OTP</label>
            <input id="otp" type="text" name="otp" pattern="[0-9]{6}" title="Debe contener 6 dígitos" required autofocus>

            <button type="submit">Verificar y Entrar</button>
        </form>
    </main>
</body>

</html>