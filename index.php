<?php 

session_start();

require_once("vendor/autoload.php");

use \Slim\Slim;
use \Hcode\Page;
use \Hcode\PageAdmin;
use \Hcode\Model\User;

//print_r($_SERVER);
$aux = $_SERVER['QUERY_STRING'];
//echo $aux;


/*function UrlAtual(){
 $dominio= $_SERVER['HTTP_HOST'] . "=======";
 $url = "http://" . $dominio. $_SERVER['REQUEST_URI'];
 return $url;
 }
 echo "a url atual é: " . UrlAtual() . "\n";*/

$app = new Slim();

$app->config('debug', true); //se der erro ele avisa

//primeira rota
$app->get('/', function() {
    
	$page = new Page(); //vai chamar o construct e adicionar o header na tela

	$page -> setTpl("index");

	//"bem aqui" ele vai chamar o destruct e incluir o footer.html na tela
});

//segunda rota (para o Admin)
$app->get('/admin', function() {

	User::verifyLogin();
    
	$page = new PageAdmin(); //vai chamar o construct e adicionar o header na tela

	$page -> setTpl("index");

	//"bem aqui" ele vai chamar o destruct e incluir o footer.html na tela
});

$app->get('/admin/login', function() {
    
	$page = new PageAdmin([
		"header" => false,
		"footer" => false
		]); //vai chamar o construct e adicionar o header na tela

	$page -> setTpl("login");

	//"bem aqui" ele vai chamar o destruct e incluir o footer.html na tela
});

$app->post('/admin/login', function(){
	//VALIDANDO O LOGIN

	//Método estático "::login" que recebe o post do login e o post do password
	User::login($_POST["login"], $_POST["password"]);

	//redirecinando para a página da administração
	header("Location: /admin");

	exit;


});

$app->get('/admin/logout', function(){
	User::logout();
	header("Location: /admin/login");
	exit;
});

$app->run(); //é o que faz rodar tudo que está acima

 ?>
