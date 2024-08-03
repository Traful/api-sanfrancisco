<?php
	//namespace objects;

	//use objects\Base;
	require_once("objects/base.php");

	class Estadisticas extends Base {
		private $table_name = "inscripciones";

		// constructor
		public function __construct($db) {
			parent::__construct($db);
		}
		public function getEstadisticasTotalInscriptos() {
			// Total de inscriptos
			$query = "SELECT COUNT(*) AS cantidad FROM $this->table_name WHERE idItem IS NOT NULL AND idPago IS NOT NULL";
			parent::getOne($query);
			return $this;
		}

		public function getEstadisticasByGeneros() {
			// Cantidades por genero
			$query = "SELECT genero, COUNT(*) AS cantidad FROM $this->table_name WHERE idItem IS NOT NULL AND idPago IS NOT NULL GROUP BY genero";
			parent::getAll($query);
			return $this;
		}

		public function getEstadisticasByItems() {
			// Items (tipo de carrera/producto que se vende)
			$query = <<<EOD
			SELECT
				C1.*,
				items.*
			FROM (
				SELECT
					idItem,
					COUNT(*) AS cantidad
				FROM {$this->table_name}
				WHERE
					idItem IS NOT NULL
					AND idPago IS NOT NULL
				GROUP BY idItem
			) C1
			INNER JOIN items ON
				items.id = c1.idItem
			EOD;
			parent::getAll($query);
			return $this;
		}

		public function getEstadisticasByEdades() {
			// Categorías/Edad
			$query = "SELECT categoria_edad, COUNT(*) AS cantidad FROM $this->table_name WHERE idItem IS NOT NULL AND idPago IS NOT NULL GROUP BY categoria_edad";
			parent::getAll($query);
			return $this;
		}

		public function getEstadisticasByTalles() {
			// Talles de Remera
			$query = "SELECT talle_remera, COUNT(*) AS cantidad FROM $this->table_name WHERE idItem IS NOT NULL AND idPago IS NOT NULL GROUP BY talle_remera";
			parent::getAll($query);
			return $this;
		}
	}
?>