<?php
require_once __DIR__ . '/api_common.php';
$contexto = iniciarApi(array('PUT', 'PATCH'), true);
$datos = validarDatosApi(leerDatosApi(), $contexto['entidad']['campos'], false);

ejecutarApi($contexto, function ($conexion) use ($contexto, $datos) {
    $tabla = $contexto['entidad']['tabla'];
    $campos = $contexto['entidad']['campos'];
    $consulta = $conexion->prepare("SELECT ID FROM $tabla WHERE ID = :id");
    $consulta->execute(array('id' => $contexto['id']));
    if (!$consulta->fetch()) {
        responderApi(404, array('success' => false, 'message' => 'No se encontró el registro.'));
    }

    $asignaciones = array();
    foreach ($datos as $nombre => $valor) {
        $asignaciones[] = $campos[$nombre]['column'] . ' = :' . $nombre;
    }
    $consulta = $conexion->prepare(
        'UPDATE ' . $tabla . ' SET ' . implode(', ', $asignaciones) . ' WHERE ID = :id'
    );
    $datos['id'] = $contexto['id'];
    $consulta->execute($datos);
    $select = columnasApi($campos);
    $consulta = $conexion->prepare("SELECT $select FROM $tabla WHERE ID = :id");
    $consulta->execute(array('id' => $contexto['id']));
    responderApi(200, array(
        'success' => true,
        'message' => 'Registro actualizado.',
        'data' => $consulta->fetch()
    ));
});
