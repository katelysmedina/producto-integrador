<?php // Formularios de inicio sesión/registro 
session_start();
require 'conexion.php'; 

$pagina = $_GET['pagina'] ?? 'login';
$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registro'])) {
    include 'auth.php';  
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $correo = trim($_POST['correo']);
    $contrasena = trim($_POST['contrasena']);
    $nombreIngresado = trim($_POST['nombre']);

    $query = "SELECT * FROM usuarios WHERE correo = ?";
    $stmt = $pdo->prepare($query);  
    $stmt->bindParam(1, $correo, PDO::PARAM_STR);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($contrasena, $usuario['contrasena'])) {
        $_SESSION["usuario"] = $usuario['id'];  
        $_SESSION["rol"] = $usuario['rol'];  

        if (!empty($nombreIngresado)) {
            $_SESSION["nombre"] = $nombreIngresado;
        } else {
            $_SESSION["nombre"] = explode('@', $correo)[0]; 
        }

        if ($usuario['rol'] === 'admin') {
            header("Location: panel.php");
            exit();
        } else {
            header("Location: upload.php");
            exit();
        }
    } else {
        $_SESSION['mensaje'] = "Usuario o contraseña incorrectos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Producto Integrador</title>
    <link rel="stylesheet" href="styles/styles.css">
</head>
<body class="body-index">
<div class="contenedor">
    <div class="form-container" id="form-container">
        <?php if ($pagina === 'registro'): ?>
            <h1>Regístrate</h1>
            <h2>Crea una cuenta gratis para comenzar</h2>
            <form method="post" action="?pagina=registro" id="form">
                <div class="input-grupo">
                    <label for="correo">Correo electrónico *</label>
                    <input type="email" name="correo" id="correo" required>
                </div>
                <div class="input-grupo">
                    <label for="contrasena">Contraseña *</label>
                    <div class="contrasena-contenedor">
                        <input type="password" name="contrasena" id="contrasena" required>
                        <i data-feather="eye" class="contrasena" onclick="contrasena('contrasena', this)"></i>
                    </div>
                </div>
                <div class="input-grupo">
                    <label for="confirmar_contrasena">Confirmar contraseña *</label>
                    <div class="contrasena-contenedor">
                        <input type="password" name="confirmar_contrasena" id="confirmar_contrasena" required>
                        <i data-feather="eye" class="contrasena" onclick="contrasena('confirmar_contrasena', this)"></i>
                    </div>
                </div>
                <div class="registro">
                    <p class="p-registro">¿Ya tienes una cuenta? <a href="?pagina=login" class="enlace-registro">Inicia sesión</a></p>
                </div>
                <button type="submit" name="registro" id="continuar">Continuar</button>
                <div id="mensaje"><?= $mensaje ?></div>
            </form>
            <?php
                if (isset($_SESSION['mensaje'])) {
                echo $_SESSION['mensaje'];
                unset($_SESSION['mensaje']); 
                }
                ?>

        <?php else: ?>
            <h1>Inicia sesión</h1>
            <h2>Ingresa tus datos para acceder a tu cuenta</h2>
            <form method="post" action="?pagina=login" id="form">
            <div class="input-grupo">
                    <label for="nombre">Nombre *</label>
                    <input type="text" name="nombre" id="nombre" required>
                </div> 
                <div class="input-grupo">
                    <label for="correo">Correo electrónico *</label>
                    <input type="email" name="correo" id="correo" required>
                </div>
                <div class="input-grupo">
                    <label for="contrasena">Contraseña *</label>
                    <div class="contrasena-contenedor">
                        <input type="password" name="contrasena" id="contrasena" required>
                        <i data-feather="eye" class="contrasena" onclick="contrasena('contrasena', this)"></i>
                    </div>
                </div>
                <div class="registro">
                    <p class="p-registro">¿No tienes una cuenta? <a href="?pagina=registro" class="enlace-registro">Regístrate ahora</a></p>
                </div>
                <button type="submit" name="login" id="continuar">Continuar</button>
                <?php
                if (isset($_SESSION['mensaje'])) {
                echo $_SESSION['mensaje'];
                unset($_SESSION['mensaje']); 
                }
                ?>
            </form>
        <?php endif; ?>

        <p class="copyright">Diseñado por Kately S. Medina &copy; 2025</p>
    </div>

    <div class="img">
        <img src="styles/img.jpg" />
    </div>
</div>

<script src="https://unpkg.com/feather-icons"></script>
<script>
    feather.replace();

    function contrasena(id, icon) {
        const input = document.getElementById(id);
        if (input.type === "password") {
            input.type = "text";
            icon.setAttribute("data-feather", "eye-off");
        } else {
            input.type = "password";
            icon.setAttribute("data-feather", "eye");
        }
        feather.replace();
    }
</script>
</body>
</html>
