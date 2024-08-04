<?php
	//namespace objects;

	//use objects\Base;
	require_once("objects/base.php");

	class Inscripciones extends Base {
		private $table_name = "inscripciones";

		// constructor
		public function __construct($db) {
			parent::__construct($db);
		}

		public function updatePaymentState($idPreferencia) {
			$query = "UPDATE $this->table_name SET pagado = 1 WHERE idPago = :idPago";
			parent::update($query, ["idPago" => $idPreferencia]);
			return $this;
		}

		public function setInscripcion($values) {
			$query = "INSERT INTO $this->table_name (
                usuario_id, dni, nombre, apellido, fecha_nacimiento, genero, 
                email, telefono, domicilio, ciudad, provincia, pais, 
                codigo_postal, contacto_emergencia_nombre, contacto_emergencia_apellido, 
                contacto_emergencia_telefono, talle_remera, team_agrupacion, 
                categoria_edad, codigo_descuento, certificado_medico, tipo_mime, nombre_archivo, acepta_promocion,
				idItem, idPago, importe, pagado, rEntregada
            ) VALUES (
                :usuario_id, :dni, :nombre, :apellido, :fecha_nacimiento, :genero,
                :email, :telefono, :domicilio, :ciudad, :provincia, :pais,
                :codigo_postal, :contacto_emergencia_nombre, :contacto_emergencia_apellido,
                :contacto_emergencia_telefono, :talle_remera, :team_agrupacion,
                :categoria_edad, :codigo_descuento, :certificado_medico, :tipo_mime, :nombre_archivo, :acepta_promocion,
				:idItem, :idPago, :importe, :pagado, 0
            )";
			parent::add($query, $values);
			return $this;
		}

		
		public function getInscripciones() {
			$query = "SELECT id, usuario_id, dni, nombre, apellido, fecha_nacimiento, genero, email, telefono, domicilio, ciudad, provincia, pais, codigo_postal, contacto_emergencia_nombre, contacto_emergencia_apellido, contacto_emergencia_telefono, talle_remera, team_agrupacion, categoria_edad, codigo_descuento, tipo_mime, nombre_archivo, acepta_promocion, idItem, idPago FROM $this->table_name WHERE pagado = 1 ORDER BY apellido, nombre";
 			parent::getAll($query);
			return $this;
		}

		public function getInscripcionesByUser($user) {
			$query = "SELECT id, usuario_id, dni, nombre, apellido, fecha_nacimiento, genero, email, telefono, domicilio, ciudad, provincia, pais, codigo_postal, contacto_emergencia_nombre, contacto_emergencia_apellido, contacto_emergencia_telefono, talle_remera, team_agrupacion, categoria_edad, codigo_descuento, tipo_mime, nombre_archivo, acepta_promocion, idItem, idPago FROM $this->table_name WHERE pagado = 1 AND usuario_id = :idx";
 			parent::getAll($query, ["idx" => $user]);
			return $this;
		}

		public function setEntregaRemera($id) {
			$query = "UPDATE $this->table_name SET rEntregada = !rEntregada WHERE id = :id";
 			parent::update($query, ["id" => $id]);
			return $this;
		}
	}
?>