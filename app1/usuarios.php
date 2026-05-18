<?php
session_start();
require_once "conexion.php";

if (!isset($_SESSION["usuario"])) {
    header("Location: login.php");
    exit();
}

$accion = isset($_GET["accion"]) ? $_GET["accion"] : "";
$mensaje = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $tipo = isset($_POST["tipo"]) ? $_POST["tipo"] : "";

    if ($tipo === "crear") {
        $nuevoUsuario = isset($_POST["usuario"]) ? trim($_POST["usuario"]) : "";
        $nuevaPassword = isset($_POST["password"]) ? $_POST["password"] : "";

        if ($nuevoUsuario === "" || $nuevaPassword === "") {
            $error = "Usuario y contrasena son obligatorios para crear.";
        } else {
            $check = $conn->prepare("SELECT usuario FROM usuarios WHERE usuario = ?");
            $check->bind_param("s", $nuevoUsuario);
            $check->execute();
            $exists = $check->get_result();

            if ($exists->num_rows > 0) {
                $error = "El usuario ya existe.";
            } else {
                $hash = sha1($nuevaPassword);
                $stmt = $conn->prepare("INSERT INTO usuarios (usuario, password) VALUES (?, ?)");
                $stmt->bind_param("ss", $nuevoUsuario, $hash);

                if ($stmt->execute()) {
                    $mensaje = "Usuario creado correctamente.";
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

        if ($usuarioOriginal === "" || $usuarioNuevo === "") {
            $error = "Datos incompletos para editar el usuario.";
        } else {
            if ($usuarioOriginal !== $usuarioNuevo) {
                $check = $conn->prepare("SELECT usuario FROM usuarios WHERE usuario = ?");
                $check->bind_param("s", $usuarioNuevo);
                $check->execute();
                $exists = $check->get_result();

                if ($exists->num_rows > 0) {
                    $error = "El nuevo nombre de usuario ya esta en uso.";
                }
            }

            if ($error === "") {
                if ($passwordNueva !== "") {
                    $hash = sha1($passwordNueva);
                    $stmt = $conn->prepare("UPDATE usuarios SET usuario = ?, password = ? WHERE usuario = ?");
                    $stmt->bind_param("sss", $usuarioNuevo, $hash, $usuarioOriginal);
                } else {
                    $stmt = $conn->prepare("UPDATE usuarios SET usuario = ? WHERE usuario = ?");
                    $stmt->bind_param("ss", $usuarioNuevo, $usuarioOriginal);
                }

                if ($stmt->execute()) {
                    $mensaje = "Usuario actualizado correctamente.";
                } else {
                    $error = "No se pudo actualizar el usuario.";
                }
            }
        }
    }

    if ($tipo === "eliminar") {
        $usuarioEliminar = isset($_POST["usuario"]) ? trim($_POST["usuario"]) : "";

        if ($usuarioEliminar === "") {
            $error = "No se indico el usuario a eliminar.";
        } elseif ($usuarioEliminar === $_SESSION["usuario"]) {
            $error = "No puedes eliminar el usuario de la sesion actual.";
        } else {
            $stmt = $conn->prepare("DELETE FROM usuarios WHERE usuario = ?");
            $stmt->bind_param("s", $usuarioEliminar);

            if ($stmt->execute()) {
                $mensaje = "Usuario eliminado correctamente.";
            } else {
                $error = "No se pudo eliminar el usuario.";
            }
        }
    }
}

$usuarioEdicion = "";
if ($accion === "editar" && isset($_GET["usuario"])) {
    $usuarioEdicion = trim($_GET["usuario"]);
}

$usuarios = [];
$q = $conn->query("SELECT usuario FROM usuarios ORDER BY usuario ASC");
if ($q) {
    while ($row = $q->fetch_assoc()) {
        $usuarios[] = $row["usuario"];
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

        h1, h2 { margin: 0 0 12px; }

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

        input {
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

        .btn-main { background: var(--accent); }
        .btn-main:hover { background: var(--accent-dark); }
        .btn-danger { background: var(--danger); }
        .btn-danger:hover { background: var(--danger-dark); }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        th, td {
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

        form.inline { display: inline; }
    </style>
</head>
<body>
<div class="wrap">
    <section class="card">
        <h1>ABM de usuarios</h1>
        <p>Gestion simple de altas, bajas y modificaciones.</p>
        <div class="top-links">
            <a class="btn btn-main" href="dashboard.php">Volver al dashboard</a>
            <a class="btn btn-main" href="logout.php">Cerrar sesion</a>
        </div>
    </section>

    <section class="card">
        <h2><?php echo ($accion === "editar" && $usuarioEdicion !== "") ? "Editar usuario" : "Crear usuario"; ?></h2>

        <?php if ($mensaje !== ""): ?>
            <div class="msg"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>

        <?php if ($error !== ""): ?>
            <div class="err"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($accion === "editar" && $usuarioEdicion !== ""): ?>
            <form method="POST" action="usuarios.php">
                <input type="hidden" name="tipo" value="editar">
                <input type="hidden" name="usuario_original" value="<?php echo htmlspecialchars($usuarioEdicion); ?>">

                <div class="grid">
                    <div>
                        <label for="usuario">Usuario</label>
                        <input id="usuario" type="text" name="usuario" value="<?php echo htmlspecialchars($usuarioEdicion); ?>" required>
                    </div>
                    <div>
                        <label for="password">Nueva contrasena (opcional)</label>
                        <input id="password" type="password" name="password" placeholder="Dejar vacio para no cambiar">
                    </div>
                </div>

                <div class="top-links">
                    <button class="btn btn-main" type="submit">Guardar cambios</button>
                    <a class="btn btn-main" href="usuarios.php">Cancelar</a>
                </div>
            </form>
        <?php else: ?>
            <form method="POST" action="usuarios.php">
                <input type="hidden" name="tipo" value="crear">

                <div class="grid">
                    <div>
                        <label for="usuario">Usuario</label>
                        <input id="usuario" type="text" name="usuario" required>
                    </div>
                    <div>
                        <label for="password">Contrasena</label>
                        <input id="password" type="password" name="password" required>
                    </div>
                </div>

                <div class="top-links">
                    <button class="btn btn-main" type="submit">Crear usuario</button>
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
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($u); ?></td>
                            <td class="actions">
                                <a class="btn btn-main" href="usuarios.php?accion=editar&usuario=<?php echo urlencode($u); ?>">Editar</a>
                                <form class="inline" method="POST" action="usuarios.php" onsubmit="return confirm('Seguro que deseas eliminar este usuario?');">
                                    <input type="hidden" name="tipo" value="eliminar">
                                    <input type="hidden" name="usuario" value="<?php echo htmlspecialchars($u); ?>">
                                    <button class="btn btn-danger" type="submit">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</div>
</body>
</html>
