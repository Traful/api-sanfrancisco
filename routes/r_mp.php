<?php
	use Psr\Http\Message\ResponseInterface as Response;
	use Psr\Http\Message\ServerRequestInterface as Request;
	use \PHPMailer\PHPMailer\PHPMailer;
	use \PHPMailer\PHPMailer\SMTP;
	use \PHPMailer\PHPMailer\Exception;
    
	//use objects\Mp;
	require_once("objects/mp.php");
	require_once("objects/inscripciones.php");
    //use utils\Validate;
	require_once("utils/validate.php");

	
	function sendMail($email, $nombre, $subject, $body, $alt = "") {
		$mail = new PHPMailer(true);
		$mail->CharSet = "UTF-8";
		try {
			// Server settings
			$mail->SMTPDebug = 0;
			$mail->isSMTP();
			$mail->Host       = $_ENV["SMTP_HOST"];
			$mail->SMTPAuth   = true;
			$mail->Username   = $_ENV["SMTP_USERNAME"];
			$mail->Password   = $_ENV["SMTP_PASSWORD"];
			$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
			$mail->Port       = $_ENV["SMTP_PORT"];

			// Recipients
			$mail->setFrom($_ENV["SMTP_USERNAME"], $_ENV["SMTP_SENDER_NAME"]);
			$mail->addAddress($email, $nombre);

			$mail->isHTML(true);
			$mail->Subject = $subject;

			$mail->Body = $body;
			$mail->AltBody = $alt;

			$mail->send();

			return true;
		} catch (Exception $e) {
			return false;
		}
	};

	function mailPagoOk($db, $preference_id) {
		try {
			$ins = new Inscripciones($db);
			$inscripcion = $ins->getInscripcionesByIdPago($preference_id)->getResult()->data;

			$email = $inscripcion->email;
			$nombre = $inscripcion->nombre;
			$importe = $inscripcion->importe;

			$subject = "Pago de inscripción recepcionado.";
			$alt = "";
			$logoUrl = $_ENV["APP_URL"] . "assets/images/logo_municipalidad.png"; // Asegúrate de tener esta imagen
			$body = <<<EOD
			<!DOCTYPE html>
			<html lang="es">
			<head>
				<meta charset="UTF-8">
				<style>
					body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
					.container { max-width: 600px; margin: 0 auto; padding: 20px; }
					.header { text-align: center; margin-bottom: 20px; }
					.logo { max-width: 200px; }
					h1 { color: #0056b3; }
					.footer { margin-top: 30px; font-size: 12px; text-align: center; color: #666; }
				</style>
			</head>
			<body>
				<div class="container">
					<div class="header">
						<img src="{$logoUrl}" alt="Logo Municipalidad de San Francisco" class="logo">
						<h1>Bienvenido al Sistema de Tickets 10K</h1>
					</div>
					<p>Estimado/a <strong>{$nombre}</strong>.</p>
					<p>Recibimos tu pago de $ {$importe}</p>
					<p>Gracias por su confianza en nuestro sistema. Si tiene alguna pregunta, no dude en contactarnos.</p>
					<div class="footer">
						<p>Desarrollado por Codeo</p>
					</div>
				</div>
			</body>
			</html>
			EOD;

			sendMail($email, $nombre, $subject, $body, $alt = "");
		} catch (\Throwable $th) {
			//throw $th;
		}
	};
	

	//[GET]

	$app->get("/mp/items", function (Request $request, Response $response, array $args) {
		$item = new Mp($this->get("db"));
		$resp = $item->getItems()->getResult();

		$response->getBody()->write(json_encode($resp));
		return $response
			->withHeader("Content-Type", "application/json")
			->withStatus($resp->ok ? 200 : 409);
	});

	$app->get("/mp/historial", function (Request $request, Response $response, array $args) {
		$historial = new Mp($this->get("db"));
		$resp = $historial->getHistorial()->getResult();
		$response->getBody()->write(json_encode($resp));
		return $response
			->withHeader("Content-Type", "application/json")
			->withStatus($resp->ok ? 200 : 409);
	});

	$app->get("/mp/historial/user/{id:[0-9]+}", function (Request $request, Response $response, array $args) {
		$historial = new Mp($this->get("db"));
		$resp = $historial->getHistorialByUser($args["id"])->getResult();
		$response->getBody()->write(json_encode($resp));
		return $response
			->withHeader("Content-Type", "application/json")
			->withStatus($resp->ok ? 200 : 409);
	});

	$app->get("/mp/success", function (Request $request, Response $response, array $args) {
		$fields = $request->getQueryParams();

		/*
		var_dump($fields);
		array(11) {
			["collection_id"]=> string(11) "84063485259"
			["collection_status"]=> string(8) "approved"
			["payment_id"]=> string(11) "84063485259"
			["status"]=> string(8) "approved"
			["external_reference"]=> string(21) "hans success test - 2"
			["payment_type"]=> string(13) "account_money"
			["merchant_order_id"]=> string(11) "21392216305"
			["preference_id"]=> string(47) "1922226487-e9d7fbb4-8362-43be-bbef-31eb277b790d" !!! <-Este es el ID del Item que se vendio [buscar en inscripciones y dar como pago!]
			["site_id"]=> string(3) "MLA"
			["processing_mode"]=> string(10) "aggregator"
			["merchant_account_id"]=> string(4) "null"
		}
		*/

		$fieldsString = print_r($fields, true);
		$file = "archivos/success.txt";
		file_put_contents($file, $fieldsString, FILE_APPEND);

		$mp = new Mp($this->get("db"));
		$resp = $mp->setNotificacion($fields)->getResult();;

		if($fields["status"] === "approved") {
			$ins = new Inscripciones($this->get("db"));
			$resp = $ins->updatePaymentState($fields["preference_id"])->getResult();
			//Enviar mail!
			mailPagoOk($this->get("db"), $fields["preference_id"]);
		}

		/*
		$resp = new stdClass();
		$resp->ok = true;
		$resp->msg = "";
		$resp->data = null;
		*/

		return $response->withHeader("Location", "https://hans.net.ar/mis_inscripciones")->withStatus(302);

		/*
		$response->getBody()->write(json_encode($resp));
		return $response
			->withHeader("Content-Type", "application/json")
			->withStatus($resp->ok ? 200 : 409);
		*/
	});

	//[POST]

	$app->post("/mp/item", function (Request $request, Response $response, array $args) {
		$fields = $request->getParsedBody();
		
		$verificar = [
			"titulo" => [
				"type" => "string",
				"min" => 5,
				"max" => 50
			],
			"cantidad" => [
				"type" => "number",
				"min" => 1
			],
			"precio" => [
				"type" => "number",
				"min" => 1
			]
		];

		$validacion = new Validate($this->get("db"));
		$validacion->validar($fields, $verificar);

		$resp = null;

		if($validacion->hasErrors()) {
			$resp = $validacion->getErrors();
		} else {
			$item = new Mp($this->get("db"));
			$resp = $item->setItem($fields)->getResult();
		}

		$response->getBody()->write(json_encode($resp));
		return $response
			->withHeader("Content-Type", "application/json")
			->withStatus($resp->ok ? 200 : 409);
	});

	$app->post("/mp/procesar/pago", function (Request $request, Response $response, array $args) {
		$fields = $request->getParsedBody();

		$pago = new Mp($this->get("db"));
		$resp = $pago->procesarPago($fields)->getResult();

		$response->getBody()->write(json_encode($resp));
		return $response
			->withHeader("Content-Type", "application/json")
			->withStatus($resp->ok ? 200 : 409);
	});

    $app->post("/mp/notificaciones", function (Request $request, Response $response, array $args) {
		$fields = $request->getParsedBody();

		$fieldsString = print_r($fields, true);
		$file = "archivos/notificaciones.txt";
		file_put_contents($file, $fieldsString, FILE_APPEND);

		$mp = new Mp($this->get("db"));
		$resp = $mp->setNotificacion($fields)->getResult();

		/*
		$resp = new stdClass();
		$resp->ok = true;
		$resp->msg = "";
		$resp->data = null;
		*/

		$response->getBody()->write(json_encode($resp));
		return $response
			->withHeader("Content-Type", "application/json")
			->withStatus($resp->ok ? 200 : 409);
	});

?>