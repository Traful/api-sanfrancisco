<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once("objects/estadisticas.php");

//[GET]

$app->get("/estadisticas/total/inscriptos", function (Request $request, Response $response, array $args) {
	$estadisticas = new Estadisticas($this->get("db"));
	$resp = $estadisticas->getEstadisticasTotalInscriptos()->getResult();
	$response->getBody()->write(json_encode($resp));
	return $response
		->withHeader("Content-Type", "application/json")
		->withStatus($resp->ok ? 200 : 409);
});

$app->get("/estadisticas/generos", function (Request $request, Response $response, array $args) {
	$estadisticas = new Estadisticas($this->get("db"));
	$resp = $estadisticas->getEstadisticasByGeneros()->getResult();
	$response->getBody()->write(json_encode($resp));
	return $response
		->withHeader("Content-Type", "application/json")
		->withStatus($resp->ok ? 200 : 409);
});

$app->get("/estadisticas/items", function (Request $request, Response $response, array $args) {
	$estadisticas = new Estadisticas($this->get("db"));
	$resp = $estadisticas->getEstadisticasByItems()->getResult();
	$response->getBody()->write(json_encode($resp));
	return $response
		->withHeader("Content-Type", "application/json")
		->withStatus($resp->ok ? 200 : 409);
});

$app->get("/estadisticas/edades", function (Request $request, Response $response, array $args) {
	$estadisticas = new Estadisticas($this->get("db"));
	$resp = $estadisticas->getEstadisticasByEdades()->getResult();
	$response->getBody()->write(json_encode($resp));
	return $response
		->withHeader("Content-Type", "application/json")
		->withStatus($resp->ok ? 200 : 409);
});

$app->get("/estadisticas/talles", function (Request $request, Response $response, array $args) {
	$estadisticas = new Estadisticas($this->get("db"));
	$resp = $estadisticas->getEstadisticasByTalles()->getResult();
	$response->getBody()->write(json_encode($resp));
	return $response
		->withHeader("Content-Type", "application/json")
		->withStatus($resp->ok ? 200 : 409);
});

$app->get("/estadisticas/tipo-inscripcion", function (Request $request, Response $response, array $args) {
	$estadisticas = new Estadisticas($this->get("db"));
	$resp = $estadisticas->getEstadisticasByTipoInscripcion()->getResult();
	$response->getBody()->write(json_encode($resp));
	return $response
		->withHeader("Content-Type", "application/json")
		->withStatus($resp->ok ? 200 : 409);
});
$app->get("/inscritos", function (Request $request, Response $response, array $args) {
    $estadisticas = new Estadisticas($this->get("db"));
    $resp = $estadisticas->getListadoInscritos()->getResult();
    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

$app->get("/inscritos/{dni}", function (Request $request, Response $response, array $args) {
    $dni = $args['dni'];
    $estadisticas = new Estadisticas($this->get("db"));
    $resp = $estadisticas->getInscritoPorDni($dni)->getResult();
    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

