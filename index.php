<?php 

require_once("vendor/autoload.php");

use \Slim\Slim;
use \Hcode\Page;

$app = new Slim();

$app->config('debug', true); //se der erro ele avisa

$app->get('/', function() {
    
	$page = new Page(); //vai chamar o construct e adicionar o header na tela

	$page -> setTpl("index");

	//"bem aqui" ele vai chamar o destruct e incluir o footer.html na tela
});

$app->run(); //é o que faz rodar tudo que está acima

 ?>
