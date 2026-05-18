<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "login_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Error de conexion: " . $conn->connect_error);
}

$conn->set_charset("utf8");

// Función global para registrar eventos forenses
function registrarAuditoria($conn, $modulo, $accion, $detalles)
{
    // Capturamos el contexto de la aplicación
    $usuario = $_SESSION['usuario'] ?? 'Sistema';
    $rol = $_SESSION['rol'] ?? 'N/A';

    // Capturamos la IP real del usuario
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }

    $stmt = $conn->prepare("INSERT INTO auditoria_logs (usuario, rol, ip, modulo, accion, detalles) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $usuario, $rol, $ip, $modulo, $accion, $detalles);
    $stmt->execute();
    $stmt->close();
}
