<?php 

session_start();

require_once("vendor/autoload.php");

use \Slim\Slim;
use \Hcode\Page;
use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Category;

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

$app->get("/admin/categories", function(){

	//verificar se o usuário está logado para não acessar diretamente sem passar pela tela de autenticação
	User::verifyLogin();

	$categories = Category::listAll();

	$page = new PageAdmin();

	$page->setTpl("categories", [
		'categories'=>$categories
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

$app->get("/categories/:idcategory", function($idcategory){

	$category = new Category();

	$category->get((int)$idcategory);

	$page = new Page();

	$page->setTpl("category", [
		'category'=>$category->getValues(),
		'pruducts'=>[]
	]);

});



$app->run(); //é o que faz rodar tudo que está acima

 ?>
