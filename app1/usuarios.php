<?php
session_start();
require_once "conexion.php";

// 1. PROTECCIÓN DE ACCESO: Solo admins pueden entrar aquí
if (!isset($_SESSION["usuario"]) || $_SESSION["rol"] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$accion = isset($_GET["accion"]) ? $_GET["accion"] : "";
$mensaje = "";
$error = "";

// 2. FUNCIÓN DE VALIDACIÓN OTP: Para verificar al admin antes de cada acción
function validarOTPAdmin($adminUser, $otpToken)
{
    $exe_path = realpath(__DIR__ . '/../multiotp_5.10.2.2/windows/multiotp.exe');
    if (!$exe_path) return false;

    $command = escapeshellarg($exe_path) . ' ' . escapeshellarg($adminUser) . ' ' . escapeshellarg($otpToken);
    $output = [];
    $exitCode = 1;
    exec($command . ' 2>&1', $output, $exitCode);

    return ($exitCode === 0);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $tipo = isset($_POST["tipo"]) ? $_POST["tipo"] : "";
    $otp_admin = isset($_POST["otp_admin"]) ? trim($_POST["otp_admin"]) : "";

    // 3. STEP-UP AUTHENTICATION: Validamos el token antes de hacer nada en la BD
    if (empty($otp_admin) || !validarOTPAdmin($_SESSION["usuario"], $otp_admin)) {
        $error = "Fallo de seguridad: Código OTP de Administrador inválido.";
    } else {
        // --- INICIO DE ACCIONES ABM ---
        if ($tipo === "crear") {
            $nuevoUsuario = isset($_POST["usuario"]) ? trim($_POST["usuario"]) : "";
            $nuevaPassword = isset($_POST["password"]) ? $_POST["password"] : "";
            $nuevoRol = isset($_POST["rol"]) ? $_POST["rol"] : "user"; // Capturamos el rol

            if ($nuevoUsuario === "" || $nuevaPassword === "") {
                $error = "Usuario y contraseña son obligatorios para crear.";
            } else {
                $check = $conn->prepare("SELECT usuario FROM usuarios WHERE usuario = ?");
                $check->bind_param("s", $nuevoUsuario);
                $check->execute();
                $exists = $check->get_result();

                if ($exists->num_rows > 0) {
                    $error = "El usuario ya existe.";
                } else {
                    $hash = sha1($nuevaPassword);
                    // Actualizamos el INSERT para incluir el rol
                    $stmt = $conn->prepare("INSERT INTO usuarios (usuario, password, rol) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $nuevoUsuario, $hash, $nuevoRol);

                    if ($stmt->execute()) {
                        // Integración multiOTP silenciosa (como en register.php, pero sin mostrar QR)
                        $app_dir = __DIR__;
                        $multiotp_dir = realpath($app_dir . '/../multiotp_5.10.2.2/windows');
                        if ($multiotp_dir) {
                            chdir($multiotp_dir);
                            $cmd_create = escapeshellarg("multiotp.exe") . " -fastcreatenopin " . escapeshellarg($nuevoUsuario);
                            exec($cmd_create);
                            chdir($app_dir);
                        }
                        $mensaje = "Usuario creado correctamente (Sin QR. El usuario debe usar reset para obtenerlo).";
                        // Registrar auditoría de creación
                        registrarAuditoria($conn, 'ABM Usuarios', 'Crear', 'Se creó el usuario: ' . $nuevoUsuario . ' con rol: ' . $nuevoRol);
                    } else {
                        $error = "No se pudo crear el usuario.";
                    }
                }
            }
        }

        if ($tipo === "editar") {
            $usuarioOriginal = isset($_POST["usuario_original"]) ? trim($_POST["usuario_original"]) : "";
            $usuarioNuevo = isset($_POST["usuario"]) ? trim($_POST["usuario"]) : "";
            $passwordNueva = isset($_POST["password"]) ? $_POST["password"] : "";
            $rolNuevo = isset($_POST["rol"]) ? $_POST["rol"] : "user"; // Capturamos el nuevo rol

            if ($usuarioOriginal === "" || $usuarioNuevo === "") {
                $error = "Datos incompletos para editar el usuario.";
            } else {
                if ($usuarioOriginal !== $usuarioNuevo) {
                    $check = $conn->prepare("SELECT usuario FROM usuarios WHERE usuario = ?");
                    $check->bind_param("s", $usuarioNuevo);
                    $check->execute();
                    $exists = $check->get_result();

                    if ($exists->num_rows > 0) {
                        $error = "El nuevo nombre de usuario ya está en uso.";
                    }
                }

                if ($error === "") {
                    if ($passwordNueva !== "") {
                        $hash = sha1($passwordNueva);
                        // Actualizamos el UPDATE para incluir el rol
                        $stmt = $conn->prepare("UPDATE usuarios SET usuario = ?, password = ?, rol = ? WHERE usuario = ?");
                        $stmt->bind_param("ssss", $usuarioNuevo, $hash, $rolNuevo, $usuarioOriginal);
                    } else {
                        // Actualizamos el UPDATE para incluir el rol
                        $stmt = $conn->prepare("UPDATE usuarios SET usuario = ?, rol = ? WHERE usuario = ?");
                        $stmt->bind_param("sss", $usuarioNuevo, $rolNuevo, $usuarioOriginal);
                    }

                    if ($stmt->execute()) {
                        $mensaje = "Usuario actualizado correctamente.";
                        // Registrar auditoría de edición
                        registrarAuditoria($conn, 'ABM Usuarios', 'Editar', 'Se actualizó el usuario: ' . $usuarioOriginal . ' a: ' . $usuarioNuevo . ' con rol: ' . $rolNuevo);
                    } else {
                        $error = "No se pudo actualizar el usuario.";
                    }
                }
            }
        }

        if ($tipo === "eliminar") {
            $usuarioEliminar = isset($_POST["usuario"]) ? trim($_POST["usuario"]) : "";

            if ($usuarioEliminar === "") {
                $error = "No se indicó el usuario a eliminar.";
            } elseif ($usuarioEliminar === $_SESSION["usuario"]) {
                $error = "No puedes eliminar tu propio usuario de administrador.";
            } else {
                $stmt = $conn->prepare("DELETE FROM usuarios WHERE usuario = ?");
                $stmt->bind_param("s", $usuarioEliminar);

                if ($stmt->execute()) {
                    // Eliminamos también del servidor multiOTP
                    $app_dir = __DIR__;
                    $multiotp_dir = realpath($app_dir . '/../multiotp_5.10.2.2/windows');
                    if ($multiotp_dir) {
                        chdir($multiotp_dir);
                        $cmd_delete = escapeshellarg("multiotp.exe") . " -delete " . escapeshellarg($usuarioEliminar);
                        exec($cmd_delete);
                        chdir($app_dir);
                    }
                    $mensaje = "Usuario eliminado correctamente.";
                    // Registrar auditoría de eliminación
                    registrarAuditoria($conn, 'ABM Usuarios', 'Eliminar', 'Se eliminó el usuario: ' . $usuarioEliminar);
                } else {
                    $error = "No se pudo eliminar el usuario.";
                }
            }
        }
        // --- FIN DE ACCIONES ABM ---
    }
}

$usuarioEdicion = "";
$rolEdicion = "user"; // Por defecto
if ($accion === "editar" && isset($_GET["usuario"])) {
    $usuarioEdicion = trim($_GET["usuario"]);
    // Buscar el rol actual del usuario a editar
    $stmt_rol = $conn->prepare("SELECT rol FROM usuarios WHERE usuario = ?");
    $stmt_rol->bind_param("s", $usuarioEdicion);
    $stmt_rol->execute();
    $stmt_rol->bind_result($rol_db);
    if ($stmt_rol->fetch()) {
        $rolEdicion = $rol_db;
    }
    $stmt_rol->close();
}

$usuarios = [];
$q = $conn->query("SELECT usuario, rol FROM usuarios ORDER BY usuario ASC");
if ($q) {
    while ($row = $q->fetch_assoc()) {
        $usuarios[] = $row; // Ahora guardamos un array con usuario y rol
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ABM de Usuarios</title>
    <style>
        :root {
            --bg: #f3f7fb;
            --card: #ffffff;
            --text: #1b2a41;
            --muted: #5f6c80;
            --accent: #0f766e;
            --accent-dark: #115e59;
            --danger: #b91c1c;
            --danger-dark: #991b1b;
            --border: #d9e2ec;
            --ok-bg: #dcfce7;
            --ok-text: #166534;
            --err-bg: #fee2e2;
            --err-text: #991b1b;
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
            max-width: 920px;
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
        }

        .btn-main {
            background: var(--accent);
        }

        .btn-main:hover {
            background: var(--accent-dark);
        }

        .btn-danger {
            background: var(--danger);
        }

        .btn-danger:hover {
            background: var(--danger-dark);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        th,
        td {
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .top-links {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        form.inline {
            display: inline;
        }

        /* Estilos del Modal OTP */
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

    <!-- Modal OTP (Oculto por defecto) -->
    <div id="otpModal" class="modal-overlay">
        <div class="modal-content">
            <h3>Autenticación Requerida</h3>
            <p style="font-size: 0.9rem; color: var(--muted);">Ingrese su código OTP de Administrador para confirmar esta acción.</p>
            <input type="text" id="otpInput" placeholder="000000" pattern="[0-9]{6}" maxlength="6">
            <div class="btn-group">
                <button class="btn btn-main" onclick="submitConOTP()">Confirmar</button>
                <button class="btn btn-danger" onclick="cerrarModal()">Cancelar</button>
            </div>
        </div>
    </div>

    <div class="wrap">
        <section class="card">
            <h1>ABM de usuarios</h1>
            <p>Gestión segura de altas, bajas y modificaciones.</p>
            <div class="top-links">
                <a class="btn btn-main" href="dashboard.php">Volver al dashboard</a>
            </div>
        </section>

        <section class="card">
            <h2><?php echo ($accion === "editar" && $usuarioEdicion !== "") ? "Editar usuario" : "Crear usuario"; ?></h2>

            <?php if ($mensaje !== ""): ?> <div class="msg"><?php echo htmlspecialchars($mensaje); ?></div> <?php endif; ?>
            <?php if ($error !== ""): ?> <div class="err"><?php echo htmlspecialchars($error); ?></div> <?php endif; ?>

            <?php if ($accion === "editar" && $usuarioEdicion !== ""): ?>
                <form id="formAccion" method="POST" action="usuarios.php">
                    <input type="hidden" name="tipo" value="editar">
                    <input type="hidden" name="usuario_original" value="<?php echo htmlspecialchars($usuarioEdicion); ?>">
                    <!-- Campo oculto para el OTP -->
                    <input type="hidden" name="otp_admin" id="otpOculto" value="">

                    <div class="grid">
                        <div>
                            <label for="usuario">Usuario</label>
                            <input id="usuario" type="text" name="usuario" value="<?php echo htmlspecialchars($usuarioEdicion); ?>" required>
                        </div>
                        <div>
                            <label for="password">Nueva contraseña (opcional)</label>
                            <input id="password" type="password" name="password" placeholder="Dejar vacío para no cambiar">
                        </div>
                        <!-- Nuevo selector de ROL -->
                        <div>
                            <label for="rol">Rol del Usuario</label>
                            <select name="rol" id="rol">
                                <option value="user" <?php echo ($rolEdicion == 'user') ? 'selected' : ''; ?>>Usuario Estándar</option>
                                <option value="admin" <?php echo ($rolEdicion == 'admin') ? 'selected' : ''; ?>>Administrador</option>
                            </select>
                        </div>
                    </div>

                    <div class="top-links">
                        <button class="btn btn-main" type="button" onclick="abrirModal(this.form)">Guardar cambios</button>
                        <a class="btn btn-danger" href="usuarios.php">Cancelar</a>
                    </div>
                </form>
            <?php else: ?>
                <form id="formAccion" method="POST" action="usuarios.php">
                    <input type="hidden" name="tipo" value="crear">
                    <!-- Campo oculto para el OTP -->
                    <input type="hidden" name="otp_admin" id="otpOculto" value="">

                    <div class="grid">
                        <div>
                            <label for="usuario">Usuario</label>
                            <input id="usuario" type="text" name="usuario" required>
                        </div>
                        <div>
                            <label for="password">Contraseña</label>
                            <input id="password" type="password" name="password" required>
                        </div>
                        <!-- Nuevo selector de ROL -->
                        <div>
                            <label for="rol">Rol del Usuario</label>
                            <select name="rol" id="rol">
                                <option value="user" selected>Usuario Estándar</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                    </div>

                    <div class="top-links">
                        <button class="btn btn-main" type="button" onclick="abrirModal(this.form)">Crear usuario</button>
                    </div>
                </form>
            <?php endif; ?>
        </section>

        <section class="card">
            <h2>Usuarios registrados</h2>

            <?php if (count($usuarios) === 0): ?>
                <p>No hay usuarios cargados.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($u['usuario']); ?></td>
                                <td>
                                    <!-- Mostrar el rol con un color diferente si es admin -->
                                    <?php if ($u['rol'] === 'admin'): ?>
                                        <span style="color: var(--danger); font-weight:bold;">Administrador</span>
                                    <?php else: ?>
                                        Usuario
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <a class="btn btn-main" href="usuarios.php?accion=editar&usuario=<?php echo urlencode($u['usuario']); ?>">Editar</a>
                                    <form class="inline" method="POST" action="usuarios.php">
                                        <input type="hidden" name="tipo" value="eliminar">
                                        <input type="hidden" name="usuario" value="<?php echo htmlspecialchars($u['usuario']); ?>">
                                        <!-- Campo oculto para el OTP -->
                                        <input type="hidden" name="otp_admin" class="otpEliminar" value="">
                                        <button class="btn btn-danger" type="button" onclick="abrirModal(this.form, '¿Seguro que deseas eliminar a este usuario?')">Eliminar</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </div>

    <script>
        let formActual = null;

        function abrirModal(formulario, mensajeConfirmacion = null) {
            // Si hay mensaje (ej. para eliminar) usamos confirm nativo primero como barrera
            if (mensajeConfirmacion && !confirm(mensajeConfirmacion)) {
                return;
            }

            // Guardar referencia del formulario que disparó el modal
            formActual = formulario;

            // Mostrar modal y enfocar input
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
                alert('Por favor ingrese un código OTP válido de 6 dígitos.');
                return;
            }

            // Buscar el input oculto de OTP dentro del formulario actual
            let inputOculto = formActual.querySelector('input[name="otp_admin"]');
            if (inputOculto) {
                inputOculto.value = otpValue;
                formActual.submit(); // Enviamos el formulario original con el OTP inyectado
            }
        }
    </script>
</body>

</html>