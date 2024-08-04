<?php
//namespace objects;

//use objects\Base;
require_once("objects/base.php");

class Estadisticas extends Base
{
	private $table_name = "inscripciones";

	// constructor
	public function __construct($db)
	{
		parent::__construct($db);
	}
	public function getEstadisticasTotalInscriptos()
	{
		// Total de inscriptos
		$query = "SELECT COUNT(*) AS cantidad, SUM(importe) AS total FROM $this->table_name WHERE pagado = 1";
		parent::getOne($query);
		return $this;
	}

	public function getEstadisticasByGeneros()
	{
		// Cantidades por genero
		$query = "SELECT genero, COUNT(*) AS cantidad FROM $this->table_name WHERE pagado = 1 GROUP BY genero";
		parent::getAll($query);
		return $this;
	}

	public function getEstadisticasByItems()
	{
		// Items (tipo de carrera/producto que se vende)
		$query = <<<EOD
			SELECT
				items.titulo,
				C1.cantidad,
				C1.recaudado
			FROM (
				SELECT
					idItem,
					COUNT(*) AS cantidad,
					SUM(importe) AS recaudado
				FROM $this->table_name
				WHERE
					pagado = 1
				GROUP BY idItem
			) C1
			INNER JOIN items ON
				items.id = C1.idItem
			EOD;
		parent::getAll($query);
		return $this;
	}

	public function getEstadisticasByEdades()
	{
		// Categorías/Edad
		$query = "SELECT categoria_edad, COUNT(*) AS cantidad FROM $this->table_name  WHERE pagado = 1 GROUP BY categoria_edad";
		parent::getAll($query);
		return $this;
	}

	public function getEstadisticasByTalles()
	{
		// Talles de Remera
		$query = "SELECT talle_remera, COUNT(*) AS cantidad FROM $this->table_name  WHERE pagado = 1 GROUP BY talle_remera";
		parent::getAll($query);
		return $this;
	}
}
