<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Order;
use \Hcode\Model\OrderStatus;


$app->get("/admin/orders/:idorder/status", function($idorder){

	User::verifyLogin();

	$order = new Order();

	//carrega para verificar se o pedido ainda existe no banco de dados.
	$order->get((int)$idorder);

	$page = new PageAdmin();

	$page->setTpl("order-status", [
		'order'=>$order->getValues(),
		'status'=>OrderStatus::listAll(),
		'msgSuccess'=>Order::getSuccess(),
		'msgError'=>Order::getError()
	]);

});

$app->post("/admin/orders/:idorder/status", function($idorder){

	User::verifyLogin();

	if (!isset($_POST['idstatus']) || !(int)$_POST['idstatus'] > 0) {
		Order::setError("Informe o status atual.");
		header("Location: /admin/orders/".$idorder."/status");
		exit;
	}

	$order = new Order();
	
	//carrega para verificar se o pedido ainda existe no banco de dados.
	$order->get((int)$idorder);

	$order->setidstatus((int)$_POST['idstatus']);

	$order->save();

	Order::setSuccess("Status atualizado.");

	header("Location: /admin/orders/".$idorder."/status");
	exit;

});

$app->get("/admin/orders/:idorder/delete", function($idorder){

	User::verifyLogin();

	$order = new Order();

	//carrega para verificar se o pedido ainda existe no banco de dados.
	$order->get((int)$idorder);

	$order->delete();

	header("Location: /admin/orders");

	exit;
});

$app->get("/admin/orders/:idorder", function($idorder){

	User::verifyLogin();

	$order = new Order();

	$order->get((int)$idorder);

	$cart = $order->getCart();

	$page = new PageAdmin();

	$page->setTpl("order", [
		'order'=>$order->getValues(),
		'cart'=>$cart->getValues(),
		'products'=>$cart->getProducts()
	]);

});


$app->get("/admin/orders", function(){

	User::verifyLogin();

	//Se ela existir, vem ela mesmo. Se não existir, vem vazio "".
	$search = (isset($_GET['search'])) ? $_GET['search'] : "";

	//pagina atual
	//se for definido na url o page, então vai ser o link desse page, se não for definido qual é a página atual, será 1. Esse parâmetro é utilizado nos métodos "getPageSearch" e "getPage".
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	if ($search != ''){

		//a linha abaixo, resumidamente, carrega as páginas com a lista de usuários pesquisados no $search
		$pagination = Order::getPageSearch($search, $page);

	} else {

		//$pagination = User::getPage($page, 1); //1 --> um usuário por página
		//a linha abaixo, resumidamente, carrega as páginas com a lista de usuários
		$pagination = Order::getPage($page); //1 --> um usuário por página

	}

	//o array $pages irá conter o texto do search e o link da página
	$pages = [];

	//número de páginas ---> $pagination['pages']
	for ($x = 0; $x < $pagination['pages']; $x++){

		array_push($pages, [
			'href'=> '/admin/orders?'.http_build_query([  /// a interrogação (.../users?) serve para concatenar
				'page'=>$x + 1, //só para não começar da página zero, senão irá ficar um "0" no botão que mostras as páginas
				'search'=>$search //tem que passar o $search pois, caso esteja sendo realizada alguma pesquisa, esta continue na nova página
		]),
		//só para não começar da página zero, senão irá ficar um "0" no botão que mostras as páginas
		'text'=>$x + 1
	]);

	}

	$page = new PageAdmin();

	$page->setTpl("orders", [
		"orders"=>$pagination['data'], //o $pagination na chave 'data', contém o resultado dos pedidos.
		"search"=>$search,
		"pages"=>$pages
	]);

});












?>