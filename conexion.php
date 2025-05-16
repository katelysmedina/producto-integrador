<?php
$host = "localhost"; 
$dbname = "usuarios_database"; 
$username = "root";
$password = "blesseD111."; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    die("Error en la conexión: " . $e->getMessage());
}
?>

