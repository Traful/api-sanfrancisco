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


function sendMail($email, $nombre, $subject, $body, $alt = "")
{
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

function mailPagoOk($db, $preference_id)
{
	try {
		$ins = new Inscripciones($db);
		$inscripcion = $ins->getInscripcionesByIdPago($preference_id)->getResult()->data;
		if ($inscripcion) {
			$email = $inscripcion->email;
			$nombre = $inscripcion->nombre;
			$apellido = $inscripcion->apellido;
			$importe = $inscripcion->importe;

			$subject = "¡Inscripción Realizada con Éxito!";
			$alt = "";
			$logoUrl = $_ENV["APP_URL"] . "assets/images/logo_municipalidad.png";
			$sponsor1Url = $_ENV["APP_URL"] . "assets/images/sponsor11.png";
			$sponsor2Url = $_ENV["APP_URL"] . "assets/images/sponsor22.png";
			$sponsor3Url = $_ENV["APP_URL"] . "assets/images/sponsor33.png";
			$codeoLogoUrl = $_ENV["APP_URL"] . "assets/images/codeo_logo.png";

			$body = <<<EOD
			<!DOCTYPE html>
			<html lang="es">
			<head>
				<meta charset="UTF-8">
				<style>
					body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; }
					.container { max-width: 600px; margin: 0 auto; padding: 20px; background-color: #ffffff; }
					.header { text-align: center; margin-bottom: 20px; }
					.logo { max-width: 200px; }
					h1 { color: #0056b3; }
					.content { padding: 20px; }
					.recommendations { background-color: #e9ecef; padding: 15px; border-radius: 5px; margin-top: 20px; }
					.recommendation-item { display: flex; align-items: center; margin-bottom: 10px; }
					.recommendation-icon { width: 24px; height: 24px; margin-right: 10px; }
					.sponsors {  margin-top: 30px; text-align: center; color: #666; }
					.sponsor-logo {  max-width: 80px; padding-left: 25px;}
					.logo1 {  max-width: 130px;}
					.footer { margin-top: 30px; font-size: 12px; text-align: center; color: #666; }
					.codeo-logo { max-width: 30px; }
				</style>
			</head>
			<body>
				<div class="container">
					<div class="header">
						<img src="{$logoUrl}" alt="Logo Municipalidad de San Francisco" class="logo">
						<h1>¡Inscripción Realizada con Éxito!</h1>
					</div>
					<div class="content">
						<p>Estimado/a <strong>{$nombre} {$apellido}</strong>,</p>
						<p>Nos complace confirmar que tu inscripción ha sido procesada correctamente. Hemos recibido tu pago de <strong>$ {$importe}</strong>.</p>
						<p>Estamos emocionados de tenerte como participante en nuestro evento.</p>
						
						<div class="recommendations">
							<h3>Recomendaciones importantes:</h3>
							<div class="recommendation-item">
								☑️ <span>&nbsp; Mantente hidratado antes, durante y después de la carrera.</span>
							</div>
							<div class="recommendation-item">
								☑️ <span>&nbsp; Usa ropa cómoda y adecuada para correr.</span>
							</div>
							<div class="recommendation-item">
								☑️ <span>&nbsp; Consume un desayuno ligero al menos 2 horas antes de la carrera.</span>
							</div>
							<div class="recommendation-item">
							☑️ <span>&nbsp; No olvides llevar tu identificación el día del evento.</span>
							</div>
						</div>
					</div>
					
					<div class="sponsors">
						<img src="{$sponsor1Url}" alt="Sponsor 1" class="logo1 sponsor-logo">
						<img src="{$sponsor2Url}" alt="Sponsor 2" class="sponsor-logo">
						<img src="{$sponsor3Url}" alt="Sponsor 3" class="sponsor-logo">
					</div>
					
					<div class="footer">
						<p>Realizado por Codeo</p>
						<img src="{$codeoLogoUrl}" alt="Codeo Logo" class="codeo-logo">
					</div>
				</div>
			</body>
			</html>
			EOD;

			return sendMail($email, $nombre, $subject, $body, $alt);
		}
	} catch (\Throwable $th) {
		// Manejo de errores
		error_log("Error en mailPagoOk: " . $th->getMessage());
		return false;
	}
}


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

	$mp = new Mp($this->get("db"));
	$resp = $mp->setNotificacion($fields)->getResult();

	if ($fields["status"] === "approved") {
		$ins = new Inscripciones($this->get("db"));
		$resp = $ins->updatePaymentState($fields["preference_id"])->getResult();
		if ($resp->ok) {
			mailPagoOk($this->get("db"), $fields["preference_id"]);
		}
	}

	//error_log("Redirecting to: https://vivisanfrancisco.com/ticket/inscripcion-exito");

	return $response
		->withHeader("Content-Type", "application/json")
		->withStatus($resp->ok ? 200 : 409);
});

$app->get('/redirect', function (Request $request, Response $response, $args) {
	return $response->withHeader('Location', 'https://vivisanfrancisco.com/ticket/inscripcion-exito')->withStatus(302);
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

	if ($validacion->hasErrors()) {
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
