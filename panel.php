<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION["usuario"]) || $_SESSION["rol"] !== "admin") {
    echo "<p>No tienes permiso para acceder a esta página.</p>";
    exit();
}

//Eliminar
if (isset($_POST['eliminar'])) {
    $id = $_POST['eliminar'];
    $stmt = $pdo->prepare("SELECT ruta FROM imagenes WHERE usuario_id = ?");
    $stmt->execute([$id]);
    $imagenes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($imagenes as $img) {
        $ruta = "uploads/" . $img['ruta'];
        if (file_exists($ruta)) unlink($ruta);
    }
    $pdo->prepare("DELETE FROM imagenes WHERE usuario_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM usuarios WHERE id = ?")->execute([$id]);

    header("Location: panel.php");
    exit();
}

// Editar 
if (isset($_POST['editar'])) {
    $id = $_POST['id'];
    $correo = $_POST['correo'];
    $rol = $_POST['rol'];

    $pdo->prepare("UPDATE usuarios SET correo = ?, rol = ? WHERE id = ?")
        ->execute([$correo, $rol, $id]);

    header("Location: panel.php");
    exit();
}

// Eliminar imagen
if (isset($_POST['eliminar_img'])) {
    $ruta_relativa = $_POST['ruta'];
    $ruta_completa = "uploads/" . $ruta_relativa;

    if (file_exists($ruta_completa)) unlink($ruta_completa);
    $pdo->prepare("DELETE FROM imagenes WHERE ruta = ?")->execute([$ruta_relativa]);

    header("Location: panel.php");
    exit();
}

// Agregar nuevo usuario
if (isset($_POST['crear_usuario'])) {
    $correo = $_POST['nuevo_correo'];
    $clave = $_POST['contrasena'];  
    $rol = $_POST['nuevo_rol'];
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ?");
    $stmt->execute([$correo]);

    if ($stmt->rowCount() > 0) {
        echo "<script>alert('Ese correo ya está registrado.');</script>";
    } else {
        $hash = password_hash($clave, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO usuarios (correo, contrasena, rol) VALUES (?, ?, ?)");
        $stmt->execute([$correo, $hash, $rol]);
        header("Location: panel.php");
        exit();
    }
}

// Obtener usuarios
$stmt = $pdo->query("SELECT id, correo, rol FROM usuarios");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de administración</title>
    <link rel="stylesheet" href="styles/styles.css">
    <script src="https://unpkg.com/feather-icons"></script>
</head>
<body class="body-panel">

<h1 class="titulo-panel">Panel de administración</h1>

<button id="btn-nuevo-usuario" onclick="mostrarFormularioNuevoUsuario()" class="agregar-boton" type="button">
    Agregar usuario 
</button>

<!-- Form nuevo usuario -->
<form method="POST" action="panel.php">
    <table class="tabla-panel">
        <tbody id="fila-nuevo-usuario" style="display: none;">
            <tr class="nuevo-usuario-fila">
                <td>
                    <label>Correo electrónico *</label>
                    <input type="email" name="nuevo_correo" required>
                </td>
                <td>
                    <label>Rol *</label>
                    <select name="nuevo_rol">
                        <option value="usuario">Usuario</option>
                        <option value="admin">Administrador</option>
                    </select>
                </td>
                <td>
                    <label>Contraseña *</label>
                    <input type="password" name="contrasena" required>
                </td>
                <td>
                    <button type="submit" name="crear_usuario" class="agregar-usuario-boton" onclick="return confirmarAccion('¿Estás seguro de que quieres agregar un nuevo usuario?')">Agregar</button>
                </td>
                <td>
                    <button type="button" onclick="cancelarNuevoUsuario()" class="cancelar-usuario-boton" >Cancelar</button>
                </td>
            </tr>
        </tbody>
    </table>
</form>

<table class="tabla-panel">
    <thead>
        <tr>
            <th>ID</th>
            <th>Correo electrónico</th>
            <th>Rol</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($usuarios as $usuario): ?>
        <form method="POST" action="panel.php">
            <tr data-id="<?= $usuario['id']; ?>">
                <td><?= htmlspecialchars($usuario['id']); ?></td>
                <td>
                    <input type="text" name="correo" value="<?= htmlspecialchars($usuario['correo']); ?>" required disabled>
                </td>
                <td>
                    <select name="rol" disabled>
                        <option value="usuario" <?= $usuario['rol'] === 'usuario' ? 'selected' : ''; ?>>Usuario</option>
                        <option value="admin" <?= $usuario['rol'] === 'admin' ? 'selected' : ''; ?>>Administrador</option>
                    </select>
                </td>
                <td class="botones-form">
                    <div class="botones-form"">
                        <button type="button" class="editar-btn" onclick="activarEdicion(<?= $usuario['id']; ?>)" title="Editar" >
                            <i data-feather="edit" width="19px" height="19px"></i>
                        </button>

                        <input type="hidden" name="id" value="<?= $usuario['id']; ?>">
                        
                        <button type="submit" name="editar" class="guardar-btn" title="Guardar" disabled
                        onclick="return confirmarAccion('¿Estás seguro de que quieres guardar los cambios?')">
                        <i data-feather="save" width="19px" height="19px"></i>
                        </button>

                        <button type="submit" name="eliminar" value="<?= $usuario['id']; ?>" class="eliminar-btn"
                        onclick="return confirmarAccion('¿Estás seguro de que quieres eliminar este usuario y todas sus imágenes?')">
                        <i data-feather="trash-2" width="19px" height="19px"></i>
                        </button>

                    </div>
                </td>
            </tr>
        </form>

        <tr>
            <td colspan="4">
                <strong>Imágenes del usuario</strong><br>
                <div class="panel-img-container">
                    <?php
                    $stmtImgs = $pdo->prepare("SELECT ruta FROM imagenes WHERE usuario_id = ?");
                    $stmtImgs->execute([$usuario['id']]);
                    $imgs = $stmtImgs->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($imgs)) {
                        echo "<p class='sin-imagenes-p'>No tiene imágenes</p>";
                    } else {
                        foreach ($imgs as $img) {
                            $ruta_relativa = htmlspecialchars($img['ruta']);
                            $ruta_completa = "uploads/" . $ruta_relativa;
                            echo "<div class='panel-img-wrapper'>
                                <img src='$ruta_completa' alt='img'>
                                <form method='POST' action='panel.php' style='position: absolute; top: 0; right: 0;'>
                                    <input type='hidden' name='ruta' value='$ruta_relativa'>
                                    <button type='submit' name='eliminar_img' class='boton-cerrar-img' title='Eliminar imagen' 
                                    onclick=\"return confirmarAccion('¿Estás seguro de que quieres eliminar esta imagen?')\">
                                    <i data-feather='x'></i>
                                    </button>
                                </form>
                            </div>";
                        }
                    }
                    ?>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<form method="GET" action="upload.php" class="cerrar-sesion-form">
    <button type="submit" name="logout" value="true" class="cerrar-sesion-btn">
         <i data-feather='log-out' width='18' height='18'></i>Cerrar sesión 
    </button>
</form>


<p class="copyright">Diseñado por Kately S. Medina &copy; 2025</p>

<script>
feather.replace();

function mostrarFormularioNuevoUsuario() {
    document.getElementById('fila-nuevo-usuario').style.display = 'table-row';
    document.getElementById('btn-nuevo-usuario').style.display = 'none';
}

function cancelarNuevoUsuario() {
    document.getElementById('fila-nuevo-usuario').style.display = 'none';
    document.getElementById('btn-nuevo-usuario').style.display = 'inline-block';
}

function activarEdicion(id) {
    const fila = document.querySelector(`tr[data-id='${id}']`);
    if (!fila) return;

    const inputs = fila.querySelectorAll('input, select');
    inputs.forEach(el => el.disabled = false);

    const btnGuardar = fila.querySelector("button[name='editar']");
    if (btnGuardar) btnGuardar.disabled = false;
}

function confirmarAccion(mensaje) {
    return confirm(mensaje);
}

</script>

</body>
</html>
