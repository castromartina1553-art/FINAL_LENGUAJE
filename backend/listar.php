<?php
require_once __DIR__ . '/api_common.php';
$contexto = iniciarApi(array('GET'), false);

ejecutarApi($contexto, function ($conexion) use ($contexto) {
    $tabla = $contexto['entidad']['tabla'];
    $select = columnasApi($contexto['entidad']['campos']);
    if ($contexto['id'] !== null) {
        $consulta = $conexion->prepare("SELECT $select FROM $tabla WHERE ID = :id");
        $consulta->execute(array('id' => $contexto['id']));
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
});
