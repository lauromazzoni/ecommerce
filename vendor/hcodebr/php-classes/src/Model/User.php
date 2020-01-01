<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;

class User extends Model {


const SESSION = "User";
const SECRET = "HcodePhp7_Secret";
const SECRET_IV = "HcodePhp7_Secret_IV";

	public static function getFromSession(){

		$user = new User();

		if (isset($_SESSION[User::SESSION]) && (int)$_SESSION[User::SESSION]['iduser'] > 0){

			$user->setData($_SESSION[User::SESSION]);

		}

		return $user;

	}

	public static function checkLogin($inadmin = true){

		if (
			//Verifica se a sessão do usuáro está definida. Se não está definida, estão não está logado
			!isset($_SESSION[User::SESSION])
			||
			//Verifica se está vazia (ou se perdeu o valor)
			!$_SESSION[User::SESSION]
			||
			//Verifica se dentro da coleção da sessão, foi setado o iduser
			//o (int) abaixo é uma moldage de tipo. Se pegar o iduser e ele estiver vazio, quando for feito o cast para inteiro ele vai vira zero.
			!(int)$_SESSION[User::SESSION]["iduser"] > 0
		) {
			//Não está logado
			return false;

		} else {
			//$inadmin === true ---> é uma rota de administração?
			//$_SESSION[User::SESSION]['inadmin'] ----> verifica se o usuário faz parte da administração
			if ($inadmin === true && (booL)$_SESSION[User::SESSION]['inadmin'] === true){

				return true; //é um administrador

			} else if ($inadmin === false){

				return true;
			} else {
				return false;
			}

		}

	}


	public static function login ($login, $password){

		$sql = new Sql();

		$results = $sql -> select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", array(
			":LOGIN" => $login
		));

		if (count($results) === 0){

			//A "\Exception" está no escopo principal (namespace principal) do PHP e não dentro do namespace Hcode\Model (não foi criada a Exception deste sitema) por isto, é utilizada a contra barra (\Exception) para achar a Exception principal
			throw new \Exception("Usuário inexistente ou senha inválida.", 1);
		}

		$data = $results[0]; //primeiro registro encontrado

		//A funçao password_verify é nativa
		if (password_verify($password, $data["despassword"]) === true){
			$user = new User();

			$user -> setData($data);

			$_SESSION[User::SESSION] = $user->getValues();

			return $user;

		} else {
			throw new \Exception("Usuário inexistente ou senha inválida.", 1);
		}
	}

	public static function verifyLogin($inadmin = true){

		if (User::checkLogin($inadmin)){
			header("Location: /admin/login");
			exit;
		}
	}

	public static function logout(){
		$_SESSION[User::SESSION] = NULL;
	}

	public static function listAll(){
		$sql = new Sql();
		return $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) ORDER BY b.desperson");
	}

	public function save(){
		$sql = new Sql();
		$results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
			":desperson"=>$this->getdesperson(),
			":deslogin"=>$this->getdeslogin(),
			":despassword"=>$this->getdespassword(),
			":desemail"=>$this->getdesemail(),
			":nrphone"=>$this->getnrphone(),
			":inadmin"=>$this->getinadmin()
		));

		$this->setData($results[0]);
	}

	public function get($iduser){

		$sql = new Sql();
		$results = $sql->select("SELECT * FROM tb_users a INNER JOIN tb_persons b USING(idperson) WHERE a.iduser = :iduser ", array(
				":iduser"=>$iduser
				));

		$this->setData($results[0]);

	}

	public function update(){
		$sql = new Sql();
		$results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", array(
			":iduser"=>$this->getiduser(),
			":desperson"=>$this->getdesperson(),
			":deslogin"=>$this->getdeslogin(),
			":despassword"=>$this->getdespassword(),
			":desemail"=>$this->getdesemail(),
			":nrphone"=>$this->getnrphone(),
			":inadmin"=>$this->getinadmin()
		));

		$this->setData($results[0]);

	}

	public function delete(){

		$sql = new Sql();
		$sql->query("CALL sp_users_delete(:iduser)", array(
			":iduser"=>$this->getiduser()
		));
	}

	public static function getForgot($email, $inadmin = true)
	{
		$sql = new Sql();
		$results = $sql->select("
			SELECT *
			FROM tb_persons a
			INNER JOIN tb_users b USING(idperson)
			WHERE a.desemail = :email;
		", array(
			":email"=>$email
		));
		if (count($results) === 0)
		{
			throw new \Exception("Não foi possível recuperar a senha.");
		}
		else
		{
			$data = $results[0];
			$results2 = $sql->select("CALL sp_userspasswordsrecoveries_create(:iduser, :desip)", array(
				":iduser"=>$data['iduser'],
				":desip"=>$_SERVER['REMOTE_ADDR']
			));
			if (count($results2) === 0)
			{
				throw new \Exception("Não foi possível recuperar a senha.");
			}
			else
			{
				$dataRecovery = $results2[0];
				$code = openssl_encrypt($dataRecovery['idrecovery'], 'AES-128-CBC', pack("a16", User::SECRET), 0, pack("a16", User::SECRET_IV));
				$code = base64_encode($code);
/*				if ($inadmin === true) {
					$link = "http://ecommerce.com.br/admin/forgot/reset?code=$code";
				} else {
					$link = "http://ecommerce.com.br/forgot/reset?code=$code";
					
				}*/		
				$link = "http://ecommerce.com.br/admin/forgot/reset?code=$code";
		
				$mailer = new Mailer($data['desemail'], $data['desperson'], "Redefinir senha da Hcode Store", "forgot", array(
					"name"=>$data['desperson'],
					"link"=>$link
				));				
				$mailer->send();
				return $link;
			}
		}
	}

	public static function validForgotDecrypt($code){

		$code = base64_decode($code);
		$idrecovery = openssl_decrypt($code, 'AES-128-CBC', pack("a16", User::SECRET), 0, pack("a16", User::SECRET_IV));
		$sql = new Sql();
		$results = $sql->select("
			SELECT *
			FROM tb_userspasswordsrecoveries a
			INNER JOIN tb_users b USING(iduser)
			INNER JOIN tb_persons c USING(idperson)
			WHERE
				a.idrecovery = :idrecovery
				AND
				a.dtrecovery IS NULL
				AND
				DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW();
		", array(
			":idrecovery"=>$idrecovery
		));
		if (count($results) === 0)
		{
			throw new \Exception("Não foi possível recuperar a senha.");
		}
		else
		{
			return $results[0];
		}
	}


	public static function setForgotUsed($idrecovery){

		$sql = new Sql();

		$sql->query("UPDATE tb_userspasswordsrecoveries SET dtrecovery = NOW() WHERE idrecovery = :idrecovery", array(
			":idrecovery"=>$idrecovery
		));


	}

	public function setPassword($password){
		$sql = new Sql();
		$sql->query("UPDATE tb_users SET despassword = :password WHERE iduser = :iduser", array(
			":password"=>$password,
			":iduser"=>$this->getiduser()
		));

	}

}
?>