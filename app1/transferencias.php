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
    usuario_propietario VARCHAR(50) NOT NULL,
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

function validarOTPTransaccional($usuario, $otpToken)
{
    $exe_path = realpath(__DIR__ . '/../multiotp_5.10.2.2/windows/multiotp.exe');
    if (!$exe_path) return false;

    $command = escapeshellarg($exe_path) . ' ' . escapeshellarg($usuario) . ' ' . escapeshellarg($otpToken);
    $output = [];
    $exitCode = 1;
    exec($command . ' 2>&1', $output, $exitCode);

    return ($exitCode === 0);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $tipo = isset($_POST["tipo"]) ? $_POST["tipo"] : "";
    $otp_user = isset($_POST["otp_user"]) ? trim($_POST["otp_user"]) : "";

    if (empty($otp_user) || !validarOTPTransaccional($_SESSION["usuario"], $otp_user)) {
        $error = "Transacción denegada: Código OTP de seguridad inválido.";
    } else {
        if ($tipo === "crear_cuenta") {
            $cuenta = isset($_POST["cuenta"]) ? trim($_POST["cuenta"]) : "";
            $titular = isset($_POST["titular"]) ? trim($_POST["titular"]) : "";
            $saldoInicial = isset($_POST["saldo_inicial"]) ? (float) $_POST["saldo_inicial"] : 0;

            if ($_SESSION['rol'] === 'admin') {
                $propietario = isset($_POST["usuario_propietario"]) ? trim($_POST["usuario_propietario"]) : "";
            } else {
                $propietario = $_SESSION['usuario'];
            }

            if ($cuenta === "" || $titular === "" || $propietario === "") {
                $error = "Completa todos los datos de la cuenta.";
            } elseif ($saldoInicial < 0) {
                $error = "El saldo inicial no puede ser negativo.";
            } else {
                $stmt = $conn->prepare("INSERT INTO cuentas_bancarias (cuenta, titular, saldo, usuario_propietario) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssds", $cuenta, $titular, $saldoInicial, $propietario);

                if ($stmt->execute()) {
                    $mensaje = "Cuenta agregada exitosamente para el usuario: $propietario.";
                    // Registrar auditoría: creación de cuenta bancaria
                    registrarAuditoria($conn, 'Cuentas Bancarias', 'Crear', 'Se creó la cuenta: ' . $cuenta . ' titular: ' . $titular . ' propietario: ' . $propietario . ' saldo_inicial: ' . $saldoInicial);
                } else {
                    $error = "No se pudo crear la cuenta. Verifica que no exista el mismo número.";
                }
            }
        }

        if ($tipo === "transferir") {
            $origenId = isset($_POST["origen_id"]) ? (int) $_POST["origen_id"] : 0;
            $destinoId = isset($_POST["destino_id"]) ? (int) $_POST["destino_id"] : 0;
            $monto = isset($_POST["monto"]) ? (float) $_POST["monto"] : 0;
            $concepto = isset($_POST["concepto"]) ? trim($_POST["concepto"]) : "Transferencia interna";

            if ($origenId <= 0 || $destinoId <= 0 || $monto <= 0) {
                $error = "Selecciona cuentas válidas e ingresa un monto mayor a cero.";
            } elseif ($origenId === $destinoId) {
                $error = "La cuenta origen y destino deben ser diferentes.";
            } else {
                $conn->begin_transaction();

                try {
                    if ($_SESSION['rol'] !== 'admin') {
                        $checkProp = $conn->prepare("SELECT id FROM cuentas_bancarias WHERE id = ? AND usuario_propietario = ?");
                        $checkProp->bind_param("is", $origenId, $_SESSION["usuario"]);
                        $checkProp->execute();
                        if ($checkProp->get_result()->num_rows === 0) {
                            throw new Exception("ALERTA DE SEGURIDAD: Intento de transferencia desde una cuenta no autorizada.");
                        }
                    }

                    $saldoOrigen = 0;
                    $qOrigen = $conn->prepare("SELECT saldo FROM cuentas_bancarias WHERE id = ? FOR UPDATE");
                    $qOrigen->bind_param("i", $origenId);
                    $qOrigen->execute();
                    $rOrigen = $qOrigen->get_result();

                    if ($rOrigen->num_rows === 0) throw new Exception("La cuenta de origen no existe.");
                    $saldoOrigen = (float) $rOrigen->fetch_assoc()["saldo"];

                    $qDestino = $conn->prepare("SELECT id FROM cuentas_bancarias WHERE id = ? FOR UPDATE");
                    $qDestino->bind_param("i", $destinoId);
                    $qDestino->execute();
                    $rDestino = $qDestino->get_result();

                    if ($rDestino->num_rows === 0) throw new Exception("La cuenta de destino no existe.");
                    if ($saldoOrigen < $monto) throw new Exception("Saldo insuficiente en la cuenta de origen.");

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
                    $mensaje = "Transferencia de $monto realizada correctamente.";
                    // Registrar auditoría de la transferencia
                    registrarAuditoria($conn, 'Transferencias', 'Transferir', 'Transfirió $' . $monto . ' desde cuenta ID ' . $origenId . ' hacia cuenta ID ' . $destinoId . '. Concepto: ' . $concepto);
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = $e->getMessage();
                }
            }
        }
    }
}

$usuariosSistema = [];
$qUsr = $conn->query("SELECT usuario FROM usuarios ORDER BY usuario ASC");
if ($qUsr) {
    while ($r = $qUsr->fetch_assoc()) {
        $usuariosSistema[] = $r['usuario'];
    }
}

$cuentasDestino = [];
$qCuentas = $conn->query("SELECT id, cuenta, titular, saldo, usuario_propietario FROM cuentas_bancarias ORDER BY cuenta ASC");
if ($qCuentas) {
    while ($row = $qCuentas->fetch_assoc()) {
        $cuentasDestino[] = $row;
    }
}

$cuentasOrigen = [];
if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    $cuentasOrigen = $cuentasDestino;
} else {
    foreach ($cuentasDestino as $c) {
        if ($c['usuario_propietario'] === $_SESSION['usuario']) {
            $cuentasOrigen[] = $c;
        }
    }
}

$historial = [];
$sqlHistorial = "SELECT t.id, t.monto, t.concepto, t.usuario_sistema, t.created_at,
                co.cuenta AS cuenta_origen, co.titular AS titular_origen,
                cd.cuenta AS cuenta_destino, cd.titular AS titular_destino
                FROM transferencias t
                INNER JOIN cuentas_bancarias co ON t.cuenta_origen_id = co.id
                INNER JOIN cuentas_bancarias cd ON t.cuenta_destino_id = cd.id
                ORDER BY t.id DESC LIMIT 100";
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
    <title>Módulo de Transferencias - Seguro</title>
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
            --danger: #b91c1c;
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

        h1,
        h2 {
            margin: 0 0 12px;
        }

        .hint {
            margin-top: -4px;
            color: var(--muted);
            font-size: 0.92rem;
        }

        .msg,
        .err {
            padding: 10px 12px;
            border-radius: 10px;
            margin-bottom: 12px;
            font-size: 0.93rem;
        }

        .msg {
            background: var(--ok-bg);
            color: var(--ok-text);
        }

        .err {
            background: var(--err-bg);
            color: var(--err-text);
        }

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

        input,
        select {
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

        .btn:hover {
            background: var(--accent-dark);
        }

        .btn-danger {
            background: var(--danger);
        }

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

        th,
        td {
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        .money {
            font-weight: 700;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            background: #e2e8f0;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .modal-content {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            width: 320px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .modal-content h3 {
            margin-top: 0;
            color: var(--accent);
        }

        .modal-content input {
            margin-bottom: 15px;
            text-align: center;
            letter-spacing: 2px;
        }

        .modal-content .btn-group {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
    </style>
</head>

<body>

    <div id="otpModal" class="modal-overlay">
        <div class="modal-content">
            <h3>Firma de Transacción</h3>
            <p style="font-size: 0.9rem; color: var(--muted);">Ingrese el código generado en su aplicación OTP.</p>
            <input type="text" id="otpInput" placeholder="000000" pattern="[0-9]{6}" maxlength="6">
            <div class="btn-group">
                <button class="btn" onclick="submitConOTP()">Autorizar</button>
                <button class="btn btn-danger" onclick="cerrarModal()">Cancelar</button>
            </div>
        </div>
    </div>

    <div class="wrap">
        <section class="card">
            <h1>Simulador de transferencias bancarias</h1>
            <p class="hint">Modo: <strong><?php echo $_SESSION['rol'] === 'admin' ? 'Administrador (Acceso Total)' : 'Cliente (Solo mis cuentas)'; ?></strong></p>
            <div class="top-links">
                <a class="btn" href="dashboard.php">Volver al dashboard</a>
                <a class="btn" href="logout.php">Cerrar sesión</a>
            </div>
        </section>

        <section class="card">
            <h2>Estado del módulo</h2>
            <?php if ($mensaje !== ""): ?> <div class="msg"><?php echo htmlspecialchars($mensaje); ?></div> <?php endif; ?>
            <?php if ($error !== ""): ?> <div class="err"><?php echo htmlspecialchars($error); ?></div> <?php endif; ?>
        </section>

        <section class="card">
            <h2>Abrir Nueva Cuenta</h2>
            <form method="POST" action="transferencias.php">
                <input type="hidden" name="tipo" value="crear_cuenta">
                <input type="hidden" name="otp_user" value="">
                <div class="grid">
                    <div>
                        <label for="cuenta">Número de cuenta</label>
                        <input id="cuenta" name="cuenta" required>
                    </div>
                    <div>
                        <label for="titular">Alias / Titular</label>
                        <input id="titular" name="titular" required>
                    </div>

                    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin'): ?>
                        <div>
                            <label for="usuario_propietario">Asignar al Usuario del Sistema</label>
                            <select id="usuario_propietario" name="usuario_propietario" required>
                                <option value="">Seleccione el propietario...</option>
                                <?php foreach ($usuariosSistema as $us): ?>
                                    <option value="<?php echo htmlspecialchars($us); ?>"><?php echo htmlspecialchars($us); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div>
                        <label for="saldo_inicial">Saldo inicial</label>
                        <input id="saldo_inicial" name="saldo_inicial" type="number" step="0.01" min="0" value="0" required>
                    </div>
                </div>
                <div class="top-links">
                    <button class="btn" type="button" onclick="abrirModal(this.form)">Crear cuenta</button>
                </div>
            </form>
        </section>

        <!-- NUEVA SECCIÓN: MIS CUENTAS / SALDOS -->
        <section class="card">
            <h2><?php echo $_SESSION['rol'] === 'admin' ? 'Todas las Cuentas del Sistema' : 'Mis Cuentas y Saldos'; ?></h2>
            <?php if (count($cuentasOrigen) === 0): ?>
                <p>No hay cuentas bancarias registradas.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Cuenta</th>
                            <th>Titular / Alias</th>
                            <?php if ($_SESSION['rol'] === 'admin'): ?>
                                <th>Usuario Propietario</th>
                            <?php endif; ?>
                            <th>Saldo Disponible</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cuentasOrigen as $c): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($c["cuenta"]); ?></td>
                                <td><?php echo htmlspecialchars($c["titular"]); ?></td>
                                <?php if ($_SESSION['rol'] === 'admin'): ?>
                                    <td><span class="badge"><?php echo htmlspecialchars($c["usuario_propietario"]); ?></span></td>
                                <?php endif; ?>
                                <td class="money" style="color: #0f766e;">$ <?php echo number_format((float) $c["saldo"], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>

        <section class="card">
            <h2>Realizar transferencia</h2>
            <?php if (count($cuentasOrigen) === 0): ?>
                <p>No tienes cuentas bancarias asociadas para enviar dinero.</p>
            <?php else: ?>
                <form method="POST" action="transferencias.php">
                    <input type="hidden" name="tipo" value="transferir">
                    <input type="hidden" name="otp_user" value="">
                    <div class="grid">
                        <div>
                            <label for="origen_id">Desde mi cuenta (Origen)</label>
                            <select id="origen_id" name="origen_id" required>
                                <option value="">Selecciona tu cuenta</option>
                                <?php foreach ($cuentasOrigen as $c): ?>
                                    <option value="<?php echo (int) $c["id"]; ?>">
                                        <?php echo htmlspecialchars($c["cuenta"] . " - " . $c["titular"] . " ($" . number_format((float) $c["saldo"], 2) . ")"); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="destino_id">Hacia la cuenta (Destino)</label>
                            <select id="destino_id" name="destino_id" required>
                                <option value="">Selecciona una cuenta</option>
                                <?php foreach ($cuentasDestino as $c): ?>
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
                            <input id="concepto" name="concepto" value="Transferencia" required>
                        </div>
                    </div>
                    <div class="top-links">
                        <button class="btn" type="button" onclick="abrirModal(this.form)">Transferir Fondos</button>
                    </div>
                </form>
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
                            <th>Origen</th>
                            <th>Destino</th>
                            <th>Monto</th>
                            <th>Concepto</th>
                            <th>Autorizado por</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historial as $t): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($t["titular_origen"] . "\n" . $t["cuenta_origen"]); ?></td>
                                <td><?php echo htmlspecialchars($t["titular_destino"] . "\n" . $t["cuenta_destino"]); ?></td>
                                <td class="money" style="color: #0f766e;">$ <?php echo number_format((float) $t["monto"], 2); ?></td>
                                <td><?php echo htmlspecialchars($t["concepto"]); ?></td>
                                <td><span class="badge"><?php echo htmlspecialchars($t["usuario_sistema"]); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </div>

    <script>
        let formActual = null;

        function abrirModal(formulario) {
            if (!formulario.checkValidity()) {
                formulario.reportValidity();
                return;
            }
            formActual = formulario;
            document.getElementById('otpModal').style.display = 'flex';
            document.getElementById('otpInput').value = '';
            document.getElementById('otpInput').focus();
        }

        function cerrarModal() {
            document.getElementById('otpModal').style.display = 'none';
            formActual = null;
        }

        function submitConOTP() {
            let otpValue = document.getElementById('otpInput').value.trim();
            if (otpValue.length !== 6) {
                alert('Ingrese un código OTP válido.');
                return;
            }
            let inputOculto = formActual.querySelector('input[name="otp_user"]');
            if (inputOculto) {
                inputOculto.value = otpValue;
                formActual.submit();
            }
        }
    </script>
</body>

</html>