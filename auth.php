<?php
session_start();
require 'conexion.php'; 

$mensaje = "";

if (isset($_POST['registro'])) {
    $correo = trim($_POST['correo']);
    $contrasena = trim($_POST['contrasena']);
    $confirmarContrasena = trim($_POST['confirmar_contrasena']);

    if ($contrasena !== $confirmarContrasena) {
        $mensaje = "<p class='error'>Las contraseñas no coinciden</p>";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = ?");
        $stmt->execute([$correo]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            $mensaje = "<p class='error'>Usuario ya registrado. Inicie sesión</p>";
        } else {
            $hashed_password = password_hash($contrasena, PASSWORD_DEFAULT); 
            $stmt = $pdo->prepare("INSERT INTO usuarios (correo, contrasena) VALUES (?, ?)");
            $stmt->execute([$correo, $hashed_password]);
            $mensaje = "<p class='valido'>Usuario registrado con éxito. Inicie sesión</p>";
        }
    }
    
    $_SESSION['mensaje'] = $mensaje;
    header("Location: index.php?pagina=registro");
    exit();
}

if (isset($_POST['login'])) {
    $correo = trim($_POST['correo']);
    $contrasena = trim($_POST['contrasena']);

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = ?");
    $stmt->execute([$correo]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        if (password_verify($contrasena, $usuario['contrasena'])) {
            $_SESSION["usuario"] = $usuario['id'];
            $_SESSION["correo"] = $correo;
            $_SESSION["rol"] = $usuario['rol'];
            $_SESSION["nombre"] = $_POST["nombre"]; 


            header("Location: upload.php"); 
            exit();
        } else {
            $_SESSION['mensaje'] = "<p class='error'>La contraseña es incorrecta</p>";
            header("Location: index.php?pagina=login");
            exit();
        }
    } else {
        $_SESSION['mensaje'] = "<p class='error'>El correo no está registrado</p>";
        header("Location: index.php?pagina=login"); 
        exit();
    }
}
