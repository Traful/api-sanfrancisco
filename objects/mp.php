<?php

require_once("objects/base.php");
require_once './vendor/autoload.php'; // SDK de Mercado Pago

use MercadoPago\SDK;
use MercadoPago\Preference;
use MercadoPago\Item;

class Mp extends Base
{
	public function __construct($db)
	{
		SDK::setAccessToken($_ENV["MP_ACCESS_TOKEN"]);
		parent::__construct($db);
	}

	public function createPreference($name, $surname, $email, $tickets)
	{
		$PRICES = [
			'day1' => 6000,
			'day2' => 10000
		];
		$items = [];
		$totalAmount = 0;

		if ($tickets['day1'] > 0) {
			$item = new Item();
			$item->title = 'Entrada 11 de Enero';
			$item->quantity = $tickets['day1'];
			$item->unit_price = $PRICES['day1'];
			$item->currency_id = 'ARS';
			$items[] = $item;
			$totalAmount += $tickets['day1'] * $PRICES['day1'];
		}

		if ($tickets['day2'] > 0) {
			$item = new Item();
			$item->title = 'Entrada 12 de Enero';
			$item->quantity = $tickets['day2'];
			$item->unit_price = $PRICES['day2'];
			$item->currency_id = 'ARS';
			$items[] = $item;
			$totalAmount += $tickets['day2'] * $PRICES['day2'];
		}

		if (empty($items)) {
			throw new \Exception("No se seleccionaron entradas.");
		}

		$preference = new Preference();
		$preference->items = $items;

		$preference->payer = array(
			"name" => $name,
			"surname" => $surname,
			"email" => $email
		);

		$preference->back_urls = array(
			"success" => $_ENV['APP_URL'] . "/evento/success",
			"failure" => $_ENV['APP_URL'] . "/evento/failure",
			"pending" => $_ENV['APP_URL'] . "/evento/pending"
		);
		$preference->auto_return = "approved";

		$preference->save();

		return $preference->id;
	}
}

function saveOrderToDatabase($db, $user_id, $name, $surname, $email, $tickets, $preferenceId)
{
	$query = "INSERT INTO orders (user_id, name, surname, email, tickets, preference_id, status, created_at)
              VALUES (:user_id, :name, :surname, :email, :tickets, :preference_id, :status, NOW())";
	$stmt = $db->prepare($query);
	$ticketsJson = json_encode($tickets);
	$status = 'pending';

	$stmt->bindParam(':user_id', $user_id);
	$stmt->bindParam(':name', $name);
	$stmt->bindParam(':surname', $surname);
	$stmt->bindParam(':email', $email);
	$stmt->bindParam(':tickets', $ticketsJson);
	$stmt->bindParam(':preference_id', $preferenceId);
	$stmt->bindParam(':status', $status);

	if ($stmt->execute()) {
		return $db->lastInsertId();
	} else {
		throw new Exception("Error al guardar el pedido en la base de datos.");
	}
}
