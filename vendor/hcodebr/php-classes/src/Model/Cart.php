<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;
use \Hcode\Model\User;

class Cart extends Model {

	//O ID DO CARRINHO SERÁ GUARDADA JUNTO COM OS DADOS DA SESSÃO.
	const SESSION = "Cart";

	//Método para saber se precisa de criar um carrinho novo, ou se o carrinho já existe e irá pegar o id na sessão, se a sessão foi finalizada mas se ainda tem o id da sessão.
	public static function getFromSession(){

		$cart = new Cart();

		//como pegar o nome da constante do carrinho da sessão?? -----> $_SESSION[Cart::SESSION]
		//se a sessão foi definida, verificar se detro dela existe o id do carrinho e se este id é maior que zero -->$_SESSION[Cart::SESSION]['idcart'] > 0
		//o cast para inteiro (int)----> se for vazio, na hora de fazer o cast irá retornar zero ('0').
		if (isset($_SESSION[Cart::SESSION]) && (int)$_SESSION[Cart::SESSION]['idcart'] > 0){

			$cart->get((int)$_SESSION[Cart::SESSION]['idcart']);
		} else {

			$cart->getFromSessionID();

			//verificando se o getFromSessionID() conseguiu carregar o carrinho
			if(!(int)$cart->getidcart() > 0 ){
				$data = [
					'dessessionid'=>session_id(),
				];

				if (User::checkLogin(false)){
					//verificadno se há um usuário logado
					$user = User::getFromSession();
					$data['iduser'] = $user->getiduser();
				}

				$cart->setData($data);

				$cart->save();

				//COMO É UM CARRINHO NOVO E NÃO ESTAVA NA SESSÃO, ELE AGORA PRECISA SE INSERIDO NA SESSÃO
				$cart->setToSession();
			}
		}

		return $cart;
	}

	public function setToSession(){

		//colocou o carrinho na sessão
		$_SESSION[Cart::SESSION] = $this->getVAlues();
	
	}

	public function getFromSessionID(){

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_carts WHERE dessessionid = :dessessionid", [
			':dessessionid'=>$this->session_id()
		]);

		if (count($results) > 0){
			//Inserindo os dados no objeto. Desta maneira, nao precisa usar o return
			$this->setData($results[0]);
		}
	}





	public function get(int $idcart){

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_carts WHERE idcart = :idcart", [
			':idcart'=>$idcart
		]);

		if (count($results) > 0){
			//Inserindo os dados no objeto. Desta maneira, nao precisa usar o return
			$this->setData($results[0]);
		}
	}

	public function save(){

		$sql = new Sql();

		$results = $sql->select("CALL sp_carts_save(:idcart, :dessessionid, :iduser, :deszipcode, :vlfreight, :nrdays)", [
			':idcart'=>$this->getidcart(),
			':dessessionid'=>$this->getdessessionid(),
			':iduser'=>$this->getiduser(),
			':deszipcode'=>$this->getdeszipcode(),
			':vlfreight'=>$this->getvlfreight(),
			':nrdays'=>$this->getnrdays(),
		]);

		$this->setData($results[0]);


	}
}
?>