<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responderApi($estado, array $datos)
{
    http_response_code($estado);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit;
}

function leerDatosApi()
{
    $tipoContenido = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    if (stripos($tipoContenido, 'application/json') !== false) {
        $datos = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($datos)) {
            responderApi(400, array('success' => false, 'message' => 'El cuerpo JSON no es válido.'));
        }
        return $datos;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return $_POST;
    }
    $datos = array();
    parse_str(file_get_contents('php://input'), $datos);
    return $datos;
}

function validarDatosApi(array $entrada, array $campos, $esCreacion)
{
    $datos = array();
    foreach ($entrada as $nombre => $valor) {
        if (!isset($campos[$nombre])) {
            responderApi(400, array(
                'success' => false,
                'message' => 'El campo "' . $nombre . '" no está permitido.'
            ));
        }

        $campo = $campos[$nombre];
        if ($valor === null) {
            if (empty($campo['nullable'])) {
                responderApi(400, array(
                    'success' => false,
                    'message' => 'El campo "' . $nombre . '" no puede ser nulo.'
                ));
            }
            $datos[$nombre] = null;
            continue;
        }

        if ($campo['type'] === 'string') {
            if (!is_string($valor)) {
                responderApi(400, array('success' => false, 'message' => 'El campo "' . $nombre . '" debe ser texto.'));
            }
            $valor = trim($valor);
            if ($valor === '' || strlen($valor) > $campo['max']) {
                responderApi(400, array(
                    'success' => false,
                    'message' => 'El campo "' . $nombre . '" está vacío o supera el largo permitido.'
                ));
            }
            if (isset($campo['format']) && $campo['format'] === 'email'
                && !filter_var($valor, FILTER_VALIDATE_EMAIL)) {
                responderApi(400, array('success' => false, 'message' => 'El email no es válido.'));
            }
            $datos[$nombre] = $valor;
            continue;
        }

        if ($campo['type'] === 'password') {
            if (!is_string($valor) || $valor === '' || strlen($valor) > 4096) {
                responderApi(400, array('success' => false, 'message' => 'La contraseña está vacía o es demasiado larga.'));
            }
            $datos[$nombre] = password_hash($valor, PASSWORD_DEFAULT);
            continue;
        }

        if ($campo['type'] === 'int') {
            $numero = filter_var($valor, FILTER_VALIDATE_INT);
            if ($numero === false || $numero < $campo['min']) {
                responderApi(400, array('success' => false, 'message' => 'El campo "' . $nombre . '" debe ser un entero válido.'));
            }
            $datos[$nombre] = $numero;
            continue;
        }

        if ($campo['type'] === 'decimal') {
            if (!is_numeric($valor) || !is_finite((float) $valor)
                || (float) $valor < 0 || (float) $valor > 99999999.99) {
                responderApi(400, array(
                    'success' => false,
                    'message' => 'El campo "' . $nombre . '" debe ser un número válido entre 0 y 99999999.99.'
                ));
            }
            $datos[$nombre] = $valor;
            continue;
        }

        if ($campo['type'] === 'datetime') {
            if (!is_string($valor)) {
                responderApi(400, array('success' => false, 'message' => 'La fecha debe tener el formato YYYY-MM-DD HH:MM:SS.'));
            }
            $valor = str_replace('T', ' ', trim($valor));
            if (strlen($valor) === 16) {
                $valor .= ':00';
            }
            $fecha = DateTime::createFromFormat('!Y-m-d H:i:s', $valor);
            $erroresFecha = DateTime::getLastErrors();
            if (!$fecha || ($erroresFecha !== false
                && ($erroresFecha['warning_count'] > 0 || $erroresFecha['error_count'] > 0))
                || $fecha->format('Y-m-d H:i:s') !== $valor) {
                responderApi(400, array(
                    'success' => false,
                    'message' => 'La fecha debe tener el formato YYYY-MM-DD HH:MM:SS y ser válida.'
                ));
            }
            $datos[$nombre] = $valor;
        }
    }

    if ($esCreacion) {
        foreach ($campos as $nombre => $campo) {
            if (!empty($campo['required']) && !array_key_exists($nombre, $datos)) {
                responderApi(400, array(
                    'success' => false,
                    'message' => 'Falta el campo obligatorio "' . $nombre . '".'
                ));
            }
        }
    } elseif (count($datos) === 0) {
        responderApi(400, array('success' => false, 'message' => 'Indicá al menos un campo para actualizar.'));
    }
    return $datos;
}

$entidades = array(
    'tipo_usuario' => array(
        'tabla' => 'TIPO_USUARIO',
        'campos' => array(
            'roles' => array('column' => 'ROLES', 'type' => 'string', 'max' => 100, 'required' => true)
        )
    ),
    'usuarios' => array(
        'tabla' => 'USUARIOS',
        'campos' => array(
            'nombre' => array('column' => 'NOMBRE', 'type' => 'string', 'max' => 100, 'required' => true),
            'apellido' => array('column' => 'APELLIDO', 'type' => 'string', 'max' => 100, 'required' => true),
            'email' => array('column' => 'EMAIL', 'type' => 'string', 'format' => 'email', 'max' => 100, 'required' => true),
            'password' => array('column' => 'PASSWORD', 'type' => 'password', 'required' => true),
            'tipo_usuario_id' => array('column' => 'TIPO_USUARIO_ID', 'type' => 'int', 'min' => 1, 'nullable' => true)
        )
    ),
    'categorias' => array(
        'tabla' => 'CATEGORIAS',
        'campos' => array(
            'nombre' => array('column' => 'NOMBRE', 'type' => 'string', 'max' => 100, 'required' => true)
        )
    ),
    'productos' => array(
        'tabla' => 'PRODUCTOS',
        'campos' => array(
            'nombre' => array('column' => 'NOMBRE', 'type' => 'string', 'max' => 100, 'required' => true),
            'descripcion' => array('column' => 'DESCRIPCION', 'type' => 'string', 'max' => 65535, 'nullable' => true),
            'precio' => array('column' => 'PRECIO', 'type' => 'decimal', 'required' => true),
            'stock' => array('column' => 'STOCK', 'type' => 'int', 'min' => 0, 'required' => true),
            'imagen' => array('column' => 'IMAGEN', 'type' => 'string', 'max' => 255, 'nullable' => true),
            'categoria_id' => array('column' => 'CATEGORIA_ID', 'type' => 'int', 'min' => 1, 'nullable' => true)
        )
    ),
    'clientes' => array(
        'tabla' => 'CLIENTES',
        'campos' => array(
            'nombre' => array('column' => 'NOMBRE', 'type' => 'string', 'max' => 100, 'required' => true),
            'apellido' => array('column' => 'APELLIDO', 'type' => 'string', 'max' => 100, 'required' => true),
            'email' => array('column' => 'EMAIL', 'type' => 'string', 'format' => 'email', 'max' => 100, 'required' => true),
            'telefono' => array('column' => 'TELEFONO', 'type' => 'string', 'max' => 20, 'nullable' => true),
            'direccion' => array('column' => 'DIRECCION', 'type' => 'string', 'max' => 255, 'nullable' => true)
        )
    ),
    'ventas' => array(
        'tabla' => 'VENTAS',
        'campos' => array(
            'cliente_id' => array('column' => 'CLIENTE_ID', 'type' => 'int', 'min' => 1, 'nullable' => true),
            'usuario_id' => array('column' => 'USUARIO_ID', 'type' => 'int', 'min' => 1, 'nullable' => true),
            'fecha' => array('column' => 'FECHA', 'type' => 'datetime', 'required' => true),
            'total' => array('column' => 'TOTAL', 'type' => 'decimal', 'required' => true),
            'estado' => array('column' => 'ESTADO', 'type' => 'string', 'max' => 50, 'required' => true)
        )
    ),
    'detalle_ventas' => array(
        'tabla' => 'DETALLE_VENTAS',
        'campos' => array(
            'venta_id' => array('column' => 'VENTA_ID', 'type' => 'int', 'min' => 1, 'required' => true),
            'producto_id' => array('column' => 'PRODUCTO_ID', 'type' => 'int', 'min' => 1, 'required' => true),
            'cantidad' => array('column' => 'CANTIDAD', 'type' => 'int', 'min' => 1, 'required' => true),
            'precio' => array('column' => 'PRECIO', 'type' => 'decimal', 'required' => true)
        )
    )
);

$metodo = $_SERVER['REQUEST_METHOD'];
$nombreEntidad = isset($_GET['entidad']) ? strtolower($_GET['entidad']) : '';
if (!isset($entidades[$nombreEntidad])) {
    responderApi(400, array(
        'success' => false,
        'message' => 'Indicá una entidad válida: tipo_usuario, usuarios, categorias, productos, clientes, ventas o detalle_ventas.'
    ));
}
if (!in_array($metodo, array('GET', 'POST', 'PUT', 'PATCH', 'DELETE'), true)) {
    header('Allow: GET, POST, PUT, PATCH, DELETE');
    responderApi(405, array('success' => false, 'message' => 'Método no permitido.'));
}

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
if (!session_start() || !isset($_SESSION['usuario']['id'])) {
    responderApi(401, array('success' => false, 'message' => 'Iniciá sesión para acceder a esta API.'));
}

$entidad = $entidades[$nombreEntidad];
$tabla = $entidad['tabla'];
$campos = $entidad['campos'];
$id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
if (isset($_GET['id']) && ($id === false || $id < 1)) {
    responderApi(400, array('success' => false, 'message' => 'El id debe ser un entero positivo.'));
}

$columnasSalida = array('ID AS id');
foreach ($campos as $nombre => $campo) {
    if ($campo['type'] !== 'password') {
        $columnasSalida[] = $campo['column'] . ' AS ' . $nombre;
    }
}
$select = implode(', ', $columnasSalida);

try {
    require_once __DIR__ . '/conexion.php';

    if ($metodo === 'GET') {
        if ($id !== null) {
            $consulta = $conexion->prepare("SELECT $select FROM $tabla WHERE ID = :id");
            $consulta->execute(array('id' => $id));
            $registro = $consulta->fetch();
            if (!$registro) {
                responderApi(404, array('success' => false, 'message' => 'No se encontró el registro.'));
            }
            responderApi(200, array('success' => true, 'data' => $registro));
        }

        $limite = isset($_GET['limite']) ? filter_var($_GET['limite'], FILTER_VALIDATE_INT) : 100;
        $pagina = isset($_GET['pagina']) ? filter_var($_GET['pagina'], FILTER_VALIDATE_INT) : 1;
        if ($limite === false || $limite < 1 || $limite > 500 || $pagina === false || $pagina < 1) {
            responderApi(400, array(
                'success' => false,
                'message' => 'La página debe ser positiva y el límite debe estar entre 1 y 500.'
            ));
        }
        $offset = ($pagina - 1) * $limite;
        $consulta = $conexion->prepare("SELECT $select FROM $tabla ORDER BY ID DESC LIMIT :limite OFFSET :offset");
        $consulta->bindValue(':limite', $limite, PDO::PARAM_INT);
        $consulta->bindValue(':offset', $offset, PDO::PARAM_INT);
        $consulta->execute();
        responderApi(200, array(
            'success' => true,
            'data' => $consulta->fetchAll(),
            'pagina' => $pagina,
            'limite' => $limite
        ));
    }

    if ($metodo === 'POST') {
        if ($id !== null) {
            responderApi(400, array('success' => false, 'message' => 'No envíes id al crear un registro.'));
        }
        $datos = validarDatosApi(leerDatosApi(), $campos, true);
        $columnas = array();
        $marcadores = array();
        foreach ($datos as $nombre => $valor) {
            $columnas[] = $campos[$nombre]['column'];
            $marcadores[] = ':' . $nombre;
        }
        $consulta = $conexion->prepare(
            'INSERT INTO ' . $tabla . ' (' . implode(', ', $columnas) . ') VALUES (' . implode(', ', $marcadores) . ')'
        );
        $consulta->execute($datos);
        $nuevoId = (int) $conexion->lastInsertId();
        $consulta = $conexion->prepare("SELECT $select FROM $tabla WHERE ID = :id");
        $consulta->execute(array('id' => $nuevoId));
        responderApi(201, array(
            'success' => true,
            'message' => 'Registro creado.',
            'data' => $consulta->fetch()
        ));
    }

    if ($id === null) {
        responderApi(400, array('success' => false, 'message' => 'Indicá el id del registro en el parámetro id.'));
    }
    if ($metodo === 'DELETE') {
        $consulta = $conexion->prepare("DELETE FROM $tabla WHERE ID = :id");
        $consulta->execute(array('id' => $id));
        if ($consulta->rowCount() === 0) {
            responderApi(404, array('success' => false, 'message' => 'No se encontró el registro.'));
        }
        responderApi(200, array('success' => true, 'message' => 'Registro eliminado.'));
    }

    $consulta = $conexion->prepare("SELECT ID FROM $tabla WHERE ID = :id");
    $consulta->execute(array('id' => $id));
    if (!$consulta->fetch()) {
        responderApi(404, array('success' => false, 'message' => 'No se encontró el registro.'));
    }
    $datos = validarDatosApi(leerDatosApi(), $campos, false);
    $asignaciones = array();
    foreach ($datos as $nombre => $valor) {
        $asignaciones[] = $campos[$nombre]['column'] . ' = :' . $nombre;
    }
    $consulta = $conexion->prepare('UPDATE ' . $tabla . ' SET ' . implode(', ', $asignaciones) . ' WHERE ID = :id');
    $datos['id'] = $id;
    $consulta->execute($datos);
    $consulta = $conexion->prepare("SELECT $select FROM $tabla WHERE ID = :id");
    $consulta->execute(array('id' => $id));
    responderApi(200, array(
        'success' => true,
        'message' => 'Registro actualizado.',
        'data' => $consulta->fetch()
    ));
} catch (PDOException $e) {
    error_log('Error en CRUD de ' . $nombreEntidad . ': ' . $e->getMessage());
    if ($e->getCode() === '23000') {
        responderApi(409, array(
            'success' => false,
            'message' => 'El registro duplica un dato único o tiene una relación que no permite esta operación.'
        ));
    }
    responderApi(500, array('success' => false, 'message' => 'No se pudo completar la operación.'));
}
