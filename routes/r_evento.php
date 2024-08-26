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

    $resp = new \stdClass();
    $resp->ok = false;
    $resp->msg = "";
    $resp->errores = [];
    $resp->debug_info = [];

    try {
        $db = $this->get("db");

        $resp->debug_info['received_fields'] = $fields;
        $resp->debug_info['received_files'] = array_keys($uploadedFiles);

        // Validación
        $verificar = [
            "email" => [
                "type" => "string",
                "isValidMail" => true,
            ],
            // Añade aquí más reglas de validación según sea necesario
        ];
        $validacion = new Validate($db);
        $validacion->validar($fields, $verificar);

        if ($validacion->hasErrors()) {
            $resp->errores = $validacion->getErrors();
            throw new Exception("Errores de validación: " . json_encode($resp->errores));
        }

        $fields["usuario_id"] = $request->getAttribute("jwt")["data"]->id;

        // Procesar archivos
        if (isset($uploadedFiles["certificado_medico"])) {
            if ($uploadedFiles["certificado_medico"]->getError() === UPLOAD_ERR_OK) {
                $fields["certificado_medico"] = $uploadedFiles["certificado_medico"]->getStream()->getContents();
                $fields["nombre_archivo"] = $uploadedFiles["certificado_medico"]->getClientFilename();
                $fields["tipo_mime"] = $uploadedFiles["certificado_medico"]->getClientMediaType();
            } else {
                throw new Exception("Error al subir el certificado médico: " . $uploadedFiles["certificado_medico"]->getError());
            }
        }

        if (isset($uploadedFiles["certificado_discapacidad"])) {
            if ($uploadedFiles["certificado_discapacidad"]->getError() === UPLOAD_ERR_OK) {
                $fields["certificado_discapacidad"] = $uploadedFiles["certificado_discapacidad"]->getStream()->getContents();
                $fields["nombre_archivo_discapacidad"] = $uploadedFiles["certificado_discapacidad"]->getClientFilename();
                $fields["tipo_mime_discapacidad"] = $uploadedFiles["certificado_discapacidad"]->getClientMediaType();
            } else {
                throw new Exception("Error al subir el certificado de discapacidad: " . $uploadedFiles["certificado_discapacidad"]->getError());
            }
        }

        // Buscar los datos del item seleccionado
        $mp = new Mp($db);
        $item = $mp->getItemById($fields["idItem"])->getResult();
        $resp->debug_info['item_result'] = $item;

        if (!$item->ok || !$item->data) {
            throw new Exception("No se pudo obtener la información del item seleccionado");
        }
        $item = $item->data;

        $importe = floatval($item->precio);
        $cubierto = false;

        $descuentoData = new \stdClass();
        $descuentoData->realizado = false;
        $descuentoData->codigo = "";

        // Procesar descuento
        if (!empty(trim($fields["codigo_descuento"]))) {
            $codigo = trim($fields["codigo_descuento"]);
            $des = new Descuentos($db);
            $descuento = $des->getDescuentoByCodigo($codigo, true)->getResult();
            $resp->debug_info['discount_result'] = $descuento;

            if (!is_null($descuento->data) && ($descuento->data !== false)) {
                $descuento = $descuento->data;
                $importeDescuento = floatval($descuento->importe);
                $importe = max(0, $importe - $importeDescuento);
                if ($importe === 0) {
                    $cubierto = true;
                }
                $descuentoData->realizado = true;
                $descuentoData->codigo = $codigo;
            } else {
                $fields["codigo_descuento"] .= " [X]";
                $resp->errores[] = "Código de descuento no válido o agotado";
            }
        }

        $idPreferencia = null;

        if (!$cubierto) {
            $uuid4 = Uuid::uuid4();
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.mercadopago.com/checkout/preferences',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode([
                    "items" => [
                        [
                            "id" => $uuid4,
                            "title" => $item->titulo,
                            "description" => "10K Del Maestro",
                            "picture_url" => "https://vivisanfrancisco.com/ticket/assets/images/correr.jpg",
                            "category_id" => "Ticket de Carrera",
                            "quantity" => 1,
                            "currency_id" => "ARS",
                            "unit_price" => $importe
                        ]
                    ],
                    "back_urls" => [
                        "success" => "https://vivisanfrancisco.com/ticket/inscripcion-exito",
                        "failure" => "https://vivisanfrancisco.com/ticket/fallo-pago",
                        "pending" => "https://vivisanfrancisco.com/ticket/pendiente-pago"
                    ],
                    "auto_return" => "all",
                    "external_reference" => $item->titulo,
                    "notification_url" => "https://vivisanfrancisco.com/api-sanfrancisco/mp/notificaciones"
                ]),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer ' . $_ENV["MP_ACCESS_TOKEN"],
                    'Content-Type: application/json'
                ),
            ));

            $responseCurl = curl_exec($curl);
            $resp->debug_info['mercadopago_response'] = $responseCurl;

            if (curl_errno($curl)) {
                throw new Exception('Error en MercadoPago: ' . curl_error($curl));
            }
            curl_close($curl);

            $responseCurl = json_decode($responseCurl);
            if (!isset($responseCurl->id)) {
                throw new Exception('Error al obtener ID de preferencia de MercadoPago');
            }
            $idPreferencia = $responseCurl->id;
        }

        $fields["idPago"] = $idPreferencia;
        $fields["importe"] = $importe;
        $fields["pagado"] = $cubierto;

        // Insertar inscripción
        $ins = new Inscripciones($db);
        $resultadoInscripcion = $ins->setInscripcion($fields);
        $resp->debug_info['inscription_result'] = $resultadoInscripcion;

        if (!$resultadoInscripcion->ok) {
            throw new Exception("Error al insertar la inscripción: " . $resultadoInscripcion->msg);
        }

        // Si la inscripción se realiza correctamente y se utilizó un descuento, se actualiza su uso
        if ($descuentoData->realizado === true) {
            $des = new Descuentos($db);
            $resultadoDescuento = $des->descontarDisponibilidad($descuentoData->codigo);
            $resp->debug_info['discount_update_result'] = $resultadoDescuento;
        }

        $resp->ok = true;
        $resp->msg = "Inscripción procesada con éxito";
        $resp->data = [
            "idPreferencia" => $idPreferencia,
            "inscripcionId" => $resultadoInscripcion->data["id"] ?? null
        ];
    } catch (Exception $e) {
        $resp->msg = "Error al procesar la inscripción: " . $e->getMessage();
        $resp->errores[] = $e->getMessage();
        $resp->debug_info['exception'] = [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
        error_log("Error en inscripción: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    }

    // Añadir información de SQL si está disponible
    if (isset($db)) {
        $resp->debug_info['last_query'] = $db->last_query ?? 'No disponible';
        $resp->debug_info['last_params'] = $db->last_params ?? 'No disponible';
    }

    $response->getBody()->write(json_encode($resp));
    return $response
        ->withHeader("Content-Type", "application/json")
        ->withStatus($resp->ok ? 200 : 400);
});

$app->post("/registrar/evento-especial", function (Request $request, Response $response, array $args) {
    $fields = $request->getParsedBody();
    $uploadedFiles = $request->getUploadedFiles();

    $verificar = [
        "email" => [
            "type" => "string",
            "isValidMail" => true,
        ],
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

            // Para categorías especiales, el pago es gratuito
            $fields["idPago"] = null;
            $fields["importe"] = 0;
            $fields["pagado"] = true;

            // Manejo de archivos
            if (isset($uploadedFiles["certificado_medico"])) {
                if ($uploadedFiles["certificado_medico"]->getError() === UPLOAD_ERR_OK) {
                    $fields["certificado_medico"] = $uploadedFiles["certificado_medico"]->getStream()->getContents();
                    $fields["nombre_archivo"] = $uploadedFiles["certificado_medico"]->getClientFilename();
                    $fields["tipo_mime"] = $uploadedFiles["certificado_medico"]->getClientMediaType();
                }
            }

            if (isset($uploadedFiles["certificado_discapacidad"])) {
                if ($uploadedFiles["certificado_discapacidad"]->getError() === UPLOAD_ERR_OK) {
                    $fields["certificado_discapacidad"] = $uploadedFiles["certificado_discapacidad"]->getStream()->getContents();
                    $fields["nombre_archivo_discapacidad"] = $uploadedFiles["certificado_discapacidad"]->getClientFilename();
                    $fields["tipo_mime_discapacidad"] = $uploadedFiles["certificado_discapacidad"]->getClientMediaType();
                }
            }

            $ins = new Inscripciones($db);
            $resultado = $ins->setInscripcion($fields);
            if ($resultado->ok) {
                $resp = new stdClass();
                $resp->ok = true;
                $resp->msg = "Inscripción registrada con éxito";
                $resp->data = $resultado->data;

                // Enviar correo de confirmación para inscripciones especiales
                mailInscripcionEspecialOk($fields);
            } else {
                throw new Exception($resultado->msg);
            }
        } catch (Exception $e) {
            $resp = new stdClass();
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

    $stmt->bindParam(":idItem", $fields["idItem"]);
    $stmt->bindParam(":idPago", $fields["idPago"]);
    $stmt->bindParam(":id", $idIncripto);
    if ($stmt->execute()) {
        $response->getBody()->write(json_encode(["message" => "Inscripcion actualizada con éxito"]));
        return $response->withHeader("Content-Type", "application/json")->withStatus(200);
    } else {
        throw new Exception("Error al ejecutar la consulta");
    }
});

$app->get("/ver/archivo/{id}", function (Request $request, Response $response, array $args) {
    $id = $args["id"];
    $db = $this->get("db");

    $query = "SELECT certificado_medico, nombre_archivo, tipo_mime FROM inscripciones WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $id);
    $stmt->execute();
    $archivo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($archivo) {
        $response = $response->withHeader("Content-Type", $archivo["tipo_mime"]);
        $response = $response->withHeader("Content-Disposition", "inline; filename='" . $archivo["nombre_archivo"] . "'");
        $response->getBody()->write($archivo["certificado_medico"]);
    } else {
        $response = $response->withStatus(404)->withHeader("Content-Type", "text/plain");
        $response->getBody()->write("Archivo no encontrado");
    }

    return $response;
});

$app->get("/ver/archivo-discapacidad/{id}", function (Request $request, Response $response, array $args) {
    $id = $args["id"];
    $db = $this->get("db");

    $query = "SELECT certificado_discapacidad, nombre_archivo_discapacidad, tipo_mime_discapacidad FROM inscripciones WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $id);
    $stmt->execute();
    $archivo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($archivo && $archivo["certificado_discapacidad"]) {
        $response = $response->withHeader("Content-Type", $archivo["tipo_mime_discapacidad"]);
        $response = $response->withHeader("Content-Disposition", "inline; filename='" . $archivo["nombre_archivo_discapacidad"] . "'");
        $response->getBody()->write($archivo["certificado_discapacidad"]);
    } else {
        $response = $response->withStatus(404)->withHeader("Content-Type", "text/plain");
        $response->getBody()->write("Archivo de certificado de discapacidad no encontrado");
    }

    return $response;
});
