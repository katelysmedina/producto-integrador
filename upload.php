<?php 
session_start();
require 'conexion.php';

if (!isset($_SESSION["usuario"])) {
    echo "<p>No has iniciado sesión.</p>";
    exit();
}

$usuario_id = $_SESSION["usuario"]; 
$rol = $_SESSION["rol"];
$nombre_usuario = $_SESSION["nombre"]; 
$directorio_base = "uploads/";

if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit();
}

// Subir imagen
if ($rol === "usuario" && isset($_POST['upload'])) {
    $directorio_usuario = $directorio_base . $usuario_id . "/";
    if (!is_dir($directorio_usuario)) {
        mkdir($directorio_usuario, 0777, true);
    }

    $archivo_tmp = $_FILES["archivo"]["tmp_name"];
    $archivo_nombre = basename($_FILES["archivo"]["name"]);
    $archivo_destino = $directorio_usuario . $archivo_nombre;

    $tipos_permitidos = ['image/jpeg', 'image/png'];
    $tipo_archivo = mime_content_type($archivo_tmp);
    $es_imagen = getimagesize($archivo_tmp);

    if (!in_array($tipo_archivo, $tipos_permitidos) || $es_imagen === false) {
        echo "Solo se permiten imágenes JPG o PNG.";
        exit();
    }

    if (!move_uploaded_file($archivo_tmp, $archivo_destino)) {
        echo "Hubo un error al subir la imagen.";
    } else {
        $ruta_relativa = $usuario_id . "/" . $archivo_nombre;
        $stmt = $pdo->prepare("INSERT INTO imagenes (usuario_id, ruta) VALUES (?, ?)");
        $stmt->execute([$usuario_id, $ruta_relativa]);
    }
}
// Eliminar img

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['eliminar'])) {
    $ruta_relativa = $_POST['eliminar'];
    $ruta = $directorio_base . $ruta_relativa;

    if (strpos($ruta_relativa, $usuario_id . '/') === 0) {
        if (file_exists($ruta)) {
            unlink($ruta);
        }

        $stmt = $pdo->prepare("DELETE FROM imagenes WHERE usuario_id = ? AND ruta = ?");
        $stmt->execute([$usuario_id, $ruta_relativa]);

        header("Location: upload.php");
        exit();
    }}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subir imagen</title>
    <link rel="stylesheet" href="styles/styles.css">
    <script src="https://unpkg.com/feather-icons"></script> 
</head>
<body class="body-upload">

<div class="layout-principal">
    <div class="columna-izquierda">
        <h1 class="titulo2">¡Hola, <?php echo htmlspecialchars($_SESSION["nombre"]); ?>!</h1>
        <p class="subtitulo">¡Estás a un paso de subir tus imágenes!</p>

        <div class="contenedor-upload">
            <form id="formulario" class="formulario" action="upload.php" method="post" enctype="multipart/form-data">
                <div class="area-subir" id="area-subir">
                    <input type="file" id="archivo" class="input-archivo" name="archivo" accept="image/*" required>
                    <label for="archivo" class="boton-archivo">
                        <i data-feather="upload"></i>
                    </label>
                    <p class="p-texto">Selecciona tu imagen</p>
                    <p class="p-peso">Formato JPEG o PNG</p>
                    <span id="nombre-archivo" class="nombre-archivo">Ninguna imagen seleccionada</span>
                </div>
                <button type="submit" class="boton-enviar" name="upload">Subir imagen</button>
            </form>
        </div>
    </div>

    <div class="contenedor-galeria" id="contenedor-archivos">
    <ul>
    <?php
    $stmt = $pdo->prepare("SELECT ruta FROM imagenes WHERE usuario_id = ? ORDER BY id DESC");
    $stmt->execute([$usuario_id]);
    $imagenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($imagenes)) {
        echo "<div class='mensaje-vacio-wrapper'><p class='mensaje-vacio'>No has subido ninguna imagen</p><i data-feather='frown' width='16' height='16'></i>";
    } else {
        foreach ($imagenes as $img) {
            $ruta_relativa = htmlspecialchars($img['ruta']);
            echo "<li class='item-galeria'>
                <div class='galeria-contenedor-img'>
                    <img src='$directorio_base$ruta_relativa' alt='imagen' class='imagen-galeria'>
                    <a href='$directorio_base$ruta_relativa' target='_blank' class='icono-central'>
                        <i data-feather='external-link' width='16' height='16'></i>
                    </a>
                    <form method='POST' action='upload.php' class='form-upload'>
                        <input type='hidden' name='eliminar' value='$ruta_relativa'>
                        <button type='submit' class='icono-cerrar'>
                            <i data-feather='x' width='16' height='16'></i>
                        </button>
                    </form>
                </div>
            </li>";
        }
    }
    ?>
    </ul>
</div>
</div>

<form method="GET" action="upload.php" class="cerrar-sesion-form">
    <button type="submit" name="logout" value="true" class="cerrar-sesion-btn">
        Cerrar sesión <i data-feather='log-out' width='15' height='15'></i>
    </button>
</form>

<p class="copyright">Diseñado por Kately S. Medina &copy; 2025</p>

<script>
    feather.replace(); 
    function nombreArchivo() {
        var archivo = document.getElementById('archivo'); 
        var nombreArchivo = archivo.files[0]?.name || 'Ninguna imagen seleccionada'; 
        document.getElementById('nombre-archivo').textContent = nombreArchivo; 
    }
    document.getElementById('archivo')?.addEventListener('change', nombreArchivo);
    document.querySelectorAll('.form-upload').forEach(form => {
        form.addEventListener('submit', function (e) {
            const confirmar = confirm("¿Estás seguro de que deseas eliminar esta imagen?");
            if (!confirmar) {
                e.preventDefault();
            }
        });
    });
</script>

</body>
</html>
