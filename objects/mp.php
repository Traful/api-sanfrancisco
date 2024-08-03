<?php
	//namespace objects;

	use MercadoPago\MercadoPagoConfig;
	use MercadoPago\Client\Payment\PaymentClient;
	use MercadoPago\Client\Common\RequestOptions;
	use MercadoPago\Client\Preference\PreferenceClient;
	use MercadoPago\Exceptions\MPApiException;
	use Ramsey\Uuid\Uuid;

	//use objects\Base;
	require_once("objects/base.php");
	//use utils\Prepare;
	require_once("utils/prepare.php");

	class Mp extends Base {
		private $table_name = "items";

		// constructor
		public function __construct($db) {
			MercadoPagoConfig::setAccessToken($_ENV["MP_ACCESS_TOKEN"]);
			// In case you want to test in your local machine first, set runtime enviroment to LOCAL
			// MercadoPagoConfig::setRuntimeEnviroment(MercadoPagoConfig::LOCAL);
			parent::__construct($db);
		}

		public function getItems() {
			$query = "SELECT * FROM $this->table_name ORDER BY id";
			parent::getAll($query);
			return $this;
		}

		public function getItemById($id) {
			$query = "SELECT * FROM $this->table_name WHERE id = :id";
			parent::getOne($query, ["id" => $id]);
			return $this;
		}

		public function getItemByIdMP($idmp) {
			$query = "SELECT * FROM $this->table_name WHERE idmp = :idmp";
			parent::getOne($query, ["idmp" => $idmp]);
			return $this;
		}

		public function getHistorial() {
			$query = "SELECT * FROM historiapagos ORDER BY id DESC";
			parent::getAll($query);
			return $this;
		}

		public function getHistorialByUser($iduser) {
			$query = "SELECT * FROM historiapagos WHERE iduser = :iduser ORDER BY id DESC";
			parent::getOne($query, ["iduser" => $iduser]);
			return $this;
		}

		public function setItem($values) {
			/*
			$client = new PreferenceClient();
			$preference = $client->create(
				[
					"items" => array(
						array(
							"title" => $values["titulo"],
							"quantity" => $values["cantidad"],
							"unit_price" => $values["precio"],
							"currency_id" => "ARS"
						)
					)

			]);
			$preference->back_urls = array(
				"success" => "https://hans.net.ar/success",
				"failure" => "https://hans.net.ar/failure",
				"pending" => "https://hans.net.ar/pending"
			);
			$preference->auto_return = "approved";
			$preference->notification_url = "https://hans.net.ar/api-sanfrancisco/mp/notificaciones";
			$query = "INSERT INTO $this->table_name SET titulo = :titulo, cantidad = :cantidad, precio = :precio, idmp = :idmp, initpoint = :initpoint";
			$values["idmp"] = $preference->id;
			$values["initpoint"] = $preference->init_point;
			*/
			$query = "INSERT INTO $this->table_name SET titulo = :titulo, cantidad = :cantidad, precio = :precio";
			parent::add($query, $values);
			return $this;
		}

		public function setNotificacion($values) {
			$query = "INSERT INTO notificaciones SET fecha = :fecha, hora = :hora, notificacion = :notificacion";
			$fecha = date("Y-m-d");
			$hora = date("H:i:s");
			$v = array(
				"fecha" => $fecha,
				"hora" => $hora,
				"notificacion" => json_encode($values)
			);
			parent::add($query, $v);
			return $this;
		}

		public function procesarPago($values) {
			$result = new \stdClass();
			$result->ok = true;
			$result->msg = "Operación exitosa";
			$result->data = null;

			$client = new PaymentClient();
			$request_options = new RequestOptions();
			$uuid4 = Uuid::uuid4();
			$request_options->setCustomHeaders(["X-Idempotency-Key: " . $uuid4]);

			/*
			Esto es lo que envía actualmente el front
			{
				["token"]=> string(32) "cb940a8b87ba25af49f832903473cd1e"
				["issuer_id"]=> string(1) "3"
				["payment_method_id"]=> string(6) "master"
				["transaction_amount"]=> int(10)
				["installments"]=> int(1)
				["payer"]=> array(2) {
					["email"]=> string(20) "federiconj@gmail.com"
					["identification"]=> array(2) {
						["type"]=> string(3) "DNI"
						["number"]=> string(8) "12345678"
					}
				}
			}
			*/

			/*
			$createRequest = [
				"additional_info" => [
					"items" => [
						[
							"id" => "MLB2907679857",
							"title" => "Point Mini",
							"description" => "Point product for card payments via Bluetooth.",
							"picture_url" => "https://http2.mlstatic.com/resources/frontend/statics/growth-sellers-landings/device-mlb-point-i_medium2x.png",
							"category_id" => "electronics",
							"quantity" => 1,
							"unit_price" => 58.8,
							"type" => "electronics",
							"event_date" => "2023-12-31T09:37:52.000-04:00",
							"warranty" => false,
							"category_descriptor" => [
								"passenger" => [],
								"route" => []
							]
						]
					],
					"payer" => [
						"first_name" => "Test",
						"last_name" => "Test",
						"phone" => [
							"area_code" => 11,
							"number" => "987654321"
						],
						"address" => [
							"street_number" => null
						],
						"shipments" => [
							"receiver_address" => [
								"zip_code" => "12312-123",
								"state_name" => "Rio de Janeiro",
								"city_name" => "Buzios",
								"street_name" => "Av das Nacoes Unidas",
								"street_number" => 3003
							],
							"width" => null,
							"height" => null
						]
					],
				],
				"application_fee" => null,
				"binary_mode" => false,
				"campaign_id" => null,
				"capture" => false,
				"coupon_amount" => null,
				"description" => "Payment for product",
				"differential_pricing_id" => null,
				"external_reference" => "MP0001",
				"installments" => 1,
				"metadata" => null,
				"payer" => [
					"entity_type" => "individual",
					"type" => "customer",
					"email" => "test_user_123@testuser.com",
					"identification" => [
						"type" => "CPF",
						"number" => "95749019047"
					]
				],
				"payment_method_id" => "master",
				"token" => "ff8080814c11e237014c1ff593b57b4d",
				"transaction_amount" => 58.8,
			];
			

			$client->create($createRequest, $request_options);
			*/

			$dataResponse = new \stdClass();
			$dataResponse->id = null;
			$dataResponse->status = null;
			$dataResponse->status_detail = null;

			try {
				//https://github.com/mercadopago/sdk-php/blob/master/src/MercadoPago/Resources/Payment.php

				$dataMp = $values["formData"];
				$response = $client->create($dataMp, $request_options);
				
				$dataResponse->id = $response->id;
				$dataResponse->status = $response->status;
				$dataResponse->status_detail = $response->status_detail;

				$dataResponse->notification_url = $response->notification_url;
				//$dataResponse->callback_url = $response->callback_url;

				switch($response->status) {
					case "rejected":
						$result->ok = false;
						$result->msg = "El pago no fué aprobado";
						break;
					case "pending":
						$result->ok = false;
						$result->msg = "El pago esta pendiente";
						break;
					default:
						# code...
						break;
				}
				$result->dataMp = $dataResponse;

				if($result->ok) { //Si el pago se efectúa se procede a Updatear los datos del inscripto
					$dataUser = $values["user"];
					$dataUser["idPago"] = $dataResponse->id; //el id que asigna MP al pago
					$query = "UPDATE inscripciones SET idItem = :idItem, idPago = :idPago WHERE id = :id";
					parent::update($query, $dataUser);
					$result->userUpdated = clone parent::getResult();
				} else {
					$result->userUpdated = null;
				}
			} catch (MPApiException $e) {
				// Maneja la excepción específica de la API de MercadoPago
				$result->ok = false;
				$result->msg = "Error en la API de MercadoPago: " . $e->getMessage();
				$apiR = $e->getApiResponse()->getContent();
				$result->dataMp = $apiR;
				$result->userUpdated = null;
			} catch (Exception $e) {
				// Maneja cualquier otra excepción general
				$result->ok = false;
				$result->msg = "Error general: " . $e->getMessage();
			}

			//se guarda en el historial de transacciones con MP
			$query = "INSERT INTO historiapagos SET iduser = :iduser, fecha = :fecha, hora = :hora, idpago = :idpago, estado = :estado, detalle = :detalle";
			$fecha = date("Y-m-d");
			$hora = date("H:i:s");

			$v = array(
				"iduser" => 1, //esto se tiene que cambiar por el id que se obtiene de jwt
				"fecha" => $fecha,
				"hora" => $hora,
				"idpago" => $dataResponse->id,
				"estado" => $dataResponse->status,
				"detalle" => $dataResponse->status_detail
			);
			parent::add($query, $v);
			$result->hist = parent::getResult();

			parent::forceResult($result);
			return $this;
		}
	}
?>