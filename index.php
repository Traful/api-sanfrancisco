<?php

use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Exception\HttpNotFoundException;

require(__DIR__ . "/vendor/autoload.php"); //Ver composer autoload psr-4

$container = new Container();

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$container->set("db", function () {

	$con = array(
		"host" => "localhost",
		"dbname" => "db_sanfrancisco",
		"user" => "root",
		"pass" => ""
	);

	/*$con = array(
		"host" => "srv448.hstgr.io",
		"dbname" => "u823308621_mp",
		"user" => "u823308621_hansjal",
		"pass" => "p35.*&C5XgV_Z*B"
	);
	$con = array(
		"host" => "localhost",
		"dbname" => "c2640433_db_sf",
		"user" => "c2640433_bd",
		"pass" => "dinipaTU88"
	);*/
	$pdo = new PDO("mysql:host=" . $con["host"] . ";dbname=" . $con["dbname"], $con["user"], $con["pass"], array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	//$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
	$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
	return $pdo;
});

// Set container to create App with on AppFactory
AppFactory::setContainer($container);

$app = AppFactory::create();

$app->setBasePath(preg_replace("/(.*)\/.*/", "$1", $_SERVER["SCRIPT_NAME"]));

$app->addBodyParsingMiddleware();

$app->addRoutingMiddleware();

$app->add(new \Tuupola\Middleware\JwtAuthentication([
	"ignore" => [
		"/" . basename(dirname($_SERVER["PHP_SELF"])) . "/user/login",
		"/" . basename(dirname($_SERVER["PHP_SELF"])) . "/user/register",
		"/" . basename(dirname($_SERVER["PHP_SELF"])) . "/user/password/recover",
		"/" . basename(dirname($_SERVER["PHP_SELF"])) . "/user/password/temp",
		"/" . basename(dirname($_SERVER["PHP_SELF"])) . "/user/token/validate",
		"/" . basename(dirname($_SERVER["PHP_SELF"])) . "/ver/archivo/7",
		"/" . basename(dirname($_SERVER["PHP_SELF"])) . "/mp/notificaciones",
		"/" . basename(dirname($_SERVER["PHP_SELF"])) . "/mp/success",
		"/" . basename(dirname($_SERVER["PHP_SELF"])) . "/estadisticas",
		"/" . basename(dirname($_SERVER["PHP_SELF"])) . "/descuentos",

		// -> esta ruta no está ignorada pero pasa igual, error!!!: /user/register/temp/{token}
	],
	"secret" => $_ENV["JWT_SECRET_KEY"],
	"algorithm" => $_ENV["JWT_ALGORITHM"],
	"attribute" => "jwt", //$decoded = $request->getAttribute("jwt");
	"error" => function ($response, $arguments) {
		$data["ok"] = false;
		$data["msg"] = $arguments["message"];
		$response->getBody()->write(
			json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
		);
		return $response->withHeader("Content-Type", "application/json");
	}
]));

$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$app->options("/{routes:.+}", function ($request, $response, $args) {
	return $response;
});

$app->add(function ($request, $handler) {
	$response = $handler->handle($request);
	return $response
		->withHeader("Access-Control-Allow-Origin", "*")
		->withHeader("Access-Control-Allow-Headers", "X-Requested-With, Content-Type, Accept, Origin, Authorization")
		->withHeader("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, PATCH, OPTIONS");
});

$app->get("/", function (Request $request, Response $response, array $args) {
	$response->getBody()->write("API-San Francisco");
	return $response;
});

require_once("routes/r_users.php");
require_once("routes/r_evento.php");
require_once("routes/r_mercadopago.php");
require_once("routes/r_mp.php");
require_once("routes/r_estadisticas.php");
require_once("routes/r_inscripciones.php");
require_once("routes/r_descuentos.php");

$app->map(["GET", "POST", "PUT", "DELETE", "PATCH"], "/{routes:.+}", function ($request, $response) {
	throw new HttpNotFoundException($request);
});

$app->run();
