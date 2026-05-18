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
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Verificación 2FA</title>
    <style>
        :root {
            --bg: #f3f7fb;
            --card: #ffffff;
            --text: #1b2a41;
            --muted: #5f6c80;
            --accent: #0f766e;
            --accent-dark: #115e59;
            --border: #d9e2ec;
            --input-bg: #f8fafb;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text);
            background: radial-gradient(circle at top right, #dff3ef 0%, var(--bg) 45%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .card {
            width: 100%;
            max-width: 520px;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 28px;
            box-shadow: 0 14px 40px rgba(27,42,65,0.08);
        }

        h1 { margin: 0 0 6px; font-size: 1.5rem; }
        .subtitle { margin: 0 0 18px; color: var(--muted); }

        label { display:block; margin: 12px 0 8px; font-weight:600; }

        .info-row { display:flex; gap:12px; align-items:center; }
        .user-field {
            flex:1; padding:10px 12px; border-radius:10px; border:1px solid var(--border);
            background: var(--input-bg); color:var(--muted);
        }

        .otp-box {
            display:flex; gap:10px; justify-content:center; margin: 12px 0 6px;
        }

        .otp-box input[type="text"] {
            width:48px; height:56px; text-align:center; font-size:1.4rem; font-weight:700;
            border-radius:10px; border:1px solid var(--border); background:#fff;
            outline: none;
        }

        .otp-box input[type="text"]:focus { border-color:var(--accent); box-shadow:0 4px 14px rgba(15,118,110,0.08); }

        .note { font-size:0.9rem; color:var(--muted); text-align:center; margin-top:8px; }

        .actions { margin-top:18px; display:flex; gap:12px; }

        .btn { flex:1; padding:11px; border-radius:10px; border:none; cursor:pointer; font-weight:700; }
        .btn-primary { background:var(--accent); color:#fff; }
        .btn-secondary { background:transparent; border:1px solid var(--border); color:var(--muted); }

        .error { margin-bottom:12px; padding:10px 12px; border-radius:10px; background:#fee2e2; color:#991b1b; }

        @media (max-width:420px) {
            .otp-box input[type="text"] { width:40px; height:50px; }
        }
    </style>
</head>

<body>
    <main class="card">
        <h1>Verificación de Seguridad</h1>
        <p class="subtitle">Ingresa el código de 6 dígitos generado por tu aplicación OTP.</p>

        <?php if (isset($_GET['error'])): ?>
            <div class="error">Código OTP inválido o expirado.</div>
        <?php endif; ?>

        <form method="POST" action="verify_otp.php" autocomplete="off" id="otp-form">
            <label for="usuario">Usuario</label>
            <div class="info-row">
                <input id="usuario" class="user-field" type="text" name="usuario" value="<?php echo htmlspecialchars($usuario_actual); ?>" readonly>
            </div>

            <label for="otp">Código OTP</label>
            <div class="otp-box" id="otp-box" aria-label="Ingrese código de 6 dígitos">
                <input inputmode="numeric" pattern="[0-9]*" maxlength="1" type="text" class="otp-input" />
                <input inputmode="numeric" pattern="[0-9]*" maxlength="1" type="text" class="otp-input" />
                <input inputmode="numeric" pattern="[0-9]*" maxlength="1" type="text" class="otp-input" />
                <input inputmode="numeric" pattern="[0-9]*" maxlength="1" type="text" class="otp-input" />
                <input inputmode="numeric" pattern="[0-9]*" maxlength="1" type="text" class="otp-input" />
                <input inputmode="numeric" pattern="[0-9]*" maxlength="1" type="text" class="otp-input" />
            </div>

            <input type="hidden" name="otp" id="otp-hidden">

            <div class="note">Introduce los 6 dígitos que aparecen en tu app de autenticación.</div>

            <div class="actions">
                <button type="button" class="btn btn-secondary" onclick="location.href='login.php'">Volver</button>
                <button type="submit" class="btn btn-primary">Verificar y Entrar</button>
            </div>
        </form>
    </main>

    <script>
        (function(){
            const inputs = Array.from(document.querySelectorAll('.otp-input'));
            const hidden = document.getElementById('otp-hidden');
            const form = document.getElementById('otp-form');

            inputs.forEach((input, idx) => {
                input.addEventListener('input', (e) => {
                    const val = e.target.value.replace(/[^0-9]/g, '');
                    e.target.value = val;
                    if (val && idx < inputs.length - 1) {
                        inputs[idx + 1].focus();
                    }
                });

                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Backspace' && !e.target.value && idx > 0) {
                        inputs[idx - 1].focus();
                    }
                    // allow arrow navigation
                    if (e.key === 'ArrowLeft' && idx > 0) { inputs[idx - 1].focus(); }
                    if (e.key === 'ArrowRight' && idx < inputs.length - 1) { inputs[idx + 1].focus(); }
                });

                input.addEventListener('paste', (e) => {
                    e.preventDefault();
                    const paste = (e.clipboardData || window.clipboardData).getData('text').trim();
                    const digits = paste.replace(/\D/g, '').slice(0, inputs.length).split('');
                    digits.forEach((d, i) => { inputs[i].value = d; });
                    const next = Math.min(digits.length, inputs.length - 1);
                    inputs[next].focus();
                });
            });

            form.addEventListener('submit', (e) => {
                const code = inputs.map(i => i.value || '').join('');
                if (code.length !== inputs.length) {
                    e.preventDefault();
                    alert('Introduce los 6 dígitos del código OTP.');
                    inputs[code.length].focus();
                    return false;
                }
                hidden.value = code;
            });

            // focus first input on load
            inputs[0].focus();
        })();
    </script>
</body>

</html>