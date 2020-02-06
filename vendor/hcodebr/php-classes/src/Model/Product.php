<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;

class Product extends Model {

	public static function listAll(){
		$sql = new Sql();
		return $sql->select("SELECT * FROM tb_products ORDER BY desproduct");
	}

	public static function checkList($list){
		foreach ($list as &$row) {
			$p = new Product();
			$p->setData($row);
			$row = $p->getValues();
		}

		//ELE ESTÁ RETORNANDO O $list POIS ELE FOI ATUALIZADO NO FOREACH POR CAUSA DO '&' ANTES DA VARIÁVEL '$row'
		return $list;
	}

	public function save(){
		$sql = new Sql();
		$results = $sql->select("CALL sp_products_save(:idproduct, :desproduct, :vlprice, :vlwidth, :vlheight, :vllength, :vlweight, :desurl)", array(
			":idproduct"=>$this->getidproduct(),
			":desproduct"=>$this->getdesproduct(),
			":vlprice"=>$this->getvlprice(),
			":vlwidth"=>$this->getvlwidth(),
			":vlheight"=>$this->getvlheight(),
			":vllength"=>$this->getvllength(),
			":vlweight"=>$this->getvlweight(),
			":desurl"=>$this->getdesurl()
		));

		$this->setData($results[0]);

	}

	public function get($idproduct){

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_products WHERE idproduct = :idproduct", [
			":idproduct"=>$idproduct
		]);

		$this->setData($results[0]);
	}

	public function delete(){

		$sql = new Sql();

		$sql->select("DELETE FROM tb_products WHERE idproduct = :idproduct", [
			":idproduct"=>$this->getidproduct()
		]);

		$fileDir = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR .
			"res" . DIRECTORY_SEPARATOR .
			"site" . DIRECTORY_SEPARATOR .
			"img" . DIRECTORY_SEPARATOR . 
			"products" . DIRECTORY_SEPARATOR .
			$this->getidproduct() . ".jpg";

		if (file_exists($fileDir)){
			unlink($fileDir);
		}
	
	}
	
	public function checkPhoto(){

		if (file_exists(
			$_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR .
			"res" . DIRECTORY_SEPARATOR .
			"site" . DIRECTORY_SEPARATOR .
			"img" . DIRECTORY_SEPARATOR . 
			"products" . DIRECTORY_SEPARATOR .
			$this->getidproduct() . ".jpg"
		)){
			$url = "/res/site/img/products/" . $this->getidproduct() . ".jpg";
		} else {
			$url = "/res/site/img/product.jpg";
		}

		return $this->setdesphoto($url);

	}

	public function getValues(){
		$this->checkPhoto();
		$values = parent::getValues();
		return $values;
	}

	public function setPhoto($file){
		$extension = explode('.', $file['name']);
		$extension = end($extension);

		switch ($extension) {
			case "jpg":
			case "jpeg":
			$image = imagecreatefromjpeg($file["tmp_name"]);
			break;

			case "gif":
			$image = imagecreatefromgif($file["tmp_name"]);
			break;

			case "png":
			$image = imagecreatefrompng($file["tmp_name"]);
			break;
		}

		$dist = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR .
			"res" . DIRECTORY_SEPARATOR .
			"site" . DIRECTORY_SEPARATOR .
			"img" . DIRECTORY_SEPARATOR . 
			"products" . DIRECTORY_SEPARATOR .
			$this->getidproduct() . ".jpg";

		imagejpeg($image, $dist);

		imagedestroy($image);

		$this->checkPhoto();	
	}

	public function getFromUrl($desurl){

		$sql = new Sql();

		$rows = $sql->select("SELECT * FROM tb_products WHERE desurl = :desurl LIMIT 1", [
			":desurl"=>$desurl
		]);

		//AO INVÉS DE USAR O "return", PEGOU-SE A LINHA RETORNADA PELA QUERY E A INSERIU NO OBJETO COM O "setData()", ASSIM, NO ARQUIVO site.php, DENTRO DE "$app->get("/products/:desurl", function($desurl)){", O OBJETO "$product" JÁ ESTÁ COM OS DADOS DA QUERY. DAÍ, O PRÓPRIO OBJETO "$product" PEGA OS VALORES ("getValues()") E PASSA PARA O TEMPLATE
		$this->setData($rows[0]);
	}

	public function getCategories(){

		$sql = new Sql();

		return $sql->select("
				SELECT * FROM tb_categories a INNER JOIN tb_productscategories b ON a.idcategory = b.idcategory WHERE b.idproduct = :idproduct
			", [
				':idproduct'=>$this->getidproduct()
			]);
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
			FROM tb_products 
			ORDER BY desproduct
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
			FROM tb_products
			WHERE desproduct LIKE :search
			ORDER BY desproduct
			LIMIT $start, $itemsPerPage;
		", [
			':search'=>'%'.$search.'%'
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