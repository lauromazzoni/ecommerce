<?php

use \Hcode\PageAdmin;
use \Hcode\Page;
use \Hcode\Model\Product;

//primeira rota
$app->get('/', function() {

	$products = Product::listAll();
    
	$page = new Page(); //vai chamar o construct e adicionar o header na tela

	$page -> setTpl("index", [
		'products'=>Product::checkList($products)
	]);

	//"bem aqui" ele vai chamar o destruct e incluir o footer.html na tela
});












?>