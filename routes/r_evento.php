
<?php

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
//use \utils\Validate;
use \Slim\Factory\AppFactory;

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

    $resp = null;

    if ($validacion->hasErrors()) {
        $resp = $validacion->getErrors();
    } else {
        // carga de db
        try {
            // Asumiendo que tienes una conexión a la base de datos
            $db = $this->get('db');

            $query = "INSERT INTO inscripciones (
                usuario_id, dni, nombre, apellido, fecha_nacimiento, genero, 
                email, telefono, domicilio, ciudad, provincia, pais, 
                codigo_postal, contacto_emergencia_nombre, contacto_emergencia_apellido, 
                contacto_emergencia_telefono, talle_remera, team_agrupacion, 
                categoria_edad, codigo_descuento, certificado_medico, tipo_mime, nombre_archivo, acepta_promocion
            ) VALUES (
                :usuario_id, :dni, :nombre, :apellido, :fecha_nacimiento, :genero,
                :email, :telefono, :domicilio, :ciudad, :provincia, :pais,
                :codigo_postal, :contacto_emergencia_nombre, :contacto_emergencia_apellido,
                :contacto_emergencia_telefono, :talle_remera, :team_agrupacion,
                :categoria_edad, :codigo_descuento, :certificado_medico, :tipo_mime, :nombre_archivo, :acepta_promocion
            )";

            $stmt = $db->prepare($query);

            // Bind de los parámetros
            $stmt->bindParam(':usuario_id', $request->getAttribute('jwt')["data"]->id);
            $stmt->bindParam(':dni', $fields['dni']);
            $stmt->bindParam(':nombre', $fields['nombre']);
            $stmt->bindParam(':apellido', $fields['apellido']);
            $stmt->bindParam(':fecha_nacimiento', $fields['fecha_nacimiento']);
            $stmt->bindParam(':genero', $fields['genero']);
            $stmt->bindParam(':email', $fields['email']);
            $stmt->bindParam(':telefono', $fields['telefono']);
            $stmt->bindParam(':domicilio', $fields['domicilio']);
            $stmt->bindParam(':ciudad', $fields['ciudad']);
            $stmt->bindParam(':provincia', $fields['provincia']);
            $stmt->bindParam(':pais', $fields['pais']);
            $stmt->bindParam(':codigo_postal', $fields['codigo_postal']);
            $stmt->bindParam(':contacto_emergencia_nombre', $fields['contacto_emergencia_nombre']);
            $stmt->bindParam(':contacto_emergencia_apellido', $fields['contacto_emergencia_apellido']);
            $stmt->bindParam(':contacto_emergencia_telefono', $fields['contacto_emergencia_telefono']);
            $stmt->bindParam(':talle_remera', $fields['talle_remera']);
            $stmt->bindParam(':team_agrupacion', $fields['team_agrupacion']);
            $stmt->bindParam(':categoria_edad', $fields['categoria_edad']);
            $stmt->bindParam(':codigo_descuento', $fields['codigo_descuento']);

            //Manejo del archivo de certificado médico
            if (isset($uploadedFiles['certificadoMedico'])) {
                $certificadoMedico = $uploadedFiles['certificadoMedico'];
                if ($certificadoMedico->getError() === UPLOAD_ERR_OK) {
                    $certificado_medico = $certificadoMedico->getStream()->getContents();
                    $stmt->bindParam(':certificado_medico', $certificado_medico, PDO::PARAM_LOB);
                    $name_archive = $certificadoMedico->getClientFilename();
                    $type_archive = $certificadoMedico->getClientMediaType();
                    $stmt->bindParam(':nombre_archivo', $name_archive, PDO::PARAM_STR);
                    $stmt->bindParam(':tipo_mime', $type_archive, PDO::PARAM_STR);
                } else {
                    $stmt->bindParam(':certificado_medico', null, PDO::PARAM_NULL);
                    $stmt->bindParam(':nombre_archivo', null, PDO::PARAM_NULL);
                    $stmt->bindParam(':tipo_mime', null, PDO::PARAM_NULL);
                }
            } else {
                $stmt->bindParam(':certificado_medico', null, PDO::PARAM_NULL);
                $stmt->bindParam(':nombre_archivo', null, PDO::PARAM_NULL);
                $stmt->bindParam(':tipo_mime', null, PDO::PARAM_NULL);
            }

            $acepta_promocion = isset($fields['aceptaImagen']) && $fields['aceptaImagen'] ? 1 : 0;
            $stmt->bindParam(':acepta_promocion', $acepta_promocion);

            // Ejecutar la consulta
            if ($stmt->execute()) {
                $lastId  = $db->lastInsertId();
                $response->getBody()->write(json_encode(['message' => 'Inscripción creada con éxito', 'id' =>  $lastId]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
            } else {
                throw new Exception("Error al ejecutar la consulta");
            }
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
});

$app->put("/inscripcion/pago/{idIncripto}", function (Request $request, Response $response, array $args) {
    $idIncripto = $args['idIncripto'];
    $db = $this->get('db');
    $fields = $request->getParsedBody();


    $query = "UPDATE incripciones SET idItem = :idItem, idPago = :idPago WHERE id = :id";
    $stmt = $db->prepare($query);

    // Bind de los parámetros
    $stmt->bindParam(':idItem', $fields['idItem']);
    $stmt->bindParam(':idPago', $fields['idPago']);
    $stmt->bindParam(':id', $idIncripto);
    if ($stmt->execute()) {
        $response->getBody()->write(json_encode(['message' => 'Inscripcion updateada con exito']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } else {
        throw new Exception("Error al ejecutar la consulta");
    }
});


$app->get("/ver/archivo/{id}", function (Request $request, Response $response, array $args) {
    $id = $args['id'];

    // Asumiendo que tienes una conexión a la base de datos en $db
    $db = $this->get('db');

    // Consulta para obtener el archivo PDF
    $query = "SELECT certificado_medico, nombre_archivo, tipo_mime FROM inscripciones WHERE id =  :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $archivo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($archivo) {
        // Establecer el tipo MIME y el nombre del archivo en los encabezados
        $response = $response->withHeader('Content-Type', $archivo['tipo_mime']);
        $response = $response->withHeader('Content-Disposition', 'inline; filename="' . $archivo['nombre_archivo'] . '"');

        // Escribir el contenido del archivo en el cuerpo de la respuesta
        $response->getBody()->write($archivo['certificado_medico']);
    } else {
        // Si el archivo no se encuentra, responder con un 404
        $response = $response->withStatus(404)->withHeader('Content-Type', 'text/plain');
        $response->getBody()->write("Archivo no encontrado");
    }

    return $response;
});
