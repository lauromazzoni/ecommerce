<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Category;
use \Hcode\Model\Product;


$app->get("/admin/categories", function(){

	//verificar se o usuário está logado para não acessar diretamente sem passar pela tela de autenticação
	User::verifyLogin();

	//Se ela existir, vem ela mesmo. Se não existir, vem vazio "".
	$search = (isset($_GET['search'])) ? $_GET['search'] : "";

	//pagina atual
	//se for definido na url o page, então vai ser o link desse page, se não for definido qual é a página atual, será 1. Esse parâmetro é utilizado nos métodos "getPageSearch" e "getPage".
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	if ($search != ''){

		//a linha abaixo, resumidamente, carrega as páginas com a lista de usuários pesquisados no $search
		$pagination = Category::getPageSearch($search, $page);

	} else {

		//$pagination = User::getPage($page, 1); //1 --> um usuário por página
		//a linha abaixo, resumidamente, carrega as páginas com a lista de usuários
		$pagination = Category::getPage($page); //1 --> um usuário por página

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

	$page->setTpl("categories", [
		"categories"=>$pagination['data'], //o $pagination na chave 'data', contém o resultado dos usuários.
		"search"=>$search,
		"pages"=>$pages
	]);

});

$app->get("/admin/categories/create", function(){

	//verificar se o usuário está logado para não acessar diretamente sem passar pela tela de autenticação
	User::verifyLogin();

	$page = new PageAdmin();

	$page->setTpl("categories-create");

});

$app->post("/admin/categories/create", function(){

	//verificar se o usuário está logado para não acessar diretamente sem passar pela tela de autenticação
	User::verifyLogin();

	$category = new Category();

	$category->setData($_POST);

	$category->save();

	header("Location: /admin/categories");
	exit;

});

$app->get("/admin/categories/:idcategory/delete", function($idcategory){

	//verificar se o usuário está logado para não acessar diretamente sem passar pela tela de autenticação
	User::verifyLogin();

	$category = new Category();

	$category->get((int)$idcategory);

	$category->delete();

	header("Location: /admin/categories");
	exit;

});

$app->get("/admin/categories/:idcategory", function($idcategory){

	//verificar se o usuário está logado para não acessar diretamente sem passar pela tela de autenticação
	User::verifyLogin();

	$category = new Category();

	$category->get($idcategory);

	$page = new PageAdmin();

	$page->setTpl("categories-update", [
		"category"=>$category->getValues()
	]);

});

$app->post("/admin/categories/:idcategory", function($idcategory){

	//verificar se o usuário está logado para não acessar diretamente sem passar pela tela de autenticação
	User::verifyLogin();

	$category = new Category();

	$category->get($idcategory);

	$category->setData($_POST);

	$category->save();

	header("Location: /admin/categories");
	exit;

});


$app->get("/admin/categories/:idcategory/products", function($idcategory){

	//verificar se o usuário está logado para não acessar diretamente sem passar pela tela de autenticação
	User::verifyLogin();

	$category = new Category();

	$category->get((int)$idcategory);

	$page = new PageAdmin();

	$page->setTpl("categories-products", [
		'category'=>$category->getValues(),
		'productsRelated'=>$category->getProducts(true),
		'productsNotRelated'=>$category->getProducts(false)
	]);
});

$app->get("/admin/categories/:idcategory/products/:idproduct/add", function($idcategory, $idproduct){

	//verificar se o usuário está logado para não acessar diretamente sem passar pela tela de autenticação
	User::verifyLogin();

	$category = new Category();

	$category->get((int)$idcategory);

	$product = new Product();

	$product->get((int)$idproduct);

	$category->addProduct($product);

	header("Location: /admin/categories/".$idcategory."/products");
	exit;
});


$app->get("/admin/categories/:idcategory/products/:idproduct/remove", function($idcategory, $idproduct){

	//verificar se o usuário está logado para não acessar diretamente sem passar pela tela de autenticação
	User::verifyLogin();

	$category = new Category();

	$category->get((int)$idcategory);

	$product = new Product();

	$product->get((int)$idproduct);

	$category->removeProduct($product);

	header("Location: /admin/categories/".$idcategory."/products");
	exit;
});





?>