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

		// Fetch user details

		$this->sendConfirmationEmail("federiconj@gmail.com", "fede");
	}

	return $response->withHeader("Location", "https://vivisanfrancisco.com/ticket/mis_inscripciones")->withStatus(302);
});

// Add this method to your class or container
function sendConfirmationEmail($email, $nombre)
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

		// Content
		$logoUrl = "https://vivisanfrancisco.com/ticket/assets/logonegro-DXNK33qQ.png";

		$mail->isHTML(true);
		$mail->Subject = "Confirmación de Inscripción - Carrera 10K San Francisco";
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
                    <h1>Confirmación de Inscripción - Carrera 10K</h1>
                </div>
                <p>Estimado/a <strong>{$nombre}</strong>,</p>
                <p>Nos complace confirmar que su inscripción para la carrera de 10K en San Francisco del Monte de Oro ha sido registrada con éxito.</p>
                <p>Detalles de la inscripción:</p>
                <ul>
                    <li>Evento: Carrera 10K San Francisco</li>
                    <li>Estado: Confirmado</li>
                    <li>Pago: Aprobado</li>
                </ul>
                <p>Gracias por participar en nuestro evento. Le deseamos mucho éxito en la carrera.</p>
                <p>Si tiene alguna pregunta o necesita información adicional, no dude en contactarnos.</p>
                <div class="footer">
                    <p>Municipalidad de San Francisco del Monte de Oro</p>
                </div>
            </div>
        </body>
        </html>
        EOD;

		$mail->Body = $body;
		$mail->AltBody = "Confirmación de Inscripción - Carrera 10K San Francisco. Estimado/a {$nombre}, su inscripción ha sido registrada con éxito. Estado: Confirmado. Pago: Aprobado. Gracias por participar en nuestro evento.";

		$mail->send();
		return true;
	} catch (Exception $e) {
		// Log the error or handle it as needed
		return false;
	}
}
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
