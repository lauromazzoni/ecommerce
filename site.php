<?php

use \Hcode\PageAdmin;
use \Hcode\Page;

//primeira rota
$app->get('/', function() {
    
	$page = new Page(); //vai chamar o construct e adicionar o header na tela

	$page -> setTpl("index");

	//"bem aqui" ele vai chamar o destruct e incluir o footer.html na tela
});












?>