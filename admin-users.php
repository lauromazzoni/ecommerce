<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User;


$app->get('/admin/users', function(){
	//verificar se o usuário está logado para não acessar diretamente sem passar pela tela de autenticação
	User::verifyLogin();
	$users = User::listAll();
	$page = new PageAdmin();
	$page -> setTpl("users", array(
		"users"=>$users
	));
});

//responde com um html
$app->get('/admin/users/create', function(){
	User::verifyLogin();
	$page = new PageAdmin();
	$page -> setTpl("users-create");
});

//Este $app (da linha debaixo) tem que ficar nesta posição do código, isto é, acima do $app da sequência.
//Se estivesse invertico, como o $app abaixo (delete) tem o :iduser, o Slim framework na hora de deletar, iria parar no no $app de update e não chegaria no $app que tem o delete
$app->get('/admin/users/:iduser/delete', function($iduser){
	User::verifyLogin();
	$user = new User();
	$user->get((int)$iduser);
	$user->delete();
	header("Location: /admin/users");
	exit;

});

//o :iduser da rota é o $iduser passado como parâmetro na função
$app->get('/admin/users/:iduser', function($iduser){
	User::verifyLogin();
	$user = new User();
	$user->get((int)$iduser);
	$page = new PageAdmin();
	$page->setTpl("users-update", array(
		"user"=>$user->getValues()
	));
});

//vai receber os dados via post e inserir no banco
/*$app->post('/admin/users/create', function(){
	User::verifyLogin();
	$user = new User();
	$user->setData($_POST);
	//var_dump($_POST);
	$user->save();

});*/

$app->post("/admin/users/create", function () {

 	User::verifyLogin();
	$user = new User();
	//verifica se o inadmin foi setado. Se foi, ele recebe o valor 1, senão 0.
 	$_POST["inadmin"] = (isset($_POST["inadmin"])) ? 1 : 0;
 	$_POST['despassword'] = password_hash($_POST["despassword"], PASSWORD_DEFAULT, [
 		"cost"=>12
 	]);
 	$user->setData($_POST);
	$user->save();
	header("Location: /admin/users");
 	exit;
});

$app->post('/admin/users/:iduser', function($iduser){
	User::verifyLogin();
	$user = new User();
	//verifica se o inadmin foi setado. Se foi, ele recebe o valor 1, senão 0.
	//faz a validação do inadmin para saber se é 1 ou 0;
	$_POST["inadmin"] = (isset($_POST["inadmin"])) ? 1 : 0;
	$user->get((int)$iduser);
	$user->setData($_POST);
	$user->update();
	header("Location: /admin/users");
	exit;
	

});








?>