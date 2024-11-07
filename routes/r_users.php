<?php

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;
use \PHPMailer\PHPMailer\PHPMailer;
use \PHPMailer\PHPMailer\SMTP;
use \PHPMailer\PHPMailer\Exception;
//use \objects\Users;
//use \utils\Validate;
//use \utils\Prepare;

require_once("./utils/validate.php");
require_once("./utils/prepare.php");
require_once("./objects/users.php");


function sendTokenRegister($email, $nombre, $token)
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
		$urlLink = $_ENV["APP_URL"] . "register/token/" . $token;
		$logoUrl = "https://vivisanfrancisco.com/ticket/assets/logonegro-DXNK33qQ.png"; // Asegúrate de tener esta imagen

		$mail->isHTML(true);
		$mail->Subject = "Bienvenido al Sistema de Tickets de San Francisco del Monte de Oro";
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
					<h1>Bienvenido al Sistema de Ticket para la carrera de 10K</h1>
				</div>
				<p>Estimado/a <strong>{$nombre}</strong>.</p>
				<p>Le damos la más cordial bienvenida al Sistema de Tickets 10K de la Municipalidad de San Francisco. Estamos encantados de que se una a nuestra plataforma.</p>
				<p>Para completar su registro y acceder al sistema, por favor haga clic en el siguiente botón:</p>
				<p style="text-align: center;">
					<a href="{$urlLink}" style="display: inline-block; padding: 10px 20px; background-color: #0056b3; color: #ffffff; text-decoration: none; border-radius: 5px;" target="_blank" rel="nofollow noopener">Validar mi cuenta</a>
				</p>
				<p>Si el botón no funciona, puede copiar y pegar el siguiente enlace en su navegador:</p>
				<p>{$urlLink}</p>
				<p>Gracias por su confianza en nuestro sistema. Si tiene alguna pregunta, no dude en contactarnos.</p>
				<div class="footer">
					<p>Desarrollado por Codeo</p>
				</div>
			</div>
		</body>
		</html>
		EOD;

		$mail->Body = $body;
		$mail->AltBody = "Bienvenido/a {$nombre} al Sistema de Tickets 10K de San Francisco. Para validar su cuenta y acceder al sistema, visite este enlace: {$urlLink}. Desarrollado por Codeo.";

		$mail->send();
		return true;
	} catch (Exception $e) {
		return false;
	}
}
function sendTokenRecover($email, $token)
{
	$mail = new PHPMailer(true);
	$mail->CharSet = "UTF-8";
	try {
		$mail->SMTPDebug = 0;
		$mail->isSMTP();
		$mail->Host       = $_ENV["SMTP_HOST"];
		$mail->SMTPAuth   = true;
		$mail->Username   = $_ENV["SMTP_USERNAME"];
		$mail->Password   = $_ENV["SMTP_PASSWORD"];
		$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
		$mail->Port       = $_ENV["SMTP_PORT"];

		$mail->setFrom($_ENV["SMTP_USERNAME"], $_ENV["SMTP_SENDER_NAME"]);
		$mail->addAddress($email);

		$urlLink = $_ENV["APP_URL"] . "recover/token/" . $token;

		$mail->isHTML(true);
		$mail->Subject = "Sheep Shit - Recuperación de contraseña";
		$body = <<<EOD
				<h3>Sheep Shit</h3>
				<br/>
				<p>Ups.. al perecer olvidaste tu contraseña, para poder generar una nueva por favor visita este <a href='{$urlLink}' target='_blank' rel='nofollow noopener'>enlace</a>.</p>
			EOD;
		$mail->Body    = $body;
		$mail->AltBody = "Sheep Shit - Ups.. al perecer olvidaste tu contraseña, para poder generar una nueva por favor visita este enlace: " . $urlLink;
		$mail->send();
		//echo "Message has been sent";
		return true;
	} catch (Exception $e) {
		//echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
		return false;
	}
};

//[GET]

$app->get("/users", function (Request $request, Response $response, array $args) {
	$users = new Users($this->get("db"));
	$resp = $users->getUsers()->getResult();
	$response->getBody()->write(json_encode($resp));
	return $response
		->withHeader("Content-Type", "application/json")
		->withStatus($resp->ok ? 200 : 409);
});

$app->get("/user/{id:[0-9]+}", function (Request $request, Response $response, array $args) {
	$users = new Users($this->get("db"));
	$resp = $users->getUser($args["id"])->getResult();
	$response->getBody()->write(json_encode($resp));
	return $response
		->withHeader("Content-Type", "application/json")
		->withStatus($resp->ok ? 200 : 409);
});

$app->get("/user/register/temp/{token}", function (Request $request, Response $response, array $args) {
    $resp = new \stdClass();
    $users = new Users($this->get("db"));
    $result = $users->getUserTemp($args["token"])->getResult();
    
    if (isset($result->data->id)) {
        $id = $result->data->id;
        $resp = $users->moveTempUser($id)->getResult();
        $status = 200;
    } else {
        $resp->ok = false;
        $resp->msg = "El token [" . $args["token"] . "] no es válido.";
        $resp->data = false;
        $status = 401;  // Cambia el status a 401 si el token no es válido
    }
    
    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($status);  // Devolver el estado adecuado
});

$app->get("/user/password/temp/{token}", function (Request $request, Response $response, array $args) {
	$resp = new \stdClass();
	$users = new Users($this->get("db"));
	$respT = clone ($users->getUserPasswordTemp($args["token"])->getResult());
	if (isset($respT->data->id)) {
		$id = $respT->data->id;
		$resp = $users->moveTempUser($id)->getResult();
		$resp->data = $respT->data;
	} else {
		$resp->ok = false;
		$resp->msg = "El token [" . $args["token"] . "] no es válido.";
		$resp->data = false;
	}
	$response->getBody()->write(json_encode($resp));
	return $response
		->withHeader("Content-Type", "application/json")
		->withStatus($resp->ok ? 200 : 409);
});

$app->get("/user/token/validate/{token}", function (Request $request, Response $response, array $args) {
	$resp = new \stdClass();
	$resp->ok = false;
	$resp->msg = "El token [" . $args["token"] . "] no es válido.";

	//$jwt = JWT::encode($payload, $_SERVER["JWT_SECRET_KEY"], $_SERVER["JWT_ALGORITHM"]);
	$token = str_replace("Bearer ", "", $args["token"]);
	try {
		$decoded = JWT::decode($token, new Key($_SERVER["JWT_SECRET_KEY"], $_SERVER["JWT_ALGORITHM"]));
		$decoded->data->jwt = $args["token"];
		$resp->ok = true;
		$resp->msg = "";
		$resp->data = $decoded->data;
	} catch (\Throwable $th) {
		$resp->data = false;
	}

	$response->getBody()->write(json_encode($resp));
	return $response
		->withHeader("Content-Type", "application/json")
		->withStatus($resp->ok ? 200 : 409);
});

//[POST]

$app->post("/user", function (Request $request, Response $response, array $args) {
	$fields = $request->getParsedBody();

	$verificar = [
		"email" => [
			"type" => "string",
			"isValidMail" => true,
			"unique" => "users"
		],
		"firstname" => [
			"type" => "string",
			"min" => 3,
			"max" => 50
		],
		"lastname" => [
			"type" => "string",
			"min" => 3,
			"max" => 50
		],
		"password" => [
			"type" => "string",
			"min" => 3,
			"max" => 20
		]
	];

	$validacion = new Validate($this->get("db"));
	$validacion->validar($fields, $verificar);

	$resp = null;

	if ($validacion->hasErrors()) {
		$resp = $validacion->getErrors();
	} else {
		$users = new Users($this->get("db"));
		$resp = $users->setUser($fields)->getResult();
	}

	$response->getBody()->write(json_encode($resp));
	return $response
		->withHeader("Content-Type", "application/json")
		->withStatus($resp->ok ? 200 : 409);
});

$app->post("/user/login", function (Request $request, Response $response, array $args) {
	$fields = $request->getParsedBody();

	$verificar = [
		"email" => [
			"type" => "string",
			"isValidMail" => true
		],
		"password" => [
			"type" => "string",
			"min" => 3,
			"max" => 20
		]
	];

	$validacion = new Validate();
	$validacion->validar($fields, $verificar);

	$resp = new \stdClass();
	$resp->ok = false;
	$resp->msg = "Nombre de usuario o contraseña incorrecto.";
	$resp->data = null;

	if ($validacion->hasErrors()) {
		$resp = $validacion->getErrors();
	} else {
		$users = new Users($this->get("db"));
		$existe = $users->userExist($fields["email"]);
		if ($existe && password_verify($fields["password"], $existe->password)) {
			$iss = "https://vivisanfrancisco.com";
			$aud = "https://vivisanfrancisco.com";
			$iat = time();
			$exp = $iat + (3600 * 2); // Expire (2Hs)
			$nbf = $iat;
			$token = array(
				"iss" => $iss,
				"aud" => $aud,
				"iat" => $iat,
				"exp" => $exp,
				"nbf" => $nbf,
				"data" => array(
					"id" => $existe->id,
					"firstname" => $existe->firstname,
					"lastname" => $existe->lastname,
					"email" => $existe->email
				)
			);
			unset($existe->password);
			$jwt = JWT::encode($token, $_ENV["JWT_SECRET_KEY"], $_ENV["JWT_ALGORITHM"]);
			$existe->jwt = "Bearer " . $jwt;
			$resp->ok = true;
			$resp->msg = "Usuario autorizado.";
			$resp->data = $existe;
		}
	}

	$response->getBody()->write(json_encode($resp));
	return $response
		->withHeader("Content-Type", "application/json")
		->withStatus($resp->ok ? 200 : 401);
});

$app->post("/user/register", function (Request $request, Response $response, array $args) {
	$fields = $request->getParsedBody();

	$verificar = [
		"email" => [
			"type" => "string",
			"isValidMail" => true,
			"unique" => "users"
		],
		"firstname" => [
			"type" => "string",
			"min" => 3,
			"max" => 50
		],
		"lastname" => [
			"type" => "string",
			"min" => 3,
			"max" => 50
		],
		"password" => [
			"type" => "string",
			"min" => 3,
			"max" => 20
		]
	];

	$validacion = new Validate($this->get("db"));
	$validacion->validar($fields, $verificar);

	$resp = null;

	if ($validacion->hasErrors()) {
		$resp = $validacion->getErrors();
	} else {
		$fields["token"] = Prepare::randomString();
		$users = new Users($this->get("db"));
		$resp = $users->setRegister($fields)->getResult();
		//Envío de mail con el token!!!
		$nombre = Prepare::UCfirst($fields["lastname"]) . ", " . Prepare::UCfirst($fields["firstname"]);
		$resp->mailSend = sendTokenRegister($fields["email"], $nombre, $fields["token"]);
	}

	$response->getBody()->write(json_encode($resp));
	return $response
		->withHeader("Content-Type", "application/json")
		->withStatus($resp->ok ? 200 : 409);
});

$app->post("/user/password/recover", function (Request $request, Response $response, array $args) {
	$fields = $request->getParsedBody();

	$verificar = [
		"email" => [
			"type" => "string",
			"isValidMail" => true
		]
	];

	$validacion = new Validate($this->get("db"));
	$validacion->validar($fields, $verificar);

	$resp = null;

	if ($validacion->hasErrors()) {
		$resp = $validacion->getErrors();
	} else {
		$fields["token"] = Prepare::randomString();
		$users = new Users($this->get("db"));
		$resp = $users->setTempRecovery($fields); //->getResult();
		//Envío de mail con el token!!!
		if ($resp->ok) {
			$resp->mailSend = sendTokenRecover($fields["email"], $fields["token"]);
		}
	}

	$response->getBody()->write(json_encode($resp));
	return $response
		->withHeader("Content-Type", "application/json")
		->withStatus($resp->ok ? 200 : 409);
});

//[PATCH]

$app->patch("/user/password", function (Request $request, Response $response, array $args) {
	$fields = $request->getParsedBody();

	$verificar = [
		"id" => [
			"type" => "number",
			"min" => 1
		],
		"password" => [
			"type" => "string",
			"min" => 3,
			"max" => 20
		]
	];

	$validacion = new Validate($this->get("db"));
	$validacion->validar($fields, $verificar);

	$resp = null;

	if ($validacion->hasErrors()) {
		$resp = $validacion->getErrors();
	} else {
		$users = new Users($this->get("db"));
		$resp = $users->setNewPassword($fields["id"], $fields["password"])->getResult();
	}

	$response->getBody()->write(json_encode($resp));
	return $response
		->withHeader("Content-Type", "application/json")
		->withStatus($resp->ok ? 200 : 409);
});

$app->patch("/user/password/temp/update", function (Request $request, Response $response, array $args) {
	$fields = $request->getParsedBody();

	$verificar = [
		"id" => [
			"type" => "number",
			"min" => 1
		],
		"iduser" => [
			"type" => "number",
			"min" => 1
		],
		"password" => [
			"type" => "string",
			"min" => 3,
			"max" => 20
		],
		"token" => [
			"type" =>  "string",
			"min" => 10,
			"max" => 10,
			"exist" => "passrecovery"
		]
	];

	$validacion = new Validate($this->get("db"));
	$validacion->validar($fields, $verificar);

	$resp = null;

	if ($validacion->hasErrors()) {
		$resp = $validacion->getErrors();
	} else {
		$users = new Users($this->get("db"));
		$resp = $users->setNewPassword($fields["iduser"], $fields["password"])->getResult();
		if ($resp->ok) {
			$users->deleteTempPassword($fields["id"]);
		}
	}

	$response->getBody()->write(json_encode($resp));
	return $response
		->withHeader("Content-Type", "application/json")
		->withStatus($resp->ok ? 200 : 409);
});


//[DELETE]

$app->delete("/user/{id:[0-9]+}", function (Request $request, Response $response, array $args) {
	$users = new Users($this->get("db"));
	$resp = $users->deleteUser($args["id"])->getResult();
	$response->getBody()->write(json_encode($resp));
	return $response
		->withHeader("Content-Type", "application/json")
		->withStatus($resp->ok ? 200 : 409);
});
