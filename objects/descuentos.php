<?php
	//namespace objects;

	//use objects\Base;
	require_once("objects/base.php");

	class Descuentos extends Base {
		private $table_name = "descuentos";

		// constructor
		public function __construct($db) {
			parent::__construct($db);
		}

		public function getDescuentos($limit =  10) {
			$query = "SELECT * FROM $this->table_name ORDER BY id DESC LIMIT 0, $limit";
			parent::getAll($query);
			return $this;
		}

		public function getDescuentoByCodigo($codigo, $onlyUnUsed = false) {
			$query = "SELECT * FROM $this->table_name WHERE codigo = :codigo";
			if($onlyUnUsed) {
				$query .= " AND disponibilidad > 0";
			}
			parent::getOne($query, ["codigo" => $codigo]);
			return $this;
		}

		public function descontarDisponibilidad($codigo, $cantidad =  1) {
			$query = "UPDATE $this->table_name SET disponibilidad = (disponibilidad - $cantidad ) WHERE codigo = :codigo";
			parent::update($query, ["codigo" => $codigo]);
			return $this;
		}

		public function setDescuento($values) {
			$query = "INSERT INTO $this->table_name SET codigo = :codigo, disponibilidad = :disponibilidad, importe = :importe";
			parent::add($query, $values);
			return $this;
		}
	}
?>