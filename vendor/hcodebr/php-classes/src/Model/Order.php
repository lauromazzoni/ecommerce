<?php 

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Model\Cart;


class Order extends Model {

	const SUCCESS = "Order-Success";
	const ERROR = "Order-Error";

	public function save(){

		$sql = new Sql();

		//O a chamada abaixo é a união de várias informações: do carrinho na sessão (:idcart), tem um usuário logado (:iduser), o endereço de entrega (:idaddress), o total da soma dos valores dos produtos e o frete (:vltotal). O :idstatus do pedido é, por exemplo, se está em aberto, se está em andamento, se já foi entregue, se já está pago.
		$results = $sql->select("CALL sp_orders_save(:idorder, :idcart, :iduser, :idstatus, :idaddress, :vltotal)", [
			':idorder'=>$this->getidorder(),
			':idcart'=>$this->getidcart(),
			':iduser'=>$this->getiduser(),
			':idstatus'=>$this->getidstatus(),
			':idaddress'=>$this->getidaddress(),
			':vltotal'=>$this->getvltotal()
		]);

		if (count($results) > 0) {
			$this->setData($results[0]);
		}
	}

	public function get($idorder){

		$sql = new Sql();

		$results = $sql->select("
			SELECT * 
			FROM tb_orders a 
			INNER JOIN tb_ordersstatus b USING(idstatus) 
			INNER JOIN tb_carts c USING(idcart)
			INNER JOIN tb_users d ON d.iduser = a.iduser
			INNER JOIN tb_addresses e USING(idaddress)
			INNER JOIN tb_persons f ON f.idperson = d.idperson
			WHERE a.idorder = :idorder
		", [
			':idorder'=>$idorder
		]);

		if (count($results) > 0) {
			$this->setData($results[0]);
		}


	}

	public static function listAll(){

		$sql = new Sql();

		return $sql->select("
			SELECT * 
			FROM tb_orders a 
			INNER JOIN tb_ordersstatus b USING(idstatus) 
			INNER JOIN tb_carts c USING(idcart)
			INNER JOIN tb_users d ON d.iduser = a.iduser
			INNER JOIN tb_addresses e USING(idaddress)
			INNER JOIN tb_persons f ON f.idperson = d.idperson
			ORDER BY a.dtregister DESC
			");
	}

	public function delete(){

		$sql = new Sql();

		$sql->query("DELETE FROM tb_orders WHERE idorder = :idorder", [
			':idorder'=>$this->getidorder()
		]);
	}

	public function getCart():Cart {

 		$cart = new Cart();

 		$cart->get((int)$this->getidcart());

 		return $cart;

	}

	public static function setError($msg){

		$_SESSION[Order::ERROR] = $msg;

	}

	public static function getError(){

		//ser estiver setada e não estiver vazia, retorna a $_SESSION[Order::ERROR], senão retorna vazio ''.
		$msg = (isset($_SESSION[Order::ERROR]) && $_SESSION[Order::ERROR]) ? $_SESSION[Order::ERROR] : '';

		Order::clearError();

		return $msg;
	}

	public static function clearError(){

		$_SESSION[Order::ERROR] = NULL;

	}


	public static function setSuccess($msg){

		$_SESSION[Order::SUCCESS] = $msg;

	}

	public static function getSuccess(){

		//ser estiver setada e não estiver vazia, retorna a $_SESSION[Order::ERROR], senão retorna vazio ''.
		$msg = (isset($_SESSION[Order::SUCCESS]) && $_SESSION[Order::SUCCESS]) ? $_SESSION[Order::SUCCESS] : '';

		Order::clearSuccess();

		return $msg;
	}

	public static function clearSuccess(){

		$_SESSION[Order::SUCCESS] = NULL;

	}

	//é "static" pois não utiliza o "$this"
	//FUNÇÃO PARA A PAGINAÇÃO
	//$page=>número de páginas
	//$itemsPerPage => número de itens por página. A paginação (o número de páginas que aparece embaixo na tela), só comece a partir de 10 itens. Se tiver 11 usuários, será exibido "2" no botão de páginas embaixo na tela
	public static function getPage($page = 1, $itemsPerPage = 10){
		
		$start = ($page - 1) * $itemsPerPage;

		$sql = new Sql();


		//resultado dos produtos
		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_orders a 
			INNER JOIN tb_ordersstatus b USING(idstatus) 
			INNER JOIN tb_carts c USING(idcart)
			INNER JOIN tb_users d ON d.iduser = a.iduser
			INNER JOIN tb_addresses e USING(idaddress)
			INNER JOIN tb_persons f ON f.idperson = d.idperson
			ORDER BY a.dtregister DESC
			LIMIT $start, $itemsPerPage;
		");

		//resultado total (quantas linhas)
		$resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

		return [
			'data'=>$results, //número de linhas resultantes
			'total'=>(int)$resultTotal[0]["nrtotal"], //numero total de registros
			'pages'=>ceil($resultTotal[0]["nrtotal"] / $itemsPerPage) //número total de páginas
		];
	}

	//é "static" pois não utiliza o "$this"	
	public static function getPageSearch($search,$page = 1, $itemsPerPage = 10){
		
		$start = ($page - 1) * $itemsPerPage;

		$sql = new Sql();


		//resultado dos usuários
		$results = $sql->select("
			SELECT SQL_CALC_FOUND_ROWS *
			FROM tb_orders a 
			INNER JOIN tb_ordersstatus b USING(idstatus) 
			INNER JOIN tb_carts c USING(idcart)
			INNER JOIN tb_users d ON d.iduser = a.iduser
			INNER JOIN tb_addresses e USING(idaddress)
			INNER JOIN tb_persons f ON f.idperson = d.idperson
			INNER JOIN tb_ordersstatus g ON a.idstatus = g.idstatus 
			WHERE a.idorder = :id OR g.desstatus LIKE :search OR f.desperson LIKE :search 
			ORDER BY a.dtregister DESC
			LIMIT $start, $itemsPerPage;
		", [
			':search'=>'%'.$search.'%',
			':id'=>$search
		]);

		//resultado total (quantas linhas)
		$resultTotal = $sql->select("SELECT FOUND_ROWS() AS nrtotal;");

		return [
			'data'=>$results, //número de linhas resultantes
			'total'=>(int)$resultTotal[0]["nrtotal"], //numero total de registros
			'pages'=>ceil($resultTotal[0]["nrtotal"] / $itemsPerPage) //número total de páginas
		];
	}


}

?>