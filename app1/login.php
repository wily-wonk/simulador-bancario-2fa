<?php if (session_status() !== PHP_SESSION_ACTIVE) session_start(); ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        /* Dark professional theme - Glassmorphism */
        :root {
            --bg-start: #0B1120;
            /* deep navy */
            --bg-end: #000000;
            /* black */
            --card-bg: rgba(15, 23, 42, 0.72);
            --card-border: rgba(56, 189, 248, 0.12);
            --muted: #94a3b8;
            --text: #e2e8f0;
            --title: #ffffff;
            --accent: #0ea5e9;
            /* cyan accent */
            --accent-2: #0d9488;
            /* teal */
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
            max-width: 460px;
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
            margin: 0 0 18px;
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
            color: var(--danger-text);
        }

        .notice.success {
            background: var(--success);
            color: var(--success-text);
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
            transition: box-shadow .18s ease, border-color .18s ease, transform .06s ease;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            box-shadow: 0 6px 22px rgba(14, 165, 233, 0.12);
            border-color: var(--accent);
        }

        .actions {
            display: flex;
            gap: 12px;
            margin-top: 18px
        }

        button[type="submit"] {
            flex: 1;
            padding: 12px;
            border-radius: 10px;
            border: none;
            background: linear-gradient(180deg, var(--accent), var(--accent-2));
            color: #001217;
            font-weight: 800;
            cursor: pointer
        }

        button[type="submit"]:hover {
            filter: brightness(.95);
            transform: translateY(-1px)
        }

        .register-link {
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

        .register-link:hover {
            background: rgba(14, 165, 233, 0.06);
            border-color: rgba(14, 165, 233, 0.12)
        }

        @media (max-width:420px) {
            .card {
                padding: 20px
            }
        }
    </style>
</head>

<body>

    <main class="card">
        <h1>Acceso al sistema</h1>
        <p class="subtitle">Autenticación segura — Usuario y contraseña</p>

        <?php if (isset($_GET['error'])): ?>
            <div class="notice error">
                <?php
                $error = $_GET['error'];
                if ($error === '1') {
                    echo 'Usuario o contrasena incorrectos.';
                } elseif ($error === '2') {
                    echo 'Completa todos los campos.';
                } else {
                    echo 'Error de autenticacion.';
                }
                ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="auth.php" autocomplete="off">
            <label for="usuario">Usuario</label>
            <input id="usuario" type="text" name="usuario" required>

            <label for="password">Contrasena</label>
            <input id="password" type="password" name="password" required>

            <div class="actions">
                <button type="submit">Ingresar</button>
            </div>
        </form>

        <a class="register-link" href="register.php">Crear cuenta nueva</a>
    </main>

</body>

</html>