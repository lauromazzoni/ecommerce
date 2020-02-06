<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User;


$app->get('/admin/users/:iduser/password', function($iduser){

	User::verifyLogin();

	$user = new User();

	$user->get((int)$iduser);

	$page = new PageAdmin();

	$page->setTpl("users-password", [
		"user"=>$user->getValues(),
		"msgError"=>User::getError(),
		"msgSuccess"=>User::getSuccess()
	]);

});


$app->post('/admin/users/:iduser/password', function($iduser){

	User::verifyLogin();

	if (!isset($_POST['despassword']) || $_POST['despassword'] === ''){

		User::setError("Preencha a nova senha.");
		header("Location: /admin/users/$iduser/password");
		exit;
	}

	if (!isset($_POST['despassword-confirm']) || $_POST['despassword-confirm'] === ''){

		User::setError("Preencha a confirmação da nova senha.");
		header("Location: /admin/users/$iduser/password");
		exit;
	}

	if ($_POST['despassword'] !== $_POST['despassword-confirm']){
		User::setError("Senhas diferentes. Entre com uma nova senha e confirme.");
		header("Location: /admin/users/$iduser/password");
		exit;

	}

	$user = new User();

	$user->get((int)$iduser);

	$user->setPassword(password_hash($_POST["despassword"], PASSWORD_BCRYPT));

	User::setSuccess("Senha alterada com sucesso.");

	header("Location: /admin/users/$iduser/password");

	exit;

});


$app->get('/admin/users', function(){
	//verificar se o usuário está logado para não acessar diretamente sem passar pela tela de autenticação
	User::verifyLogin();

	//Se ela existir, vem ela mesmo. Se não existir, vem vazio "".
	$search = (isset($_GET['search'])) ? $_GET['search'] : "";

	//pagina atual
	//se for definido na url o page, então vai ser o link desse page, se não for definido qual é a página atual, será 1. Esse parâmetro é utilizado nos métodos "getPageSearch" e "getPage".
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	if ($search != ''){

		//a linha abaixo, resumidamente, carrega as páginas com a lista de usuários pesquisados no $search
		$pagination = User::getPageSearch($search, $page);

	} else {

		//$pagination = User::getPage($page, 1); //1 --> um usuário por página
		//a linha abaixo, resumidamente, carrega as páginas com a lista de usuários
		$pagination = User::getPage($page); //1 --> um usuário por página

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

	$page -> setTpl("users", array(
		"users"=>$pagination['data'], //o $pagination na chave 'data', contém o resultado dos usuários.
		"search"=>$search,
		"pages"=>$pages
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
     
     $_POST["inadmin"] = (isset($_POST["inadmin"])) ? 1 : 0;
     
     $_POST['despassword'] = password_hash($_POST["despassword"], PASSWORD_BCRYPT);
     
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