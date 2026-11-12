<?php
$acciones = array(
    'GET' => 'listar.php',
    'POST' => 'crear.php',
    'PUT' => 'editar.php',
    'PATCH' => 'editar.php',
    'DELETE' => 'eliminar.php'
);
$metodo = $_SERVER['REQUEST_METHOD'];

if (!isset($acciones[$metodo])) {
    header('Allow: GET, POST, PUT, PATCH, DELETE');
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(405);
    echo json_encode(array('success' => false, 'message' => 'Método no permitido.'), JSON_UNESCAPED_UNICODE);
    exit;
}

require __DIR__ . '/' . $acciones[$metodo];
