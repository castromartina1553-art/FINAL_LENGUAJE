<?php
$host = '127.0.0.1';
$port = '3306';
$db = 'sistema_php';
$user = 'root';
$pass = '';

$conexion = new PDO(
    "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
    $user,
    $pass,
    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
);
$conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);