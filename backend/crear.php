<?php
require_once __DIR__ . '/api_common.php';
$contexto = iniciarApi(array('POST'), false);
if ($contexto['id'] !== null) {
    responderApi(400, array('success' => false, 'message' => 'No envíes id al crear un registro.'));
}
$datos = validarDatosApi(leerDatosApi(), $contexto['entidad']['campos'], true);

ejecutarApi($contexto, function ($conexion) use ($contexto, $datos) {
    $tabla = $contexto['entidad']['tabla'];
    $campos = $contexto['entidad']['campos'];
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
    $select = columnasApi($campos);
    $consulta = $conexion->prepare("SELECT $select FROM $tabla WHERE ID = :id");
    $consulta->execute(array('id' => $nuevoId));
    responderApi(201, array(
        'success' => true,
        'message' => 'Registro creado.',
        'data' => $consulta->fetch()
    ));
});
