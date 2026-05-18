<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

$mensaje = "";
$error = "";

$conn->query("CREATE TABLE IF NOT EXISTS cuentas_bancarias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cuenta VARCHAR(50) NOT NULL UNIQUE,
    titular VARCHAR(100) NOT NULL,
    saldo DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS transferencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cuenta_origen_id INT NOT NULL,
    cuenta_destino_id INT NOT NULL,
    monto DECIMAL(12,2) NOT NULL,
    concepto VARCHAR(255) NOT NULL,
    usuario_sistema VARCHAR(100) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cuenta_origen_id) REFERENCES cuentas_bancarias(id),
    FOREIGN KEY (cuenta_destino_id) REFERENCES cuentas_bancarias(id)
)");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $tipo = isset($_POST["tipo"]) ? $_POST["tipo"] : "";

    if ($tipo === "crear_cuenta") {
        $cuenta = isset($_POST["cuenta"]) ? trim($_POST["cuenta"]) : "";
        $titular = isset($_POST["titular"]) ? trim($_POST["titular"]) : "";
        $saldoInicial = isset($_POST["saldo_inicial"]) ? (float) $_POST["saldo_inicial"] : 0;

        if ($cuenta === "" || $titular === "") {
            $error = "Completa los datos de la cuenta.";
        } elseif ($saldoInicial < 0) {
            $error = "El saldo inicial no puede ser negativo.";
        } else {
            $stmt = $conn->prepare("INSERT INTO cuentas_bancarias (cuenta, titular, saldo) VALUES (?, ?, ?)");
            $stmt->bind_param("ssd", $cuenta, $titular, $saldoInicial);

            if ($stmt->execute()) {
                $mensaje = "Cuenta agregada correctamente.";
            } else {
                $error = "No se pudo crear la cuenta. Verifica que no exista el mismo numero de cuenta.";
            }
        }
    }

    if ($tipo === "transferir") {
        $origenId = isset($_POST["origen_id"]) ? (int) $_POST["origen_id"] : 0;
        $destinoId = isset($_POST["destino_id"]) ? (int) $_POST["destino_id"] : 0;
        $monto = isset($_POST["monto"]) ? (float) $_POST["monto"] : 0;
        $concepto = isset($_POST["concepto"]) ? trim($_POST["concepto"]) : "Transferencia interna";

        if ($origenId <= 0 || $destinoId <= 0 || $monto <= 0) {
            $error = "Selecciona cuentas validas e ingresa un monto mayor a cero.";
        } elseif ($origenId === $destinoId) {
            $error = "La cuenta origen y destino deben ser diferentes.";
        } else {
            $conn->begin_transaction();

            try {
                $saldoOrigen = 0;
                $qOrigen = $conn->prepare("SELECT saldo FROM cuentas_bancarias WHERE id = ? FOR UPDATE");
                $qOrigen->bind_param("i", $origenId);
                $qOrigen->execute();
                $rOrigen = $qOrigen->get_result();

                if ($rOrigen->num_rows === 0) {
                    throw new Exception("La cuenta de origen no existe.");
                }

                $saldoOrigen = (float) $rOrigen->fetch_assoc()["saldo"];

                $qDestino = $conn->prepare("SELECT id FROM cuentas_bancarias WHERE id = ? FOR UPDATE");
                $qDestino->bind_param("i", $destinoId);
                $qDestino->execute();
                $rDestino = $qDestino->get_result();

                if ($rDestino->num_rows === 0) {
                    throw new Exception("La cuenta de destino no existe.");
                }

                if ($saldoOrigen < $monto) {
                    throw new Exception("Saldo insuficiente en la cuenta de origen.");
                }

                $u1 = $conn->prepare("UPDATE cuentas_bancarias SET saldo = saldo - ? WHERE id = ?");
                $u1->bind_param("di", $monto, $origenId);
                $u1->execute();

                $u2 = $conn->prepare("UPDATE cuentas_bancarias SET saldo = saldo + ? WHERE id = ?");
                $u2->bind_param("di", $monto, $destinoId);
                $u2->execute();

                $ins = $conn->prepare("INSERT INTO transferencias (cuenta_origen_id, cuenta_destino_id, monto, concepto, usuario_sistema) VALUES (?, ?, ?, ?, ?)");
                $usuarioSistema = $_SESSION["usuario"];
                $ins->bind_param("iidss", $origenId, $destinoId, $monto, $concepto, $usuarioSistema);
                $ins->execute();

                $conn->commit();
                $mensaje = "Transferencia realizada correctamente.";
            } catch (Exception $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
    }
}

$cuentas = [];
$qCuentas = $conn->query("SELECT id, cuenta, titular, saldo FROM cuentas_bancarias ORDER BY cuenta ASC");
if ($qCuentas) {
    while ($row = $qCuentas->fetch_assoc()) {
        $cuentas[] = $row;
    }
}

$historial = [];
$sqlHistorial = "SELECT t.id, t.monto, t.concepto, t.usuario_sistema, t.created_at,
                co.cuenta AS cuenta_origen,
                cd.cuenta AS cuenta_destino
                FROM transferencias t
                INNER JOIN cuentas_bancarias co ON t.cuenta_origen_id = co.id
                INNER JOIN cuentas_bancarias cd ON t.cuenta_destino_id = cd.id
                ORDER BY t.id DESC
                LIMIT 100";
$qHistorial = $conn->query($sqlHistorial);
if ($qHistorial) {
    while ($row = $qHistorial->fetch_assoc()) {
        $historial[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modulo de Transferencias</title>
    <style>
        :root {
            --bg: #f3f7fb;
            --card: #ffffff;
            --text: #1b2a41;
            --muted: #5f6c80;
            --accent: #0f766e;
            --accent-dark: #115e59;
            --border: #d9e2ec;
            --ok-bg: #dcfce7;
            --ok-text: #166534;
            --err-bg: #fee2e2;
            --err-text: #991b1b;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text);
            background: radial-gradient(circle at top right, #dff3ef 0%, var(--bg) 45%);
            padding: 20px;
        }

        .wrap {
            max-width: 1050px;
            margin: 0 auto;
            display: grid;
            gap: 16px;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 12px 30px rgba(27, 42, 65, 0.08);
        }

        h1, h2 { margin: 0 0 12px; }

        .hint {
            margin-top: -4px;
            color: var(--muted);
            font-size: 0.92rem;
        }

        .msg, .err {
            padding: 10px 12px;
            border-radius: 10px;
            margin-bottom: 12px;
            font-size: 0.93rem;
        }

        .msg { background: var(--ok-bg); color: var(--ok-text); }
        .err { background: var(--err-bg); color: var(--err-text); }

        .grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            font-size: 0.92rem;
        }

        input, select {
            width: 100%;
            padding: 10px 12px;
            border-radius: 10px;
            border: 1px solid var(--border);
            font-size: 0.95rem;
            background: #fff;
        }

        .btn {
            border: none;
            border-radius: 10px;
            padding: 10px 12px;
            color: #fff;
            cursor: pointer;
            font-weight: 700;
            text-decoration: none;
            display: inline-block;
            background: var(--accent);
        }

        .btn:hover { background: var(--accent-dark); }

        .top-links {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            font-size: 0.94rem;
        }

        th, td {
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        .money { font-weight: 700; }
    </style>
</head>
<body>
<div class="wrap">
    <section class="card">
        <h1>Simulador de transferencias bancarias</h1>
        <p class="hint">Agrega cuentas, transfiere saldo entre ellas y revisa el registro historico.</p>
        <div class="top-links">
            <a class="btn" href="dashboard.php">Volver al dashboard</a>
            <a class="btn" href="logout.php">Cerrar sesion</a>
        </div>
    </section>

    <section class="card">
        <h2>Estado del modulo</h2>
        <?php if ($mensaje !== ""): ?>
            <div class="msg"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>
        <?php if ($error !== ""): ?>
            <div class="err"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2>Agregar cuenta</h2>
        <form method="POST" action="transferencias.php">
            <input type="hidden" name="tipo" value="crear_cuenta">
            <div class="grid">
                <div>
                    <label for="cuenta">Numero de cuenta</label>
                    <input id="cuenta" name="cuenta" required>
                </div>
                <div>
                    <label for="titular">Titular</label>
                    <input id="titular" name="titular" required>
                </div>
                <div>
                    <label for="saldo_inicial">Saldo inicial</label>
                    <input id="saldo_inicial" name="saldo_inicial" type="number" step="0.01" min="0" value="0" required>
                </div>
            </div>
            <div class="top-links">
                <button class="btn" type="submit">Crear cuenta</button>
            </div>
        </form>
    </section>

    <section class="card">
        <h2>Realizar transferencia</h2>

        <?php if (count($cuentas) < 2): ?>
            <p>Necesitas al menos 2 cuentas para transferir.</p>
        <?php else: ?>
            <form method="POST" action="transferencias.php">
                <input type="hidden" name="tipo" value="transferir">
                <div class="grid">
                    <div>
                        <label for="origen_id">Cuenta origen</label>
                        <select id="origen_id" name="origen_id" required>
                            <option value="">Selecciona una cuenta</option>
                            <?php foreach ($cuentas as $c): ?>
                                <option value="<?php echo (int) $c["id"]; ?>">
                                    <?php echo htmlspecialchars($c["cuenta"] . " - " . $c["titular"] . " (Saldo: " . number_format((float) $c["saldo"], 2) . ")"); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="destino_id">Cuenta destino</label>
                        <select id="destino_id" name="destino_id" required>
                            <option value="">Selecciona una cuenta</option>
                            <?php foreach ($cuentas as $c): ?>
                                <option value="<?php echo (int) $c["id"]; ?>">
                                    <?php echo htmlspecialchars($c["cuenta"] . " - " . $c["titular"]); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="monto">Monto</label>
                        <input id="monto" name="monto" type="number" step="0.01" min="0.01" required>
                    </div>
                    <div>
                        <label for="concepto">Concepto</label>
                        <input id="concepto" name="concepto" value="Transferencia interna" required>
                    </div>
                </div>
                <div class="top-links">
                    <button class="btn" type="submit">Transferir</button>
                </div>
            </form>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2>Cuentas registradas</h2>
        <?php if (count($cuentas) === 0): ?>
            <p>No hay cuentas cargadas.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cuenta</th>
                        <th>Titular</th>
                        <th>Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cuentas as $c): ?>
                        <tr>
                            <td><?php echo (int) $c["id"]; ?></td>
                            <td><?php echo htmlspecialchars($c["cuenta"]); ?></td>
                            <td><?php echo htmlspecialchars($c["titular"]); ?></td>
                            <td class="money">$ <?php echo number_format((float) $c["saldo"], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2>Historial de transferencias</h2>
        <?php if (count($historial) === 0): ?>
            <p>No hay transferencias registradas.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Origen</th>
                        <th>Destino</th>
                        <th>Monto</th>
                        <th>Concepto</th>
                        <th>Usuario</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historial as $t): ?>
                        <tr>
                            <td><?php echo (int) $t["id"]; ?></td>
                            <td><?php echo htmlspecialchars($t["cuenta_origen"]); ?></td>
                            <td><?php echo htmlspecialchars($t["cuenta_destino"]); ?></td>
                            <td class="money">$ <?php echo number_format((float) $t["monto"], 2); ?></td>
                            <td><?php echo htmlspecialchars($t["concepto"]); ?></td>
                            <td><?php echo htmlspecialchars($t["usuario_sistema"]); ?></td>
                            <td><?php echo htmlspecialchars($t["created_at"]); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</div>
</body>
</html>
