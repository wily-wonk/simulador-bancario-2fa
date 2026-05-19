<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once "conexion.php";

// Solo el admin tiene acceso a los logs del sistema
if (!isset($_SESSION["usuario"]) || $_SESSION["rol"] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$logs = [];
$query = $conn->query("SELECT * FROM auditoria_logs ORDER BY id DESC LIMIT 200");
if ($query) {
    while ($row = $query->fetch_assoc()) {
        $logs[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Auditoría de Seguridad (Logs)</title>
    <style>
        :root {
            --bg-start: #0B1120;
            --bg-end: #000000;
            --card-bg: rgba(15, 23, 42, 0.72);
            --card-border: rgba(56, 189, 248, 0.12);
            --muted: #94a3b8;
            --text: #e2e8f0;
            --accent: #0ea5e9;
            --danger: #fb923c;
            --glass-blur: 10px
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
            padding: 28px
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            padding: 20px;
            border-radius: 12px;
            max-width: 1200px;
            margin: 0 auto;
            backdrop-filter: blur(var(--glass-blur));
            box-shadow: 0 12px 40px rgba(2, 6, 23, 0.6)
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
            margin-top: 15px
        }

        th,
        td {
            padding: 10px;
            color: #ffffff;
            text-align: left;
            vertical-align: top
        }

        th {
            color: var(--muted);

        /* Hacer visibles solo los enlaces Volver al dashboard y Cerrar sesión */
        a[href="dashboard.php"], a[href="logout.php"] { color: #ffffff !important; }
            font-weight: 700
        }

        .badge {
            background: linear-gradient(180deg, rgba(251, 146, 60, 0.08), rgba(251, 146, 60, 0.03));
            padding: 4px 8px;
            border-radius: 8px;
            font-weight: 700;
            color: var(--danger)
        }

        .btn {
            background: linear-gradient(180deg, var(--accent), #0d9488);
            color: #001217;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 800;
            display: inline-block
        }

        .ip-cell {
            font-family: monospace;
            color: #fda4af;
            font-weight: 700
        }
    </style>
</head>

<body>
    <div class="card">
        <h1> Trazabilidad y Auditoría del Sistema</h1>
        <p>Registro de eventos y monitoreo de acciones de usuarios.</p>
        <a href="dashboard.php" class="btn">Volver al Dashboard</a>

        <table>
            <thead>
                <tr>
                    <th>Fecha / Hora</th>
                    <th>IP Origen</th>
                    <th>Usuario (Rol)</th>
                    <th>Módulo</th>
                    <th>Acción</th>
                    <th>Detalles Forenses</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($log['fecha']); ?></td>
                        <td style="font-family: monospace; color: #b91c1c; font-weight: bold;"><?php echo htmlspecialchars($log['ip']); ?></td>
                        <td><?php echo htmlspecialchars($log['usuario']); ?> <span class="badge"><?php echo htmlspecialchars($log['rol']); ?></span></td>
                        <td><?php echo htmlspecialchars($log['modulo']); ?></td>
                        <td><?php echo htmlspecialchars($log['accion']); ?></td>
                        <td><?php echo htmlspecialchars($log['detalles']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>

</html>