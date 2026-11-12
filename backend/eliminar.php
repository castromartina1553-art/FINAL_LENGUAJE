<?php
require_once __DIR__ . '/api_common.php';
$contexto = iniciarApi(array('DELETE'), true);

ejecutarApi($contexto, function ($conexion) use ($contexto) {
    $consulta = $conexion->prepare(
        'DELETE FROM ' . $contexto['entidad']['tabla'] . ' WHERE ID = :id'
    );
    $consulta->execute(array('id' => $contexto['id']));
    if ($consulta->rowCount() === 0) {
        responderApi(404, array('success' => false, 'message' => 'No se encontró el registro.'));
    }
    responderApi(200, array('success' => true, 'message' => 'Registro eliminado.'));
});
