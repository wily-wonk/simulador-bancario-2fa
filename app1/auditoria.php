<?php
session_start();
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
        body {
            font-family: "Segoe UI", sans-serif;
            background: #f3f7fb;
            color: #1b2a41;
            padding: 20px;
        }

        .card {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            max-width: 1200px;
            margin: 0 auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
            margin-top: 15px;
        }

        th,
        td {
            padding: 10px;
            border-bottom: 1px solid #d9e2ec;
            text-align: left;
        }

        th {
            background: #0f766e;
            color: white;
        }

        .badge {
            background: #e2e8f0;
            padding: 3px 8px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 0.8rem;
        }

        .btn {
            background: #0f766e;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            display: inline-block;
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