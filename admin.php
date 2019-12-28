<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User;


//segunda rota (para o Admin)
$app->get('/admin', function() {

	//verifica se está logado
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


$app->get('/admin/forgot', function(){
	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
		]);

	$page->setTpl("forgot");
});

//verificar o arquivo forgot.html
$app->post('/admin/forgot', function(){
	
	$user = User::getForgot($_POST["email"]);

	//redireciona o usuário para confirmar se o email foi enviado com sucesso
	header("Location: /admin/forgot/sent");
	exit;
});

$app->get('/admin/forgot/sent', function(){
	$page = new PageAdmin([
	"header"=>false,
	"footer"=>false
	]);

	//carrega o template (não precisa do header nem do footer neste caso).
	$page->setTpl("forgot-sent");
});

$app->get("/admin/forgot/reset", function(){

	//recupera o código para saber a qual usuário pertence
	$user = User::validForgotDecrypt($_GET["code"]);
	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	//carrega o template (não precisa do header nem do footer neste caso).
	$page->setTpl("forgot-reset", array(
		"name"=>$user["desperson"],
		"code"=>$_GET["code"]
	));

});

$app->post("/admin/forgot/reset", function(){

	//Verifica de novo para checar problema na segurança ===> User::validForgotDecrypt($_POST["code"]);	
	$forgot = User::validForgotDecrypt($_POST["code"]);	

	User::setForgotUsed($forgot["idrecovery"]);

	$user = new User();

	$user->get((int)$forgot["iduser"]); //pegando os dados do usuário

	//o 'cost' é o custo para gerar o hash. Quanto maior mais seguro e gasta mais processamente,
	//podendo derrubar o servidor. 
	$password = password_hash($_POST["password"], PASSWORD_DEFAULT, ["cost"=>12]);

	$user->setPassword($password);


	$page = new PageAdmin([
		"header"=>false,
		"footer"=>false
	]);

	//carrega o template (não precisa do header nem do footer neste caso).
	//não passa nenhuma array de dados porque o template abaixo não tem variáveis
	$page->setTpl("forgot-reset-success");
});






?>