<?php
//namespace objects;

//use objects\Base;

require_once("objects/base.php");

class Users extends Base
{
	private $table_name = "users";

	// constructor
	public function __construct($db)
	{
		parent::__construct($db);
	}

	public function getUsers()
	{
		$query = "SELECT id, email, firstname, lastname, google_id FROM $this->table_name ORDER BY id";
		parent::getAll($query);
		return $this;
	}

	public function getUser($id)
	{
		$query = "SELECT id, email, firstname, lastname, tipoUser, google_id 
				 FROM $this->table_name 
				 WHERE id = :id";
		parent::getOne($query, ["id" => $id]);
		return $this;
	}

	public function getUserTemp($token)
	{
		$query = "SELECT * FROM userstemp WHERE token = :token";
		parent::getOne($query, ["token" => $token]);
		return $this;
	}

	public function getUserPasswordTemp($token)
	{
		$query = "SELECT * FROM passrecovery WHERE token = :token";
		parent::getOne($query, ["token" => $token]);
		return $this;
	}

	public function setNewPassword($id, $password)
	{
		$query = "UPDATE $this->table_name SET password = :password WHERE id = :id";
		$newPassword = password_hash($password, PASSWORD_BCRYPT);
		parent::update($query, ["id" => $id, "password" => $newPassword]);
		return $this;
	}

	public function deleteTempPassword($id)
	{
		$query = "DELETE FROM passrecovery WHERE id = :id";
		parent::update($query, ["id" => $id]);
		return $this;
	}

	public function moveTempUser($id)
	{
		$query = <<<EOD
            INSERT INTO users (email, firstname, lastname, password)
            SELECT
                ut.email,
                ut.firstname,
                ut.lastname,
                ut.password
            FROM userstemp ut
            WHERE
                ut.id = :id AND
                NOT EXISTS (
                    SELECT 1 FROM users e WHERE e.email = ut.email
                );
            EOD;
		parent::add($query, ["id" => $id]);
		if (parent::getResult()->ok) {
			$query = "DELETE FROM userstemp WHERE id = :id";
			parent::delete($query, ["id" => $id]);
		}
		return $this;
	}

	public function setUser($values)
	{
		$query = "INSERT INTO $this->table_name SET 
                  email = :email, 
                  firstname = :firstname, 
                  lastname = :lastname, 
                  password = :password,
                  created_at = NOW()";
		$values["password"] = password_hash($values["password"], PASSWORD_BCRYPT);
		parent::add($query, $values);
		return $this;
	}

	public function updateUser($values)
	{
		$query = "UPDATE $this->table_name SET 
                  email = :email, 
                  firstname = :firstname, 
                  lastname = :lastname 
                  WHERE id = :id";
		parent::update($query, $values);
		return $this;
	}

	public function deleteUser($id)
	{
		$query = "DELETE FROM $this->table_name WHERE id = :id";
		parent::delete($query, ["id" => $id]);
		return $this;
	}

	public function userExist($email)
	{
		$query = "SELECT * FROM $this->table_name WHERE email = :email";
		parent::getOne($query, ["email" => $email]);
		$userE = parent::getResult();
		if ($userE->ok) {
			return $userE->data;
		}
		return false;
	}

	public function setRegister($values)
	{
		$query = "INSERT INTO userstemp SET 
                  email = :email, 
                  firstname = :firstname, 
                  lastname = :lastname, 
                  password = :password, 
                  token = :token, 
                  fecha = :fecha";
		$values["password"] = password_hash($values["password"], PASSWORD_BCRYPT);
		$values["fecha"] = date("Y-m-d");
		parent::add($query, $values);
		return $this;
	}

	public function setTempRecovery($values)
	{
		$resp = new \stdClass();
		$existe = $this->userExist($values["email"]);
		if ($existe) {
			$query = "INSERT INTO passrecovery SET 
                      iduser = :iduser, 
                      email = :email, 
                      token = :token, 
                      fecha = :fecha";
			$data = [
				"iduser" => $existe->id,
				"email" => $existe->email,
				"token" => $values["token"],
				"fecha" => date("Y-m-d")
			];
			parent::add($query, $data);
			$dataResp = parent::getResult();
			if ($dataResp->ok) {
				$resp->ok = true;
				$resp->msg = "";
				$resp->data = [
					"newId" => $dataResp->data["newId"],
					"email" => $values["email"],
					"token" => $values["token"]
				];
				$resp->errores = [];
			} else {
				$resp = $dataResp;
			}
		} else {
			$resp->ok = false;
			$resp->msg = "La dirección de correo eletrónico " . $values["email"] . " no esta registrado en el sistema.";
			$resp->data = "";
			$resp->errores = [];
		}
		return $resp;
	}

	public function findOrCreateGoogleUser($values)
	{
		try {
			// Buscar usuario existente por google_id o email
			$query = "SELECT id, email, firstname, lastname, google_id, tipoUser 
                     FROM {$this->table_name} 
                     WHERE google_id = :google_id OR email = :email 
                     LIMIT 1";

			parent::getOne($query, [
				"google_id" => $values["googleId"],
				"email" => $values["email"]
			]);

			$result = parent::getResult();

			if ($result->ok && $result->data) {
				// Usuario existe, actualizar datos
				$query = "UPDATE {$this->table_name} SET 
                         google_id = :google_id,
                         firstname = :firstname,
                         lastname = :lastname,
                         tipoUser = :tipoUser
                         WHERE id = :id";

				parent::update($query, [
					"id" => $result->data->id,
					"google_id" => $values["googleId"],
					"firstname" => $values["firstname"],
					"lastname" => $values["lastname"],
					"tipoUser" => $result->data->tipoUser ?? 0
				]);
			} else {
				// Crear nuevo usuario
				$query = "INSERT INTO {$this->table_name} 
                         (email, firstname, lastname, google_id, tipoUser, password) 
                         VALUES 
                         (:email, :firstname, :lastname, :google_id, :tipoUser, :password)";

				parent::add($query, [
					"email" => $values["email"],
					"firstname" => $values["firstname"],
					"lastname" => $values["lastname"],
					"google_id" => $values["googleId"],
					"tipoUser" => 0,
					"password" => null
				]);

				$insertResult = parent::getResult();
				if (!$insertResult->ok) {
					throw new \Exception("Error al crear nuevo usuario");
				}

				// Obtener el usuario recién creado
				$query = "SELECT id, email, firstname, lastname, google_id, tipoUser 
                         FROM {$this->table_name} 
                         WHERE id = :id";
				parent::getOne($query, ["id" => $insertResult->data["newId"]]);
			}

			return parent::getResult();
		} catch (\Exception $e) {
			error_log("Error en findOrCreateGoogleUser: " . $e->getMessage());
			$resp = new \stdClass();
			$resp->ok = false;
			$resp->msg = "Error procesando usuario de Google: " . $e->getMessage();
			$resp->data = null;
			return $resp;
		}
	}

	// Modificar también el método getResult en la clase Base si es necesario
	public function getResult()
	{
		return $this->result;
	}

	// Método auxiliar para actualizar último login
	private function updateLastLogin($userId)
	{
		$query = "UPDATE $this->table_name SET last_login = NOW() WHERE id = :id";
		return parent::update($query, ["id" => $userId]);
	}
}
