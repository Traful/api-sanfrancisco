<?php
//namespace objects;

//use objects\Base;
require_once("objects/base.php");

class Inscripciones extends Base
{
	private $table_name = "inscripciones";
	protected $conn;  // Cambiamos a protected para que las clases hijas puedan acceder

	public function __construct($db)
	{
		parent::__construct($db);
		$this->conn = $db;  // Aseguramos que $conn se establezca correctamente
	}

	public function setInscripcion($values)
	{
		$fields = [
			'usuario_id',
			'dni',
			'nombre',
			'apellido',
			'fecha_nacimiento',
			'genero',
			'email',
			'telefono',
			'domicilio',
			'ciudad',
			'provincia',
			'pais',
			'codigo_postal',
			'contacto_emergencia_nombre',
			'contacto_emergencia_apellido',
			'contacto_emergencia_telefono',
			'talle_remera',
			'team_agrupacion',
			'categoria_edad',
			'codigo_descuento',
			'certificado_medico',
			'certificado_discapacidad',
			'tipo_mime',
			'nombre_archivo',
			'tipo_mime_discapacidad',
			'nombre_archivo_discapacidad',
			'idItem',
			'idPago',
			'importe',
			'pagado',
			'rEntregada',
			'tipo_inscripcion',
			'tipo_discapacidad'
		];

		$placeholders = array_map(function ($field) {
			return ":$field";
		}, $fields);

		$query = "INSERT INTO $this->table_name (" . implode(", ", $fields) . ") 
                  VALUES (" . implode(", ", $placeholders) . ")";

		$params = [];
		foreach ($fields as $field) {
			$params[":$field"] = isset($values[$field]) ? $values[$field] : null;
		}

		// Asegurar valores por defecto para campos que no permiten NULL
		$params[':idItem'] = $params[':idItem'] ?? 0;
		$params[':importe'] = $params[':importe'] ?? 0;
		$params[':pagado'] = isset($values['pagado']) ? ($values['pagado'] ? 1 : 0) : 0;
		$params[':rEntregada'] = isset($values['rEntregada']) ? ($values['rEntregada'] ? 1 : 0) : 0;
		$params[':tipo_inscripcion'] = $params[':tipo_inscripcion'] ?? 'regular';

		try {
			$stmt = $this->conn->prepare($query);
			$result = $stmt->execute($params);

			if ($result) {
				$resp = new \stdClass();
				$resp->ok = true;
				$resp->msg = "Inscripción creada con éxito.";
				$resp->data = ["id" => $this->conn->lastInsertId()];
				return $resp;
			} else {
				$errorInfo = $stmt->errorInfo();
				throw new \Exception("Error en la inserción: " . $errorInfo[2]);
			}
		} catch (\PDOException $e) {
			$resp = new \stdClass();
			$resp->ok = false;
			$resp->msg = "Error al insertar la inscripción: " . $e->getMessage();
			$resp->error_details = [
				'error_code' => $e->getCode(),
				'error_info' => $e->errorInfo ?? null,
			];
			return $resp;
		}
	}


	public function getInscripciones()
	{
		$query = "SELECT id, usuario_id, dni, nombre, apellido, fecha_nacimiento, genero, email, telefono, domicilio, ciudad, provincia, pais, codigo_postal, contacto_emergencia_nombre, contacto_emergencia_apellido, contacto_emergencia_telefono, talle_remera, team_agrupacion, categoria_edad, codigo_descuento, tipo_mime, nombre_archivo, idItem, idPago, importe, pagado, rEntregada, tipo_inscripcion, tipo_discapacidad FROM $this->table_name WHERE pagado = 1 ORDER BY apellido, nombre";
		parent::getAll($query);
		return $this;
	}

	public function getInscripcionesByUser($user)
	{
		$query = "SELECT id, usuario_id, dni, nombre, apellido, fecha_nacimiento, genero, email, telefono, domicilio, ciudad, provincia, pais, codigo_postal, contacto_emergencia_nombre, contacto_emergencia_apellido, contacto_emergencia_telefono, talle_remera, team_agrupacion, categoria_edad, codigo_descuento, tipo_mime, nombre_archivo, idItem, idPago, importe, pagado, rEntregada, tipo_inscripcion, tipo_discapacidad FROM $this->table_name WHERE pagado = 1 AND usuario_id = :idx";
		parent::getAll($query, ["idx" => $user]);
		return $this;
	}

	public function getInscripcionesByIdPago($idPago)
	{
		$query = "SELECT id, usuario_id, dni, nombre, apellido, fecha_nacimiento, genero, email, telefono, domicilio, ciudad, provincia, pais, codigo_postal, contacto_emergencia_nombre, contacto_emergencia_apellido, contacto_emergencia_telefono, talle_remera, team_agrupacion, categoria_edad, codigo_descuento, tipo_mime, nombre_archivo, idItem, idPago, importe, pagado, rEntregada, tipo_inscripcion, tipo_discapacidad FROM $this->table_name WHERE idPago = :idPago";
		parent::getOne($query, ["idPago" => $idPago]);
		return $this;
	}

	public function setEntregaRemera($id)
	{
		$query = "UPDATE $this->table_name SET rEntregada = !rEntregada WHERE id = :id";
		parent::update($query, ["id" => $id]);
		return $this;
	}
}
