<?php

use \Hcode\Page;
use \Hcode\Model\Product;
use \Hcode\Model\Category;
use \Hcode\Model\Cart;
use \Hcode\Model\Address;
use \Hcode\Model\User;

//primeira rota
$app->get('/', function() {

	$products = Product::listAll();
    
	$page = new Page(); //vai chamar o construct e adicionar o header na tela

	$page -> setTpl("index", [
		'products'=>Product::checkList($products)
	]);

	//"bem aqui" ele vai chamar o destruct e incluir o footer.html na tela
});


$app->get("/categories/:idcategory", function($idcategory){

	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	$category = new Category();

	$category->get((int)$idcategory);

	$pagination = $category->getProductsPage($page);

	$pages = [];

	for ($i=1; $i <= $pagination['pages']; $i++) { 
	 	array_push($pages, [
	 		'link'=>'/categories/'.$category->getidcategory().'?page='.$i,
	 		'page'=>$i
	 	]);
	}

	$page = new Page();

	$page->setTpl("category", [
		'category'=>$category->getValues(),
		'products'=>$pagination["data"],
		'pages'=>$pages
	]);

});

$app->get("/products/:desurl", function($desurl){

	$product = new Product();

	$product->getFromUrl($desurl);

	$page = new Page();

	$page->setTpl("product-detail",[
		'product'=>$product->getValues(),
		'categories'=>$product->getCategories()
		]);

});


$app->get("/cart", function(){

	$cart = Cart::getFromSession();

	$page = new Page();

	$page->setTpl("cart", [
		'cart'=>$cart->getValues(),
		'products'=>$cart->getProducts(),
		'error'=>Cart::getMsgError()
	]);
});


//o $idproduct na function (lina logo abaixo) é o parâmetro informado na url
$app->get("/cart/:idproduct/add", function($idproduct){

	$product = new Product();

	$product->get((int)$idproduct);

	//recuperando o carrinho da sessão, se não existir ele cria um novo
	$cart = Cart::getFromSession();

	//se o "qtd" for informado no $_GET, o valor será o cast para int da variável recebida "qtd". Se o "qtd" não foi definido, o padrão é 1
	$qtd = (isset($_GET['qtd'])) ? (int)$_GET['qtd'] : 1;

	for ($i = 0; $i < $qtd; $i++){
		$cart->addProduct($product); //chama o método addProduct quantas vezes forem necessárias (de acordo com o $qtd da linha acima)
	}

	header("Location: /cart");
	exit;

});


//o "minus" na url abaixo é só para remover um do carrinho
$app->get("/cart/:idproduct/minus", function($idproduct){

	$product = new Product();

	$product->get((int)$idproduct);

	//recuperando o carrinho da sessão
	$cart = Cart::getFromSession();

	//o removeProduct recebendo somente o $product irá remover somente um, já que o segundo parâmentro está sendo passado como false (padrão)
	$cart->removeProduct($product);

	header("Location: /cart");
	exit;

});

//remove todos os produtos do carrinho
$app->get("/cart/:idproduct/remove", function($idproduct){

	$product = new Product();

	$product->get((int)$idproduct);

	//recuperando o carrinho da sessão, se não existir ele cria um novo
	$cart = Cart::getFromSession();

	//está passando o true para a função pois irá remover todos
	$cart->removeProduct($product, true);

	header("Location: /cart");
	exit;

});

$app->post("/cart/freight", function(){

	$cart = Cart::getFromSession();

	$cart->setFreight($_POST['zipcode']);

	header("Location: /cart");

	exit;
});

//a pessoa só pode acessar esta rota se estiver logada ou cadastrada
$app->get("/checkout", function(){

	User::verifyLogin(false);

	$cart = Cart::getFromSession();

	$address = new Address();

	$page = new Page();

	$page->setTpl("checkout", [
		'cart'=>$cart->getValues(),
		'address'=>$address->getValues()
	]);
});


$app->get("/login", function(){

	$page = new Page();

	$page->setTpl("login", [
		'error'=>user::getError(),
		'errorRegister'=>User::getErrorRegister(),
		'registerValues'=>(isset($_SESSION['registerValues'])) ? $_SESSION['registerValues'] : ['name'=>'', 'email'=>'', 'phone'=>'']
	]);
});

$app->post("/login", function(){

	try {

		User::login($_POST['login'], $_POST['password']);

	} catch (Exception $e){

		User::setError($e->getMessage());

	}

	header("Location: /checkout");

	exit;
});

$app->get("/logout", function(){

	User::logout();

	header("Location: /login");

	exit;
});


$app->post("/register", function(){

	//guarda os dados do formulário do "Criar conta" caso dê algum erro. Aí o usuário não precisa digitar tudo de novo. Por isso recebe o $_POST
	$_SESSION['registerValues'] = $_POST;


	if (!isset($_POST['name']) || $_POST['name'] == ''){

		User::setErrorRegister("Preencha o seu nome.");

		header("Location: /login");

		exit;
	}

	if (!isset($_POST['email']) || $_POST['email'] == ''){

		User::setErrorRegister("Preencha o seu e-mail.");

		header("Location: /login");

		exit;
	}

	if (!isset($_POST['password']) || $_POST['password'] == ''){

		User::setErrorRegister("Preencha a sua senha.");

		header("Location: /login");

		exit;
	}

	if (User::checkLoginExist($_POST['email']) === true){

		User::setErrorRegister("Este endereço de e-mail já está sendo utilizado. Digite outro por favor.");

		header("Location: /login");

		exit;

	}

	$user = new User();

	$user->setData([
		'inadmin'=>0,  //Está forçando o inadmin a 0 pois quem se cadastra no site não é um administrador
		'deslogin'=>$_POST['email'],
		'desperson'=>$_POST['name'],
		'desemail'=>$_POST['email'],
		'despassword'=>$_POST['password'],
		'nrphone'=>$_POST['phone']
	]);

	$user->save();

	//Se não existisse a linha logo abaixo, o usuário seria redirecionado para o /checkout e, como ele não estaria logado, seria solicitado que ele fizesse o login. Como já foi permitido a pessoa criar um usuário, porque não deixa-la logada de uma vez? Por isto, já faz a autenticação dele com a linha logo abaixo
	User::login($_POST['email'], $_POST['password']);

	header("Location: /checkout");

	exit;
});


$app->get('/forgot', function(){

	$page = new Page();

	$page->setTpl("forgot");
});

//verificar o arquivo forgot.html
$app->post('/forgot', function(){
	
	//o "false" é porque não é um administrador
	$user = User::getForgot($_POST["email"], false);

	//redireciona o usuário para confirmar se o email foi enviado com sucesso
	header("Location: /forgot/sent");
	exit;
});

$app->get('/forgot/sent', function(){

	$page = new Page();

	//carrega o template (não precisa do header nem do footer neste caso).
	$page->setTpl("forgot-sent");
});

$app->get("/forgot/reset", function(){

	//recupera o código para saber a qual usuário pertence
	$user = User::validForgotDecrypt($_GET["code"]);
	$page = new Page();


	//carrega o template (não precisa do header nem do footer neste caso).
	$page->setTpl("forgot-reset", array(
		"name"=>$user["desperson"],
		"code"=>$_GET["code"]
	));

});

$app->post("/forgot/reset", function(){

	//Verifica de novo para checar problema na segurança ===> User::validForgotDecrypt($_POST["code"]);	
	$forgot = User::validForgotDecrypt($_POST["code"]);	

	User::setForgotUsed($forgot["idrecovery"]);

	$user = new User();

	$user->get((int)$forgot["iduser"]); //pegando os dados do usuário

	//o 'cost' é o custo para gerar o hash. Quanto maior mais seguro e gasta mais processamente,
	//podendo derrubar o servidor. 
	$password = password_hash($_POST["password"], PASSWORD_DEFAULT, ["cost"=>12]);

	$user->setPassword($password);


	$page = new Page();


	//carrega o template (não precisa do header nem do footer neste caso).
	//não passa nenhuma array de dados porque o template abaixo não tem variáveis
	$page->setTpl("forgot-reset-success");
});




?>

