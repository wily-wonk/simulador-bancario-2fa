<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
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

        .error {
            margin-bottom: 14px;
            padding: 10px 12px;
            border-radius: 10px;
            background: var(--error-bg);
            color: var(--error-text);
            font-size: 0.92rem;
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

        .register-link {
            display: block;
            margin-top: 12px;
            padding: 11px;
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--accent);
            text-align: center;
            text-decoration: none;
            font-weight: 700;
            transition: background 0.2s ease, border-color 0.2s ease, color 0.2s ease;
        }

        .register-link:hover {
            background: #e8f7f4;
            border-color: var(--accent);
            color: var(--accent-dark);
        }
    </style>
</head>

<body>

    <main class="card">
        <h1>Acceso al sistema</h1>

        <?php if (isset($_GET['error'])): ?>
            <div class="error">
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

            <button type="submit">Ingresar</button>
        </form>

        <a class="register-link" href="register.php">Crear cuenta nueva</a>
    </main>

</body>

</html>