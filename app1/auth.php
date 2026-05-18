<?php
session_start();
require_once "conexion.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $usuario = isset($_POST["usuario"]) ? trim($_POST["usuario"]) : "";
    $passwordPlano = isset($_POST["password"]) ? $_POST["password"] : "";

    if ($usuario === "" || $passwordPlano === "") {
        header("Location: login.php?error=2");
        exit();
    }

    $password = sha1($passwordPlano);

    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE usuario = ? AND password = ?");
    $stmt->bind_param("ss", $usuario, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION["usuario"] = $usuario;
        header("Location: dashboard.php");
        exit();
    }

    header("Location: login.php?error=1");
    exit();
}

header("Location: login.php");
exit();
?>
