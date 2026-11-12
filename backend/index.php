<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responder($estado, array $datos)
{
    http_response_code($estado);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    responder(405, array(
        'success' => false,
        'message' => 'Método no permitido.'
    ));
}

$tipoContenido = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
if (stripos($tipoContenido, 'application/json') !== false) {
    $contenido = file_get_contents('php://input');
    $datos = json_decode($contenido, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($datos)) {
        responder(400, array(
            'success' => false,
            'message' => 'El cuerpo JSON no es válido.'
        ));
    }
} else {
    $datos = $_POST;
}

$email = isset($datos['email']) && is_string($datos['email'])
    ? trim($datos['email'])
    : '';
$password = isset($datos['password']) && is_string($datos['password'])
    ? $datos['password']
    : '';

if ($email === '' || strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || $password === '' || strlen($password) > 4096) {
    responder(400, array(
        'success' => false,
        'message' => 'Ingresá un email y una contraseña válidos.'
    ));
}

try {
    require_once __DIR__ . '/conexion.php';
    $consulta = $conexion->prepare(
        'SELECT u.ID, u.NOMBRE, u.APELLIDO, u.EMAIL, u.PASSWORD,
                u.TIPO_USUARIO_ID, t.ROLES
         FROM USUARIOS AS u
         LEFT JOIN TIPO_USUARIO AS t ON t.ID = u.TIPO_USUARIO_ID
         WHERE u.EMAIL = :email
         LIMIT 1'
    );
    $consulta->execute(array('email' => $email));
    $usuario = $consulta->fetch();
} catch (PDOException $e) {
    error_log('Error en el inicio de sesión: ' . $e->getMessage());
    responder(500, array(
        'success' => false,
        'message' => 'No se pudo procesar el inicio de sesión.'
    ));
}

if (!$usuario) {
    responder(401, array(
        'success' => false,
        'message' => 'Email o contraseña incorrectos.'
    ));
}

$passwordCorrecta = password_verify($password, $usuario['PASSWORD']);
if (!$passwordCorrecta && hash_equals($usuario['PASSWORD'], $password)) {
    $passwordCorrecta = true;
}
if (!$passwordCorrecta) {
    responder(401, array(
        'success' => false,
        'message' => 'Email o contraseña incorrectos.'
    ));
}

if (!password_verify($password, $usuario['PASSWORD'])
    || password_needs_rehash($usuario['PASSWORD'], PASSWORD_DEFAULT)) {
    try {
        $actualizacion = $conexion->prepare(
            'UPDATE USUARIOS SET PASSWORD = :password WHERE ID = :id'
        );
        $actualizacion->execute(array(
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'id' => $usuario['ID']
        ));
    } catch (PDOException $e) {
        error_log('Error al actualizar la contraseña: ' . $e->getMessage());
        responder(500, array(
            'success' => false,
            'message' => 'No se pudo actualizar la contraseña de la cuenta.'
        ));
    }
}

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params(array(
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
    'path' => '/'
));

if (!session_start()) {
    responder(500, array(
        'success' => false,
        'message' => 'No se pudo iniciar la sesión.'
    ));
}

session_regenerate_id(true);
$_SESSION['usuario'] = array(
    'id' => (int) $usuario['ID'],
    'nombre' => $usuario['NOMBRE'],
    'apellido' => $usuario['APELLIDO'],
    'email' => $usuario['EMAIL'],
    'tipo_usuario_id' => $usuario['TIPO_USUARIO_ID'] === null
        ? null
        : (int) $usuario['TIPO_USUARIO_ID'],
    'rol' => $usuario['ROLES']
);

responder(200, array(
    'success' => true,
    'message' => 'Inicio de sesión exitoso.',
    'usuario' => $_SESSION['usuario']
));