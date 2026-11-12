<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode(array(
        'success' => false,
        'message' => 'Método no permitido.'
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
if (!session_start()) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => 'No se pudo acceder a la sesión.'
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

$_SESSION = array();
$cookie = session_get_cookie_params();
if (ini_get('session.use_cookies')) {
    setcookie(session_name(), '', array(
        'expires' => time() - 42000,
        'path' => $cookie['path'],
        'domain' => $cookie['domain'],
        'secure' => $cookie['secure'],
        'httponly' => $cookie['httponly'],
        'samesite' => isset($cookie['samesite']) ? $cookie['samesite'] : 'Lax'
    ));
}

if (!session_destroy()) {
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'message' => 'No se pudo cerrar la sesión.'
    ), JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(array(
    'success' => true,
    'message' => 'Sesión cerrada.'
), JSON_UNESCAPED_UNICODE);
