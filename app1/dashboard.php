<?php
session_start();

// Verificar si existe la sesión de usuario
if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
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
            --danger: rgba(220, 38, 38, 0.08);
            --danger-text: #fda4af;
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
            padding: 28px;
            display: flex;
            align-items: center;
            justify-content: center
        }

        .container {
            width: 100%;
            max-width: 1100px
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 18px
        }

        .title-block {
            display: flex;
            flex-direction: column
        }

        h1 {
            margin: 0;
            font-size: 1.4rem;
            color: var(--title)
        }

        .subtitle {
            color: var(--muted);
            font-size: 0.95rem;
            margin-top: 6px
        }

        .role-badge {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(14, 165, 233, 0.08);
            color: var(--accent);
            font-weight: 700;
            border: 1px solid rgba(14, 165, 233, 0.08)
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px
        }

        .panel-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 20px;
            backdrop-filter: blur(var(--glass-blur));
            box-shadow: 0 10px 30px rgba(2, 6, 23, 0.6);
            display: flex;
            flex-direction: column;
            gap: 12px;
            align-items: flex-start
        }

        .modules {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            margin-top: 14px
        }

        .module-card {
            background: transparent;
            border-radius: 12px;
            padding: 18px;
            border: 1px solid transparent;
            color: var(--text);
            text-decoration: none;
            display: flex;
            flex-direction: column;
            gap: 8px
        }

        .module-card .label {
            font-weight: 800;
            color: var(--title)
        }

        .module-card .desc {
            color: var(--muted);
            font-size: 0.92rem
        }

        .module-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 18px 40px rgba(14, 165, 233, 0.06);
            border-color: rgba(14, 165, 233, 0.14)
        }

        .module-cta {
            margin-top: auto;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 10px;
            background: linear-gradient(180deg, var(--accent), var(--accent-2));
            color: #001217;
            font-weight: 800
        }

        .logout {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.04);
            color: var(--danger-text);
            padding: 8px 12px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700
        }

        .logout:hover {
            background: var(--danger);
            border-color: rgba(220, 38, 38, 0.18)
        }

        /* Hacer visible el enlace Cerrar sesión */
        a[href="logout.php"] { color: #ffffff !important; }

        @media (max-width:560px) {
            .header {
                flex-direction: column;
                align-items: flex-start
            }

            .modules {
                grid-template-columns: 1fr
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="title-block">
                <h1>Bienvenido, <?php echo htmlspecialchars($_SESSION["usuario"]); ?></h1>
                <div class="subtitle">Accede a los módulos seguros del sistema</div>
            </div>

            <div style="display:flex;align-items:center;gap:12px">
                <div class="role-badge">Rol: <?php echo htmlspecialchars($_SESSION["rol"] ?? 'Desconocido'); ?></div>
                <a href="logout.php" class="logout">Cerrar sesión</a>
            </div>
        </div>

        <section class="panel-card">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
                <div>
                    <div style="font-weight:800;font-size:1.05rem;color:var(--title)">Panel Principal</div>
                    <div style="color:var(--muted);margin-top:6px">Selecciona una acción rápida o navega a los módulos.</div>
                </div>
            </div>

            <div class="modules">
                <?php if (isset($_SESSION["rol"]) && $_SESSION["rol"] === 'admin'): ?>
                    <a href="usuarios.php" class="module-card">
                        <div class="label">Gestionar usuarios (ABM)</div>
                        <div class="desc">Crear, editar y eliminar usuarios. Requiere permisos de administrador.</div>
                        <span class="module-cta">Ir a Usuarios</span>
                    </a>
                    <a href="auditoria.php" class="module-card">
                        <div class="label">Auditoría</div>
                        <div class="desc">Ver registros forenses y eventos de seguridad.</div>
                        <span class="module-cta">Ver Auditoría</span>
                    </a>
                <?php endif; ?>

                <a href="transferencias.php" class="module-card">
                    <div class="label">Módulo de transferencias bancarias</div>
                    <div class="desc">Simula transferencias seguras con firma OTP y auditoría.</div>
                    <span class="module-cta">Ir a Transferencias</span>
                </a>

            </div>
        </section>
    </div>
</body>

</html>