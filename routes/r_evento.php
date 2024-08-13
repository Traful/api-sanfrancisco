
<?php

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Slim\Factory\AppFactory;
use Ramsey\Uuid\Uuid;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;


require_once("objects/mp.php");
require_once("objects/descuentos.php");
require_once("objects/inscripciones.php");
require_once("utils/validate.php");

$app->post("/registrar/evento", function (Request $request, Response $response, array $args) {

    $fields = $request->getParsedBody();
    $uploadedFiles = $request->getUploadedFiles();

    $verificar = [
        "email" => [
            "type" => "string",
            "isValidMail" => true,
        ],/*
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
        ]*/
    ];

    $validacion = new Validate($this->get("db"));
    $validacion->validar($fields, $verificar);

    $resp = new \stdClass();

    if ($validacion->hasErrors()) {
        $resp = $validacion->getErrors();
    } else {
        try {
            $db = $this->get("db");

            $fields["usuario_id"] = $request->getAttribute("jwt")["data"]->id;

            //Buscar los datos del item seleccionado
            $mp = new Mp($db);
            $item = $mp->getItemById($fields["idItem"])->getResult();
            $item = $item->data;
            /*
            $item->id
            $item->titulo
            $item->cantidad
            $item->precio
            */

            $importe = floatval($item->precio);
            $cubierto = false;

            $descuentoData = new \stdClass();
            $descuentoData->realizado = false;
            $descuentoData->codigo = "";

            //Buscar si tiene algun descuento
            if (trim($fields["codigo_descuento"]) !== "") {
                $codigo = trim($fields["codigo_descuento"]);
                $des = new Descuentos($db);
                $descuento = $des->getDescuentoByCodigo($codigo, true)->getResult();
                if (!is_null($descuento->data) && ($descuento->data !== false)) {
                    $descuento = $descuento->data;
                    $importeDescuento = floatval($descuento->importe);
                    $importe = $importe - $importeDescuento; //Se aplica el descuento a la preferencia
                    if ($importe === 0) {
                        $cubierto = true;
                    }
                    $descuentoData->realizado = true;
                    $descuentoData->codigo = $codigo;
                } else {
                    $fields["codigo_descuento"] .= " [X]";
                    /*
                        El código de descuento no es válido
                        o bien ya no tiene diponibilidad
                        Se cancela la carga o simplemente no se hace descuento
                        y se continúa?
                    */
                    $resp->ok = false;
                    $resp->msg = "Código de descuento suministrado no válido o deprecado.";
                    $resp->errores = ["Código de descuento suministrado no válido o deprecado."];
                    $response->getBody()->write(json_encode($resp));
                    return $response->withHeader("Content-Type", "application/json")->withStatus(409);
                }
            }

            $idPreferencia = null;

            if (!$cubierto) {
                /*
                MercadoPagoConfig::setAccessToken($_ENV["MP_ACCESS_TOKEN"]);
                //Generar una nueva preferencia
                $client = new PreferenceClient();
                $uuid4 = Uuid::uuid4();
                $preference = $client->create(
                    [
                        "items" => array(
                            array(
                                "id" => $uuid4,
                                "title" => $item->titulo,
                                "description" => "",
                                "picture_url" => "",
                                "category_id" => "",
                                "quantity" => $item->cantidad,
                                "unit_price" => $importe,
                                "currency_id" => "ARS"
                            )
                        )

                ]);
                $preference->back_urls = array(
                    "success" => "https://hans.net.ar/api-sanfrancisco/mp/success",
                    "failure" => "https://hans.net.ar/failure",
                    "pending" => "https://hans.net.ar/pending"
                );
                $preference->auto_return = "all";
                $preference->external_reference = "asdad";
                $preference->notification_url = "https://hans.net.ar/api-sanfrancisco/mp/notificaciones";
                $idPreferencia = $preference->id;
                */

                $uuid4 = Uuid::uuid4();
                $curl = curl_init();

                curl_setopt_array($curl, array(
                    CURLOPT_URL => 'https://api.mercadopago.com/checkout/preferences',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_TIMEOUT => 10,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => 'POST',
                    CURLOPT_POSTFIELDS => '{
                        "items": [
                            {
                                "id": "' . $uuid4 . '",
                                "title": "' . $item->titulo . '",
                                "description": "10K Del Maestro",
                                "picture_url": "https://vivisanfrancisco.com/ticket/assets/images/correr.jpg",
                                "category_id": "Ticket de Carrera",
                                "quantity": 1,
                                "currency_id": "ARS",
                                "unit_price": ' . $importe . '
                            }
                        ],
                        "back_urls": {
                            "success": "https://vivisanfrancisco.com/ticket/inscripcion-exito",
                            "failure": "https://vivisanfrancisco.com/ticket/fallo-pago",
                            "pending": "https://vivisanfrancisco.com/ticket/pendiente-pago"
                        },
                        "auto_return": "all",
                        "external_reference": "' . $item->titulo . '",
                        "notification_url": "https://vivisanfrancisco.com/api-sanfrancisco/mp/notificaciones"
                        
                            }',
                    CURLOPT_HTTPHEADER => array(
                        'Authorization: Bearer ' . $_ENV["MP_ACCESS_TOKEN"],
                        'Content-Type: application/json'
                    ),
                ));
                $responseCurl = curl_exec($curl);
                curl_close($curl);
                $responseCurl = json_decode($responseCurl);
                $idPreferencia = $responseCurl->id;
            }

            $fields["idPago"] = $idPreferencia;
            $fields["importe"] = $importe;
            $fields["pagado"] = $cubierto;

            //Manejo del archivo de certificado médico
            $fields["certificado_medico"] = null;
            $fields["nombre_archivo"] = null;
            $fields["tipo_mime"] = null;

            if (isset($uploadedFiles["certificado_medico"])) {
                if ($uploadedFiles["certificado_medico"]->getError() === UPLOAD_ERR_OK) {
                    $certificado_medico = $uploadedFiles["certificado_medico"]->getStream()->getContents();
                    $fields["certificado_medico"] = $certificado_medico;
                    $name_archive = $uploadedFiles["certificado_medico"]->getClientFilename();
                    $type_archive = $uploadedFiles["certificado_medico"]->getClientMediaType();
                    $fields["nombre_archivo"] = $name_archive;
                    $fields["tipo_mime"] = $type_archive;
                }
            }

            $ins = new Inscripciones($db);
            $resp = $ins->setInscripcion($fields)->getResult();
            $resp->data["idPreferencia"] = $idPreferencia;

            //Si la inscripción se realiza correctamente y se utilizo un descuento se actualzia su uso
            if ($descuentoData->realizado === true) {
                $des = new Descuentos($db);
                $des->descontarDisponibilidad($descuentoData->codigo);
            }
        } catch (Exception $e) {
            $resp->ok = false;
            $resp->msg = $e->getMessage();
            $resp->errores = [$e->getMessage()];
        }
    }

    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 409);
});

$app->put("/inscripcion/pago/{idIncripto}", function (Request $request, Response $response, array $args) {
    $idIncripto = $args["idIncripto"];
    $db = $this->get("db");
    $fields = $request->getParsedBody();


    $query = "UPDATE incripciones SET idItem = :idItem, idPago = :idPago WHERE id = :id";
    $stmt = $db->prepare($query);

    // Bind de los parámetros
    $stmt->bindParam(":idItem", $fields["idItem"]);
    $stmt->bindParam(":idPago", $fields["idPago"]);
    $stmt->bindParam(":id", $idIncripto);
    if ($stmt->execute()) {
        $response->getBody()->write(json_encode(["message" => "Inscripcion updateada con exito"]));
        return $response->withHeader("Content-Type", "application/json")->withStatus(200);
    } else {
        throw new Exception("Error al ejecutar la consulta");
    }
});



$app->get("/ver/archivo/{id}", function (Request $request, Response $response, array $args) {
    $id = $args["id"];

    // Asumiendo que tienes una conexión a la base de datos en $db
    $db = $this->get("db");

    // Consulta para obtener el archivo PDF
    $query = "SELECT certificado_medico, nombre_archivo, tipo_mime FROM inscripciones WHERE id =  :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $id);
    $stmt->execute();
    $archivo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($archivo) {
        // Establecer el tipo MIME y el nombre del archivo en los encabezados
        $response = $response->withHeader("Content-Type", $archivo["tipo_mime"]);
        $response = $response->withHeader("Content-Disposition", "inline; filename='" . $archivo["nombre_archivo"] . "'");

        // Escribir el contenido del archivo en el cuerpo de la respuesta
        $response->getBody()->write($archivo["certificado_medico"]);
    } else {
        // Si el archivo no se encuentra, responder con un 404
        $response = $response->withStatus(404)->withHeader("Content-Type", "text/plain");
        $response->getBody()->write("Archivo no encontrado");
    }

    return $response;
});
