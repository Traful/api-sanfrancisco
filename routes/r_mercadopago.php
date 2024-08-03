<?php

// SDK de Mercado Pago
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;

use \Psr\Http\Message\ResponseInterface as Response;
use \Psr\Http\Message\ServerRequestInterface as Request;
// Agrega credenciales
MercadoPagoConfig::setAccessToken("APP_USR-8805034183291465-072923-c1787b979d0bbe1e4c4a10b16b50326f-1924207390");

$app->get("/mercadopago/item", function (Request $request, Response $response, array $args) {

    // Crear una preferencia
    //$preference = new MercadoPago\Preference();
    $client = new PreferenceClient();
    $preference = $client->create([
        "items" => array(
            array(
                "title" => "Ticket 10K",
                "quantity" => 1,
                "unit_price" => 10000
            )
        )
    ]);
    $preference->back_urls = array(
        "success" => "http://vivisanfrancisco.com/api-sanfrancisco/mercadopago/success",
        "failure" => "http://vivisanfrancisco.com/api-sanfrancisco/mercadopago/failure",
        "pending" => "http://vivisanfrancisco.com/api-sanfrancisco/mercadopago/pending"
    );
    $preference->auto_return = "approved";

    echo "ID de la preferencia: " . $preference->id;
    $response->getBody()->write("ID de la preferencia: " . $preference->id);

    return $response
        ->withStatus(200);
});


//ID de la preferencia: 109498109-c4479c3e-e545-44fa-b723-e5bc7d771c45 vivisanfrancisco.com/api-sanfrancisco

/*$app->post("/mercadopago/success", function (Request $request, Response $response, array $args) {
    $fields = $request->getParsedBody();
    var_dump($fields);
    return $response
        ->withStatus(200);
});*/
$app->post("/mercadopago/success", function (Request $request, Response $response, array $args) {
    // Obtener los datos de la solicitud POST
    $fields = $request->getParsedBody();

    // Extraer los parámetros específicos del SDK de Mercado Pago
    $paymentId = isset($fields['payment_id']) ? $fields['payment_id'] : 'No disponible';
    $status = isset($fields['status']) ? $fields['status'] : 'No disponible';
    $externalReference = isset($fields['external_reference']) ? $fields['external_reference'] : 'No disponible';
    $merchantOrderId = isset($fields['merchant_order_id']) ? $fields['merchant_order_id'] : 'No disponible';

    // Formatear los datos para escribir en el archivo
    $data = "Payment ID: $paymentId\n";
    $data .= "Status: $status\n";
    $data .= "External Reference: $externalReference\n";
    $data .= "Merchant Order ID: $merchantOrderId\n";
    $data .= "--------------------------\n"; // Separador para diferentes registros

    // Especificar la ruta y el nombre del archivo
    $filePath = 'success.txt'; // Reemplaza con la ruta deseada

    // Abrir el archivo para escritura. Crea el archivo si no existe.
    $file = fopen($filePath, 'a'); // 'a' para añadir al final del archivo

    if ($file) {
        // Escribir los datos en el archivo
        fwrite($file, $data);

        // Cerrar el archivo
        fclose($file);
    } else {
        // Manejar el error si no se puede abrir el archivo
        echo("No se pudo abrir el archivo para escritura: $filePath");
    }

    return $response->withStatus(200);
});
$app->post("/mercadopago/failure", function (Request $request, Response $response, array $args) {
    // Obtener los datos de la solicitud POST
    $fields = $request->getParsedBody();

    // Extraer los parámetros específicos del SDK de Mercado Pago
    $paymentId = isset($fields['payment_id']) ? $fields['payment_id'] : 'No disponible';
    $status = isset($fields['status']) ? $fields['status'] : 'No disponible';
    $externalReference = isset($fields['external_reference']) ? $fields['external_reference'] : 'No disponible';
    $merchantOrderId = isset($fields['merchant_order_id']) ? $fields['merchant_order_id'] : 'No disponible';

    // Formatear los datos para escribir en el archivo
    $data = "Payment ID: $paymentId\n";
    $data .= "Status: $status\n";
    $data .= "External Reference: $externalReference\n";
    $data .= "Merchant Order ID: $merchantOrderId\n";
    $data .= "--------------------------\n"; // Separador para diferentes registros

    // Especificar la ruta y el nombre del archivo
    $filePath = 'failure.txt'; // Reemplaza con la ruta deseada

    // Abrir el archivo para escritura. Crea el archivo si no existe.
    $file = fopen($filePath, 'a'); // 'a' para añadir al final del archivo

    if ($file) {
        // Escribir los datos en el archivo
        fwrite($file, $data);

        // Cerrar el archivo
        fclose($file);
    } else {
        // Manejar el error si no se puede abrir el archivo
        error_log("No se pudo abrir el archivo para escritura: $filePath");
    }

    return $response->withStatus(200);
});
$app->post("/mercadopago/pending", function (Request $request, Response $response, array $args) {
    // Obtener los datos de la solicitud POST
    $fields = $request->getParsedBody();

    // Extraer los parámetros específicos del SDK de Mercado Pago
    $paymentId = isset($fields['payment_id']) ? $fields['payment_id'] : 'No disponible';
    $status = isset($fields['status']) ? $fields['status'] : 'No disponible';
    $externalReference = isset($fields['external_reference']) ? $fields['external_reference'] : 'No disponible';
    $merchantOrderId = isset($fields['merchant_order_id']) ? $fields['merchant_order_id'] : 'No disponible';

    // Formatear los datos para escribir en el archivo
    $data = "Payment ID: $paymentId\n";
    $data .= "Status: $status\n";
    $data .= "External Reference: $externalReference\n";
    $data .= "Merchant Order ID: $merchantOrderId\n";
    $data .= "--------------------------\n"; // Separador para diferentes registros

    // Especificar la ruta y el nombre del archivo
    $filePath = 'archivos/pending.txt'; // Reemplaza con la ruta deseada

    // Abrir el archivo para escritura. Crea el archivo si no existe.
    $file = fopen($filePath, 'a'); // 'a' para añadir al final del archivo

    if ($file) {
        // Escribir los datos en el archivo
        fwrite($file, $data);

        // Cerrar el archivo
        fclose($file);
    } else {
        // Manejar el error si no se puede abrir el archivo
        error_log("No se pudo abrir el archivo para escritura: $filePath");
    }

    return $response->withStatus(200);
});
