<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;

class User extends Model {


const SESSION = "User";


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

	if (
		//Verifica se a sessão foi definida com a constante SESSION
		!isset($_SESSION[User::SESSION])
		||
		//Verifica se está vazia (ou se perdeu o valor)
		!$_SESSION[User::SESSION]
		||
		//Verifica se dentro da coleção da sessão, foi setado o iduser
		//o (int) abaixo é uma moldage de tipo. Se pegar o iduser e ele estiver vazio, quando for feito o cast para inteiro ele vai vira zero.
		!(int)$_SESSION[User::SESSION]["iduser"] > 0
		||
		//Outra moldagem para booleano
		(booL)$_SESSION[User::SESSION]["inadmin"] !== $inadmin
	){
		header("Location: /admin/login");
		exit;
	}
}

public static function logout(){
	$_SESSION[User::SESSION] = NULL;
}

}






	



?>