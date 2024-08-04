<?php
	use Psr\Http\Message\ResponseInterface as Response;
	use Psr\Http\Message\ServerRequestInterface as Request;
    
	require_once("objects/inscripciones.php");

	//[GET]

	$app->get("/inscripciones", function (Request $request, Response $response, array $args) {
		$inscripciones = new Inscripciones($this->get("db"));
		$resp = $inscripciones->getInscripciones()->getResult();
		$response->getBody()->write(json_encode($resp));
		return $response
			->withHeader("Content-Type", "application/json")
			->withStatus($resp->ok ? 200 : 409);
	});

	$app->get("/inscripciones/{id}", function (Request $request, Response $response, array $args) {
		$inscripciones = new Inscripciones($this->get("db"));
		$resp = $inscripciones->getInscripcionesByUser($args["id"])->getResult();
		$response->getBody()->write(json_encode($resp));
		return $response
			->withHeader("Content-Type", "application/json")
			->withStatus($resp->ok ? 200 : 409);
	});

	//[POST]
	$app->put("/inscripciones/remera/{id}", function (Request $request, Response $response, array $args) {
		$inscripciones = new Inscripciones($this->get("db"));
		$resp = $inscripciones->setEntregaRemera($args["id"])->getResult();
		$response->getBody()->write(json_encode($resp));
		return $response
			->withHeader("Content-Type", "application/json")
			->withStatus($resp->ok ? 200 : 409);
	});
?>