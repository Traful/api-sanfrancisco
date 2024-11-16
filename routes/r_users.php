<?php

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;
use \PHPMailer\PHPMailer\PHPMailer;
use \PHPMailer\PHPMailer\SMTP;
use \PHPMailer\PHPMailer\Exception;

require_once("./utils/validate.php");
require_once("./utils/prepare.php");
require_once("./objects/users.php");

function sendTokenRegister($email, $nombre, $token)
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
		$mail->addAddress($email, $nombre);

		$urlLink = $_ENV["APP_URL"] . "register/token/" . $token;
		$logoUrl = "https://vivisanfrancisco.com/ticket/assets/logonegro-DXNK33qQ.png";

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
		error_log("Error sending registration email: " . $e->getMessage());
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
		$mail->Subject = "Recuperación de contraseña - Sistema de Tickets";
		$body = <<<EOD
            <h3>Recuperación de contraseña</h3>
            <br/>
            <p>Para generar una nueva contraseña, por favor visite este <a href='{$urlLink}' target='_blank' rel='nofollow noopener'>enlace</a>.</p>
        EOD;
		$mail->Body    = $body;
		$mail->AltBody = "Para generar una nueva contraseña, por favor visite este enlace: " . $urlLink;

		$mail->send();
		return true;
	} catch (Exception $e) {
		error_log("Error sending recovery email: " . $e->getMessage());
		return false;
	}
}

// [GET] Routes
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
	try {
		$resp = new \stdClass();
		$users = new Users($this->get("db"));
		$result = $users->getUserTemp($args["token"])->getResult();

		if (isset($result->data->id)) {
			$id = $result->data->id;
			$resp = $users->moveTempUser($id)->getResult();
			$status = 200;
		} else {
			$resp->ok = false;
			$resp->msg = "Token inválido o expirado";
			$resp->data = false;
			$status = 401;
		}

		$response->getBody()->write(json_encode($resp));
		return $response
			->withHeader("Content-Type", "application/json")
			->withStatus($status);
	} catch (Exception $e) {
		$resp = new \stdClass();
		$resp->ok = false;
		$resp->msg = "Error procesando el registro: " . $e->getMessage();
		$resp->data = null;

		$response->getBody()->write(json_encode($resp));
		return $response
			->withHeader("Content-Type", "application/json")
			->withStatus(500);
	}
});

$app->get("/user/password/temp/{token}", function (Request $request, Response $response, array $args) {
	try {
		$resp = new \stdClass();
		$users = new Users($this->get("db"));
		$respT = $users->getUserPasswordTemp($args["token"])->getResult();

		if (isset($respT->data->id)) {
			$resp->ok = true;
			$resp->msg = "";
			$resp->data = $respT->data;
			$status = 200;
		} else {
			$resp->ok = false;
			$resp->msg = "Token inválido o expirado";
			$resp->data = false;
			$status = 401;
		}

		$response->getBody()->write(json_encode($resp));
		return $response
			->withHeader("Content-Type", "application/json")
			->withStatus($status);
	} catch (Exception $e) {
		$resp = new \stdClass();
		$resp->ok = false;
		$resp->msg = "Error procesando la recuperación: " . $e->getMessage();
		$resp->data = null;

		$response->getBody()->write(json_encode($resp));
		return $response
			->withHeader("Content-Type", "application/json")
			->withStatus(500);
	}
});

$app->get("/user/token/validate/{token}", function (Request $request, Response $response, array $args) {
	$resp = new \stdClass();
	try {
		$token = str_replace("Bearer ", "", $args["token"]);
		$decoded = JWT::decode($token, new Key($_ENV["JWT_SECRET_KEY"], $_ENV["JWT_ALGORITHM"]));

		if (time() >= $decoded->exp) {
			throw new \Exception("Token expirado");
		}

		if (empty($decoded->data->id)) {
			throw new \Exception("Datos del token inválidos");
		}

		$resp->ok = true;
		$resp->msg = "";
		$resp->data = $decoded->data;
		$resp->data->jwt = $args["token"];
		$status = 200;
	} catch (\Exception $e) {
		$resp->ok = false;
		$resp->msg = "Token inválido o expirado";
		$resp->data = null;
		error_log("Error validando token: " . $e->getMessage());
		$status = 401;
	}

	$response->getBody()->write(json_encode($resp));
	return $response
		->withHeader("Content-Type", "application/json")
		->withStatus($status);
});

// [POST] Routes
$app->post("/user/login", function (Request $request, Response $response, array $args) {
	try {
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

		$validacion = new Validate($this->get("db"));
		$validacion->validar($fields, $verificar);

		if ($validacion->hasErrors()) {
			return $response
				->withHeader("Content-Type", "application/json")
				->withStatus(400)
				->write(json_encode($validacion->getErrors()));
		}

		$users = new Users($this->get("db"));
		$existe = $users->userExist($fields["email"]);

		$resp = new \stdClass();

		if ($existe && password_verify($fields["password"], $existe->password)) {
			$token = [
				"iss" => $_ENV["APP_URL"],
				"aud" => $_ENV["APP_URL"],
				"iat" => time(),
				"exp" => time() + (3600 * 24),
				"data" => [
					"id" => $existe->id,
					"firstname" => $existe->firstname,
					"lastname" => $existe->lastname,
					"email" => $existe->email
				]
			];

			unset($existe->password);
			$jwt = JWT::encode($token, $_ENV["JWT_SECRET_KEY"], $_ENV["JWT_ALGORITHM"]);
			$existe->jwt = "Bearer " . $jwt;

			$resp->ok = true;
			$resp->msg = "Usuario autenticado correctamente";
			$resp->data = $existe;
			$status = 200;
		} else {
			$resp->ok = false;
			$resp->msg = "Credenciales inválidas";
			$resp->data = null;
			$status = 401;
		}

		$response->getBody()->write(json_encode($resp));
		return $response
			->withHeader("Content-Type", "application/json")
			->withStatus($status);
	} catch (\Exception $e) {
		error_log("Error en login: " . $e->getMessage());
		$resp = new \stdClass();
		$resp->ok = false;
		$resp->msg = "Error en el servidor";
		$resp->data = null;

		$response->getBody()->write(json_encode($resp));
		return $response
			->withHeader("Content-Type", "application/json")
			->withStatus(500);
	}
});

$app->post("/user/register", function (Request $request, Response $response, array $args) {
	try {
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

		if ($validacion->hasErrors()) {
			return $response
				->withHeader("Content-Type", "application/json")
				->withStatus(400)
				->write(json_encode($validacion->getErrors()));
		}

		$fields["token"] = Prepare::randomString();
		$users = new Users($this->get("db"));
		$resp = $users->setRegister($fields)->getResult();

		if ($resp->ok) {
			$nombre = Prepare::UCfirst($fields["lastname"]) . ", " . Prepare::UCfirst($fields["firstname"]);
			$resp->mailSend = sendTokenRegister($fields["email"], $nombre, $fields["token"]);
		}

		$response->getBody()->write(json_encode($resp));
		return $response
			->withHeader("Content-Type", "application/json")
			->withStatus($resp->ok ? 200 : 409);
	} catch (\Exception $e) {
		error_log("Error en registro: " . $e->getMessage());
		$resp = new \stdClass();
		$resp->ok = false;
		$resp->msg = "Error en el registro: " . $e->getMessage();
		$resp->data = null;

		$response->getBody()->write(json_encode($resp));
		return $response
			->withHeader("Content-Type", "application/json")
			->withStatus(500);
	}
});

$app->post("/user/google-login", function (Request $request, Response $response) {
	$resp = new \stdClass();
	try {
		$input = $request->getParsedBody();

		if (
			empty($input['email']) || empty($input['firstname']) ||
			empty($input['lastname']) || empty($input['googleId'])
		) {
			throw new \Exception("Faltan campos requeridos");
		}

		$users = new Users($this->get("db"));
		$userData = $users->findOrCreateGoogleUser($input);

		if (!$userData->ok) {
			throw new \Exception($userData->msg);
		}

		// Generar JWT
		$token = [
			'iss' => $_ENV['APP_URL'],
			'aud' => $_ENV['APP_URL'],
			'iat' => time(),
			'exp' => time() + (3600 * 24), // 24 horas
			'data' => [
				'id' => $userData->data->id,
				'email' => $userData->data->email,
				'firstname' => $userData->data->firstname,
				'lastname' => $userData->data->lastname,
				'tipoUser' => $userData->data->tipoUser,
				'google_id' => $userData->data->google_id
			]
		];

		$jwt = JWT::encode($token, $_ENV['JWT_SECRET_KEY'], $_ENV['JWT_ALGORITHM']);

		$resp->ok = true;
		$resp->msg = 'Login exitoso';
		$resp->data = $userData->data;
		$resp->data->jwt = "Bearer " . $jwt;

		return $response
			->withHeader('Content-Type', 'application/json')
			->withStatus(200)
			->write(json_encode($resp));
	} catch (\Exception $e) {
		error_log("Error en Google login: " . $e->getMessage());
		$resp->ok = false;
		$resp->msg = 'Error en el proceso de login: ' . $e->getMessage();
		$resp->data = null;

		return $response
			->withHeader('Content-Type', 'application/json')
			->withStatus(400)
			->write(json_encode($resp));
	}
});

$app->post("/user/password/recover", function (Request $request, Response $response, array $args) {
	try {
		$fields = $request->getParsedBody();

		$verificar = [
			"email" => [
				"type" => "string",
				"isValidMail" => true
			]
		];

		$validacion = new Validate($this->get("db"));
		$validacion->validar($fields, $verificar);

		if ($validacion->hasErrors()) {
			return $response
				->withHeader("Content-Type", "application/json")
				->withStatus(400)
				->write(json_encode($validacion->getErrors()));
		}

		$fields["token"] = Prepare::randomString();
		$users = new Users($this->get("db"));
		$resp = $users->setTempRecovery($fields);

		if ($resp->ok) {
			$resp->mailSend = sendTokenRecover($fields["email"], $fields["token"]);
		}

		$response->getBody()->write(json_encode($resp));
		return $response
			->withHeader("Content-Type", "application/json")
			->withStatus($resp->ok ? 200 : 409);
	} catch (\Exception $e) {
		error_log("Error en recuperación de contraseña: " . $e->getMessage());
		$resp = new \stdClass();
		$resp->ok = false;
		$resp->msg = "Error en la recuperación de contraseña: " . $e->getMessage();
		$resp->data = null;

		$response->getBody()->write(json_encode($resp));
		return $response
			->withHeader("Content-Type", "application/json")
			->withStatus(500);
	}
});

// [PATCH] Routes
$app->patch("/user/password", function (Request $request, Response $response, array $args) {
	try {
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

		if ($validacion->hasErrors()) {
			return $response
				->withHeader("Content-Type", "application/json")
				->withStatus(400)
				->write(json_encode($validacion->getErrors()));
		}

		$users = new Users($this->get("db"));
		$resp = $users->setNewPassword($fields["id"], $fields["password"])->getResult();

		$response->getBody()->write(json_encode($resp));
		return $response
			->withHeader("Content-Type", "application/json")
			->withStatus($resp->ok ? 200 : 409);
	} catch (\Exception $e) {
		error_log("Error actualizando contraseña: " . $e->getMessage());
		$resp = new \stdClass();
		$resp->ok = false;
		$resp->msg = "Error actualizando contraseña: " . $e->getMessage();
		$resp->data = null;

		$response->getBody()->write(json_encode($resp));
		return $response
			->withHeader("Content-Type", "application/json")
			->withStatus(500);
	}
});

$app->patch("/user/password/temp/update", function (Request $request, Response $response, array $args) {
	try {
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

		if ($validacion->hasErrors()) {
			return $response
				->withHeader("Content-Type", "application/json")
				->withStatus(400)
				->write(json_encode($validacion->getErrors()));
		}

		$users = new Users($this->get("db"));
		$resp = $users->setNewPassword($fields["iduser"], $fields["password"])->getResult();

		if ($resp->ok) {
			$users->deleteTempPassword($fields["id"]);
		}

		$response->getBody()->write(json_encode($resp));
		return $response
			->withHeader("Content-Type", "application/json")
			->withStatus($resp->ok ? 200 : 409);
	} catch (\Exception $e) {
		error_log("Error actualizando contraseña temporal: " . $e->getMessage());
		$resp = new \stdClass();
		$resp->ok = false;
		$resp->msg = "Error actualizando contraseña temporal: " . $e->getMessage();
		$resp->data = null;

		$response->getBody()->write(json_encode($resp));
		return $response
			->withHeader("Content-Type", "application/json")
			->withStatus(500);
	}
});

// [DELETE] Routes
$app->delete("/user/{id:[0-9]+}", function (Request $request, Response $response, array $args) {
	try {
		$users = new Users($this->get("db"));
		$resp = $users->deleteUser($args["id"])->getResult();

		$response->getBody()->write(json_encode($resp));
		return $response
			->withHeader("Content-Type", "application/json")
			->withStatus($resp->ok ? 200 : 409);
	} catch (\Exception $e) {
		error_log("Error eliminando usuario: " . $e->getMessage());
		$resp = new \stdClass();
		$resp->ok = false;
		$resp->msg = "Error eliminando usuario: " . $e->getMessage();
		$resp->data = null;

		$response->getBody()->write(json_encode($resp));
		return $response
			->withHeader("Content-Type", "application/json")
			->withStatus(500);
	}
});
