<?php
require 'conexion.php';

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = trim($_POST['usuario']);
    $password = trim($_POST['password']);

    if (!empty($usuario) && !empty($password)) {
        // 1. Verificar si el usuario ya existe
        $stmt_check = $conn->prepare("SELECT id FROM usuarios WHERE usuario = ?");
        $stmt_check->bind_param("s", $usuario);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $mensaje = "El usuario ya existe en la base de datos.";
            $tipo_mensaje = "error";
        } else {
            // 2. Insertar en BD
            $password_hashed = sha1($password);
            $stmt_insert = $conn->prepare("INSERT INTO usuarios (usuario, password) VALUES (?, ?)");
            $stmt_insert->bind_param("ss", $usuario, $password_hashed);

            if ($stmt_insert->execute()) {
                // --- INICIO INTEGRACIÓN MULTIOTP ---
                $app_dir = __DIR__;
                $multiotp_dir = realpath($app_dir . '/../multiotp_5.10.2.2/windows');

                $qr_dir = $app_dir . '/qrcodes';
                if (!is_dir($qr_dir)) {
                    mkdir($qr_dir, 0777, true);
                }

                $qr_filename = "qr_" . $usuario . ".png";
                $qr_path = $qr_dir . '/' . $qr_filename;

                if ($multiotp_dir) {
                    chdir($multiotp_dir);

                    $cmd_create = "multiotp.exe -fastcreatenopin " . escapeshellarg($usuario);
                    exec($cmd_create);

                    $cmd_qr = "multiotp.exe -qrcode " . escapeshellarg($usuario) . " " . escapeshellarg($qr_path);
                    exec($cmd_qr);

                    chdir($app_dir);

                    if (file_exists($qr_path)) {
                        $mensaje = "¡Registro exitoso! <strong>Escanea este código QR</strong> en tu aplicación (FreeOTP, Google Auth, etc).<br>";
                        $mensaje .= "<img src='qrcodes/" . $qr_filename . "' style='margin-top:15px; border-radius:8px; border: 1px solid #ccc; max-width: 250px;'><br><br>";
                        $mensaje .= "<a href='login.php' style='display:inline-block; padding:8px 15px; background:var(--accent); color:white; text-decoration:none; border-radius:5px;'>Ir al Login</a>";
                        $tipo_mensaje = "success";
                    } else {
                        $mensaje = "Usuario guardado en BD, pero falló la generación del QR en multiOTP.";
                        $tipo_mensaje = "error";
                    }
                } else {
                    $mensaje = "No se encontró el directorio de multiOTP. Revisa las carpetas.";
                    $tipo_mensaje = "error";
                }
                // --- FIN INTEGRACIÓN MULTIOTP ---
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
    <title>Registro de Usuario - 2FA</title>
    <style>
        :root {
            --bg-start: #0B1120;
            --bg-end: #000000;
            --card-bg: rgba(15, 23, 42, 0.72);
            --card-border: rgba(56, 189, 248, 0.12);
            --muted: #94a3b8;
            --text: #e2e8f0;
            --title: #ffffff;
            --accent: #0ea5e9;
            --accent-2: #0d9488;
            --danger: rgba(248, 113, 113, 0.12);
            --danger-text: #f87171;
            --success: rgba(34, 197, 94, 0.12);
            --success-text: #86efac;
            --input-bg: #111827;
            --glass-blur: 10px;
        }

        * {
            box-sizing: border-box
        }

        html,
        body {
            height: 100%;
            margin: 0
        }

        body {
            font-family: Inter, "Segoe UI", Roboto, system-ui, -apple-system, "Helvetica Neue", Arial;
            background: linear-gradient(180deg, var(--bg-start), var(--bg-end));
            color: var(--text);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 28px;
        }

        .card {
            width: 100%;
            max-width: 560px;
            padding: 28px;
            border-radius: 14px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            backdrop-filter: blur(var(--glass-blur));
            box-shadow: 0 12px 40px rgba(2, 6, 23, 0.6);
        }

        h1 {
            color: var(--title);
            margin: 0 0 6px;
            font-size: 1.5rem
        }

        .subtitle {
            color: var(--muted);
            margin: 0 0 12px;
            font-size: 0.95rem
        }

        .notice {
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 14px;
            font-size: 0.93rem
        }

        .notice.error {
            background: var(--danger);
            color: var(--danger-text)
        }

        .notice.success {
            background: var(--success);
            color: var(--success-text)
        }

        label {
            display: block;
            margin: 12px 0 6px;
            font-weight: 600;
            color: var(--muted);
            font-size: 0.92rem
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 14px;
            border-radius: 10px;
            background: var(--input-bg);
            border: 1px solid rgba(255, 255, 255, 0.04);
            color: var(--text);
            font-size: 0.95rem;
            outline: none;
            transition: box-shadow .18s ease, border-color .18s ease
        }

        input:focus {
            box-shadow: 0 6px 22px rgba(14, 165, 233, 0.12);
            border-color: var(--accent)
        }

        .form-row {
            display: grid;
            gap: 10px
        }

        button[type="submit"] {
            width: 100%;
            margin-top: 14px;
            padding: 12px;
            border-radius: 10px;
            border: none;
            background: linear-gradient(180deg, var(--accent), var(--accent-2));
            color: #001217;
            font-weight: 800;
            cursor: pointer
        }

        button[type="submit"]:hover {
            filter: brightness(.96);
            transform: translateY(-1px)
        }

        .login-link {
            display: inline-block;
            width: 100%;
            text-align: center;
            margin-top: 12px;
            padding: 10px;
            border-radius: 10px;
            color: var(--accent);
            text-decoration: none;
            font-weight: 700;
            border: 1px solid transparent
        }

        .login-link:hover {
            background: rgba(14, 165, 233, 0.06);
            border-color: rgba(14, 165, 233, 0.12)
        }

        .qr-img {
            margin-top: 12px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.06);
            max-width: 260px
        }

        @media (max-width:520px) {
            .card {
                padding: 20px
            }
        }
    </style>
</head>

<body>

    <main class="card">
        <h1>Registrar Usuario</h1>
        <p class="subtitle">Crea un nuevo usuario y configura 2FA</p>

        <?php if (!empty($mensaje)): ?>
            <div class="notice <?php echo $tipo_mensaje === 'success' ? 'success' : 'error'; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <?php if ($tipo_mensaje !== 'success'): ?>
            <form method="POST" action="register.php" autocomplete="off">
                <div class="form-row">
                    <label for="usuario">Usuario</label>
                    <input id="usuario" type="text" name="usuario" required>

                    <label for="password">Contraseña</label>
                    <input id="password" type="password" name="password" required>
                </div>

                <button type="submit">Registrar y Generar QR</button>
            </form>

            <a href="login.php" class="login-link">¿Ya tienes cuenta? Inicia sesión aquí</a>
        <?php endif; ?>
    </main>

</body>

</html>