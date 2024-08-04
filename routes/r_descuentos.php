<?php
	use Psr\Http\Message\ResponseInterface as Response;
	use Psr\Http\Message\ServerRequestInterface as Request;
    
	require_once("objects/descuentos.php");

	//[GET]

	$app->get("/descuentos", function (Request $request, Response $response, array $args) {
		$descuentos = new Descuentos($this->get("db"));
		$resp = $descuentos->getDescuentos()->getResult();
		$response->getBody()->write(json_encode($resp));
		return $response
			->withHeader("Content-Type", "application/json")
			->withStatus($resp->ok ? 200 : 409);
	});

	//[POST]

	$app->post("/descuentos", function (Request $request, Response $response, array $args) {
		$fields = $request->getParsedBody();
		
		$verificar = [
			"codigo" => [
				"type" => "string",
				"min" => 3,
				"max" => 50
			],
			"disponibilidad" => [
				"type" => "number",
				"min" => 1
			],
			"importe" => [
				"type" => "number",
				"min" => 500
			]
		];

		$validacion = new Validate($this->get("db"));
		$validacion->validar($fields, $verificar);

		$resp = null;

		if($validacion->hasErrors()) {
			$resp = $validacion->getErrors();
		} else {
			$item = new Descuentos($this->get("db"));
			$resp = $item->setDescuento($fields)->getResult();
		}

		$response->getBody()->write(json_encode($resp));
		return $response
			->withHeader("Content-Type", "application/json")
			->withStatus($resp->ok ? 200 : 409);
	});

?>