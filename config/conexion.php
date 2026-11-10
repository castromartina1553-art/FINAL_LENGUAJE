<?php
$host = "127.0.0.1";
$port = "3309"; 
$db   = "sistema_php";
$user = "root";
$pass = "lenguaje2026";

try {
    $conexion = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión a MySQL Docker: " . $e->getMessage());
}
?>