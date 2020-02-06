<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;
use \Hcode\Model\User;

class Cart extends Model {

	//O ID DO CARRINHO SERÁ GUARDADA JUNTO COM OS DADOS DA SESSÃO.
	const SESSION = "Cart";
	const SESSION_ERROR = "CartError";

	//Método para saber se precisa de criar um carrinho novo, ou se o carrinho já existe e irá pegar o id na sessão, se a sessão foi finalizada mas se ainda tem o id da sessão.
	public static function getFromSession(){

		$cart = new Cart();

		//Verifica se o nome do carrinho está na sessão ---> isset($_SESSION[Cart::SESSION]
		//como pegar o nome da constante do carrinho da sessão?? -----> $_SESSION[Cart::SESSION]
		//se a sessão foi definida, verificar se detro dela existe o id do carrinho e se este id é maior que zero -->$_SESSION[Cart::SESSION]['idcart'] > 0
		//o cast para inteiro (int)----> se for vazio, na hora de fazer o cast irá retornar zero ('0').
		if (isset($_SESSION[Cart::SESSION]) && (int)$_SESSION[Cart::SESSION]['idcart'] > 0) {
		
			//se a sessão existir e o idcart for maior que zero, significa que ele já está na sessão, então, o carrinho é carregado na linha abaixo
			$cart->get((int)$_SESSION[Cart::SESSION]['idcart']);
		
		} else {
			$cart->getFromSessionID();

			//verificando se o getFromSessionID() conseguiu carregar o carrinho
			if(!(int)$cart->getidcart() > 0 ){
				$data = [
					'dessessionid'=>session_id(), //session_id() é uma função do PHP que retorno o id da sessão.
				];

				if (User::checkLogin(false)){
					//verificadno se há um usuário logado para associa-lo à sessão
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

		//se retornar algum registro, a data será setada, ser for vazio, é necessário o if da sequência para evitar um erro
		$results = $sql->select("SELECT * FROM tb_carts WHERE dessessionid = :dessessionid", [
			':dessessionid'=>$this->session_id()
		]);

		if (count($results) > 0){

			$this->setData($results[0]);
		}
	}





	public function get(int $idcart){

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_carts WHERE idcart = :idcart", [':idcart'=>$idcart]);

		if (count($results) > 0){
			//Inserindo os dados no objeto. Desta maneira, nao precisa usar o return
			$this->setData($results[0]);
		}
	}


	public function save()	{

		$sql = new Sql();

		$results = $sql->select("CALL sp_carts_save(:idcart, :dessessionid, :iduser, :deszipcode, :vlfreight, :nrdays)", [
			':idcart'=>$this->getidcart(),
			':dessessionid'=>$this->getdessessionid(),
			':iduser'=>$this->getiduser(),
			':deszipcode'=>$this->getdeszipcode(),
			':vlfreight'=>$this->getvlfreight(),
			':nrdays'=>$this->getnrdays()
		]);

		$this->setData($results[0]);

	}

	//Recebe uma instância ($product) da classe Product
	public function addProduct(Product $product){

		$sql = new Sql();

		$sql->query("INSERT INTO tb_cartsproducts (idcart, idproduct) VALUES (:idcart, :idproduct)", [
			":idcart"=>$this->getidcart(),
			":idproduct"=>$product->getidproduct()
		]);
	
		$this->getCalculateTotal();
	}

	//$all ==> está removendo todos ou somente um item do carrinho
	public function removeProduct(Product $product, $all = false){

		$sql = new Sql();

		//se ele está removendo tudo
		if ($all){

			//Remove tudo
			$sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW() WHERE idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL", [
				':idcart'=>$this->getidcart(),
				':idproduct'=>$product->getidproduct()
			]);
		} else {

			//Remove somente um
			$sql->query("UPDATE tb_cartsproducts SET dtremoved = NOW() WHERE idcart = :idcart AND idproduct = :idproduct AND dtremoved IS NULL LIMIT 1", [
				':idcart'=>$this->getidcart(),
				':idproduct'=>$product->getidproduct()
			]);
		}

		$this->getCalculateTotal();
	}


	public function getProducts(){

		$sql = new Sql();


		$rows = $sql->select("
			SELECT b.idproduct, b.desproduct, b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl, COUNT(*) AS nrqtd, SUM(b.vlprice) AS vltotal 
			FROM tb_cartsproducts a 
			INNER JOIN tb_products b ON a.idproduct = b.idproduct 
			WHERE a.idcart = :idcart AND a.dtremoved IS NULL 
			GROUP BY b.idproduct, b.desproduct, b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl
			ORDER BY b.desproduct
			", [
				':idcart'=>$this->getidcart(),

			]);

		return Product::checkList($rows);
	}

	public function getProductsTotals(){

		$sql = new Sql();

		$results = $sql->select("
			SELECT SUM(vlprice) AS vlprice, SUM(vlwidth) AS vlwidth, SUM(vlheight) AS vlheight, SUM(vllength) AS vllength, SUM(vlweight) AS vlweight, COUNT(*) AS nrqtd
			FROM tb_products a
			INNER JOIN tb_cartsproducts b ON a.idproduct = b.idproduct
			WHERE b.idcart = :idcart AND dtremoved IS NULL;
			", [
				":idcart"=>$this->getidcart()
			]);

		if (count($results) > 0){
			return $results[0];
		} else {
			return [];
		}
	}


	public function setFreight($nrzipcode){

		$nrzipcode = str_replace('-', '', $nrzipcode);

		$totals = $this->getProductsTotals();

		if ($totals['nrqtd'] > 0) {

			if ($totals['vlheight'] < 2) $totals['vlheight'] = 2;
			if ($totals['vllength'] < 16) $totals['vllength'] = 16;

			$qs = http_build_query([
				'nCdEmpresa'=>'',
				'sDsSenha'=>'',
				'nCdServico'=>'40010',
				'sCepOrigem'=>'09853120',
				'sCepDestino'=>$nrzipcode,
				'nVlPeso'=>$totals['vlweight'],
				'nCdFormato'=>'1',
				'nVlComprimento'=>$totals['vllength'],
				'nVlAltura'=>$totals['vlheight'],
				'nVlLargura'=>$totals['vlwidth'],
				'nVlDiametro'=>'0',
				'sCdMaoPropria'=>'S',
				'nVlValorDeclarado'=>$totals['vlprice'],
				'sCdAvisoRecebimento'=>'S'
			]);
/*			//URL ABAIXO É FORNECIDA PELOS CORREIOS. O MÉTODO UTILIZADO É O CalcPrecoPrazo (no final da url)
			$xml = (array)simplexml_load_file("http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx/CalcPrecoPrazo?".$qs);

			echo json_encode($xml);
			exit;*/

			//URL ABAIXO É FORNECIDA PELOS CORREIOS. O MÉTODO UTILIZADO É O CalcPrecoPrazo (no final da url)
			$xml = simplexml_load_file("http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx/CalcPrecoPrazo?".$qs);

			$result = $xml->Servicos->cServico;

			if ($result->MsgErro != '') {

				Cart::setMsgError($result->MsgErro);

			} else {

				Cart::clearMsgError();

			}

			$this->setnrdays($result->PrazoEntrega);
			$this->setvlfreight(Cart::formatValueToDecimal($result->Valor));
			$this->setdeszipcode($nrzipcode);

			$this->save();

			return $result;

		} else {



		}

	}

	public static function formatValueToDecimal($value):float{

			$value = str_replace('.', '', $value);
			return str_replace(',', '.', $value);

		}

		public static function setMsgError($msg){

			$_SESSION[Cart::SESSION_ERROR] = $msg;

		}

		public static function getMsgError(){

			//$msg pega a mensagem que está na sessão
			//Se a constante foi definida retorna ela mesma, caso contrário, retorna vazio ("")
			$msg = (isset($_SESSION[Cart::SESSION_ERROR])) ? $_SESSION[Cart::SESSION_ERROR] : "";

			//Limpando a mensagem da sessão. Assim um erro não fica sendo exibido sempre no carrinho
			Cart::clearMsgError();

			return $msg;

		}

		public static function clearMsgError(){

			$_SESSION[Cart::SESSION_ERROR] = NULL;
		}

		public function updateFreight(){

			//verifica se existe um cep dentro do carrinho
			if ($this->getdeszipcode() != ''){

				$this->setFreight($this->getdeszipcode());
			}
		}

		public function getValues(){

			$this->getCalculateTotal();

			return parent::getValues();

		}

		public function getCalculateTotal(){

			$this->updateFreight();

			$totals = $this->getProductsTotals();

			$this->setvlsubtotal($totals['vlprice']);
			$this->setvltotal($totals['vlprice'] + $this->getvlfreight());
		}

	}
?>