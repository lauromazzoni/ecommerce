<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Product;


$app->get("/admin/products", function(){

	User::verifyLogin();

	//Se ela existir, vem ela mesmo. Se não existir, vem vazio "".
	$search = (isset($_GET['search'])) ? $_GET['search'] : "";

	//pagina atual
	//se for definido na url o page, então vai ser o link desse page, se não for definido qual é a página atual, será 1. Esse parâmetro é utilizado nos métodos "getPageSearch" e "getPage".
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	if ($search != ''){

		//a linha abaixo, resumidamente, carrega as páginas com a lista de usuários pesquisados no $search
		$pagination = Product::getPageSearch($search, $page);

	} else {

		//$pagination = User::getPage($page, 1); //1 --> um usuário por página
		//a linha abaixo, resumidamente, carrega as páginas com a lista de usuários
		$pagination = Product::getPage($page); //1 --> um usuário por página

	}

	//o array $pages irá conter o texto do search e o link da página
	$pages = [];

	//número de páginas ---> $pagination['pages']
	for ($x = 0; $x < $pagination['pages']; $x++){

		array_push($pages, [
			'href'=> '/admin/users?'.http_build_query([  /// a interrogação (.../users?) serve para concatenar
				'page'=>$x + 1, //só para não começar da página zero, senão irá ficar um "0" no botão que mostras as páginas
				'search'=>$search //tem que passar o $search pois, caso esteja sendo realizada alguma pesquisa, esta continue na nova página
		]),
		//só para não começar da página zero, senão irá ficar um "0" no botão que mostras as páginas
		'text'=>$x + 1
	]);

	}

	$page = new PageAdmin();

	$page->setTpl("products", [
		"products"=>$pagination['data'], //o $pagination na chave 'data', contém o resultado dos produtos.
		"search"=>$search,
		"pages"=>$pages
	]);

});

$app->get("/admin/products/create", function(){

	User::verifyLogin();

	$page = new PageAdmin();

	$page->setTpl("products-create");

});

$app->post("/admin/products/create", function(){

	User::verifyLogin();

	$product = new Product();

	$product->setData($_POST);

	var_dump($product);

	$product->save();

	header("Location: /admin/products");
	exit;

});


$app->get("/admin/products/:idproduct", function($idproduct){

	User::verifyLogin();

	$product = new Product();

	$product->get((int)$idproduct);

	$page = new PageAdmin();

	$page->setTpl("products-update", [
		'product'=>$product->getValues()
	]);

});

$app->post("/admin/products/:idproduct", function($idproduct){

	User::verifyLogin();

	$product = new Product();

	$product->get((int)$idproduct);

	$product->setData($_POST);

	$product->save();

	$product->setPhoto($_FILES["file"]);

	header("Location: /admin/products");
	exit;
});

$app->get("/admin/products/:idproduct/delete", function($idproduct){

	User::verifyLogin();

	$product = new Product();

	$product->get((int)$idproduct);

	$product->delete();

	header("Location: /admin/products");
	exit;

});

?>