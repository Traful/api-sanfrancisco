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

	public function getEstadisticasByTipoInscripcion()
	{
		// Asumiendo que tienes un campo 'tipo_inscripcion' en tu tabla
		$query = "SELECT tipo_inscripcion, COUNT(*) AS cantidad FROM $this->table_name WHERE pagado = 1 GROUP BY tipo_inscripcion";
		parent::getAll($query);
		return $this;
	}
	public function getListadoInscritos()
	{
		// Obtener listado general de inscritos
		$query = "
            SELECT 
                id, nombre, apellido, dni, email, telefono, tipo_inscripcion, categoria_edad 
            FROM 
                $this->table_name
            WHERE 
                pagado = 1
        ";
		parent::getAll($query);
		return $this;
	}

	public function getInscritoPorDni($dni)
	{
		$query = "
		SELECT 
			id, usuario_id, dni, nombre, apellido, fecha_nacimiento, genero, email, telefono, domicilio, ciudad, provincia, pais, codigo_postal, 
			contacto_emergencia_nombre, contacto_emergencia_apellido, contacto_emergencia_telefono, talle_remera, team_agrupacion, categoria_edad, 
			codigo_descuento, tipo_inscripcion, tipo_discapacidad, idItem, importe, pagado, rEntregada, 
			certificado_medico, certificado_discapacidad, tipo_mime, tipo_mime_discapacidad, nombre_archivo, nombre_archivo_discapacidad 
		FROM 
			$this->table_name
		WHERE 
			dni = ?
		";

		$result = parent::getOne($query, [$dni]);

		if ($result) {
			// Codificar los certificados como base64 para enviarlos correctamente
			if ($result['certificado_medico']) {
				$result['certificado_medico'] = base64_encode($result['certificado_medico']);
			}
			if ($result['certificado_discapacidad']) {
				$result['certificado_discapacidad'] = base64_encode($result['certificado_discapacidad']);
			}
		}

		return $result;
	}
}
