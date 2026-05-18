<?php
require 'conexion.php';

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = trim($_POST['usuario']);
    $password = trim($_POST['password']);

    if (!empty($usuario) && !empty($password)) {
        // 1. Verificar si el usuario ya existe en la base de datos
        $stmt_check = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
        $stmt_check->bind_param("s", $usuario);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $mensaje = "El usuario ya existe. Por favor, elige otro alias.";
            $tipo_mensaje = "error";
        } else {
            // 2. Cifrar la contraseña usando SHA1 (para mantener compatibilidad con la BD actual)
            $password_hashed = sha1($password);

            // 3. Insertar el nuevo usuario
            $stmt_insert = $conn->prepare("INSERT INTO usuarios (usuario, password) VALUES (?, ?)");
            $stmt_insert->bind_param("ss", $usuario, $password_hashed);

            if ($stmt_insert->execute()) {
                $mensaje = "Usuario registrado con éxito. <a href='login.php' style='color: var(--accent); font-weight: bold;'>Ir al Login</a>";
                $tipo_mensaje = "success";
            } else {
                $mensaje = "Error al registrar el usuario: " . $conn->error;
                $tipo_mensaje = "error";
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    } else {
        $mensaje = "Por favor, completa todos los campos.";
        $tipo_mensaje = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario</title>
    <style>
        :root {
            --bg: #f3f7fb;
            --card: #ffffff;
            --text: #1b2a41;
            --muted: #5f6c80;
            --accent: #0f766e;
            --accent-dark: #115e59;
            --error-bg: #fee2e2;
            --error-text: #991b1b;
            --success-bg: #d1fae5;
            --success-text: #065f46;
            --border: #d9e2ec;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text);
            background: radial-gradient(circle at top right, #dff3ef 0%, var(--bg) 45%);
            display: grid;
            place-items: center;
            padding: 20px;
        }

        .card {
            width: 100%;
            max-width: 420px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 12px 30px rgba(27, 42, 65, 0.1);
        }

        h1 {
            margin: 0 0 8px;
            font-size: 1.6rem;
        }

        .subtitle {
            margin: 0 0 20px;
            color: var(--muted);
            font-size: 0.95rem;
        }

        .alert {
            margin-bottom: 14px;
            padding: 10px 12px;
            border-radius: 10px;
            font-size: 0.92rem;
        }

        .error {
            background: var(--error-bg);
            color: var(--error-text);
        }

        .success {
            background: var(--success-bg);
            color: var(--success-text);
        }

        label {
            display: block;
            margin: 12px 0 6px;
            font-weight: 600;
            font-size: 0.92rem;
        }

        input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 0.95rem;
            outline: none;
            transition: border-color 0.2s ease;
        }

        input:focus {
            border-color: var(--accent);
        }

        button {
            width: 100%;
            margin-top: 18px;
            border: none;
            border-radius: 10px;
            padding: 11px;
            background: var(--accent);
            color: #fff;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        button:hover {
            background: var(--accent-dark);
        }

        .login-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            font-size: 0.9rem;
            color: var(--muted);
            text-decoration: none;
        }

        .login-link:hover {
            color: var(--accent);
            text-decoration: underline;
        }
    </style>
</head>

<body>

    <main class="card">
        <h1>Registrar Usuario</h1>
        <p class="subtitle">Añade un nuevo usuario a la base de datos</p>

        <?php if (!empty($mensaje)): ?>
            <div class="alert <?php echo $tipo_mensaje; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php" autocomplete="off">
            <label for="usuario">Usuario</label>
            <input id="usuario" type="text" name="usuario" required>

            <label for="password">Contraseña</label>
            <input id="password" type="password" name="password" required>

            <button type="submit">Registrar</button>
        </form>

        <a href="login.php" class="login-link">¿Ya tienes cuenta? Inicia sesión aquí</a>
    </main>

</body>

</html>