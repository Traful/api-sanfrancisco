<?php

use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Exception\HttpNotFoundException;

// Autoload y requires necesarios
require(__DIR__ . "/vendor/autoload.php");
require_once(__DIR__ . "/utils/validate.php");
require_once(__DIR__ . "/utils/prepare.php");
require_once(__DIR__ . "/objects/users.php");

// Configuración inicial
$container = new Container();

// Cargar variables de entorno
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Configuración de la base de datos
$container->set("db", function () {
    try {
        $con = array(
            "host" => $_ENV["DB_HOST"] ?? "193.203.175.122",
            "dbname" => $_ENV["DB_NAME"] ?? "u565673608_ticket",
            "user" => $_ENV["DB_USER"] ?? "u565673608_ticket",
            "pass" => $_ENV["DB_PASS"] ?? "0Hs=fvk22"
        );

        $pdo = new PDO(
            "mysql:host=" . $con["host"] . ";dbname=" . $con["dbname"],
            $con["user"],
            $con["pass"],
            array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
        );

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
        return $pdo;
    } catch (\PDOException $e) {
        error_log("Error de conexión a la base de datos: " . $e->getMessage());
        throw new \Exception("Error de conexión a la base de datos");
    }
});

// Crear la aplicación
AppFactory::setContainer($container);
$app = AppFactory::create();

// Configurar base path
$app->setBasePath(preg_replace("/(.*)\/.*/", "$1", $_SERVER["SCRIPT_NAME"]));

// 1. Body Parsing Middleware
$app->addBodyParsingMiddleware();

// 2. CORS Middleware (solo el middleware global, sin rutas OPTIONS específicas)
$app->add(function (Request $request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
        ->withHeader('Access-Control-Allow-Credentials', 'true')
        ->withHeader('Access-Control-Max-Age', '86400');
});

// 3. Routing Middleware
$app->addRoutingMiddleware();

// 4. JWT Authentication Middleware
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
        "/" . basename(dirname($_SERVER["PHP_SELF"])) . "/inscritos",
    ],
    "secret" => $_ENV["JWT_SECRET_KEY"],
    "algorithm" => $_ENV["JWT_ALGORITHM"],
    "attribute" => "jwt",
    "error" => function ($response, $arguments) {
        $data["ok"] = false;
        $data["msg"] = $arguments["message"];
        $response->getBody()->write(
            json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );
        return $response->withHeader("Content-Type", "application/json");
    }
]));

// 5. Error Middleware
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

// Ruta básica de la API
$app->get("/", function (Request $request, Response $response, array $args) {
    $response->getBody()->write("API-San Francisco");
    return $response;
});

// Incluir rutas
require_once(__DIR__ . "/routes/r_users.php");
//require_once(__DIR__ . "/routes/r_evento.php");
//require_once(__DIR__ . "/routes/r_mercadopago.php");
//require_once(__DIR__ . "/routes/r_mp.php");
//require_once(__DIR__ . "/routes/r_estadisticas.php");
//require_once(__DIR__ . "/routes/r_inscripciones.php");
//require_once(__DIR__ . "/routes/r_descuentos.php");

// Manejar rutas no encontradas - NOTA: Ya no incluimos OPTIONS aquí
$app->map(["GET", "POST", "PUT", "DELETE", "PATCH"], "/{routes:.+}", function ($request, $response) {
    throw new HttpNotFoundException($request);
});

// Ejecutar la aplicación
$app->run();