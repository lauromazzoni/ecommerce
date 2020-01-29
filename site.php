<?php

use \Hcode\Page;
use \Hcode\Model\Product;
use \Hcode\Model\Category;
use \Hcode\Model\Cart;
use \Hcode\Model\Address;
use \Hcode\Model\User;
use \Hcode\Model\Order;
use \Hcode\Model\OrderStatus;


//primeira rota
$app->get('/', function() {

	//os " :: " significa que é um método estático, isto é, não precisa instanciar a classe para chamar o método
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

	$address = new Address();
	$cart = Cart::getFromSession();

	if (!isset($_GET['zipcode'])) {

		$_GET['zipcode'] = $cart->getdeszipcode();

	}

	if (isset($_GET['zipcode'])) {

		$address->loadFromCEP($_GET['zipcode']);

		$cart->setdeszipcode($_GET['zipcode']);

		$cart->save();

		$cart->getCalculateTotal();

	}

	if (!$address->getdesaddress()) $address->setdesaddress('');
	if (!$address->getdescomplement()) $address->setdescomplement('');
	if (!$address->getdesdistrict()) $address->setdesdistrict('');
	if (!$address->getdescity()) $address->setdescity('');
	if (!$address->getdesstate()) $address->setdesstate('');
	if (!$address->getdescountry()) $address->setdescountry('');
	if (!$address->getdeszipcode()) $address->setdeszipcode('');

	$page = new Page();

	$page->setTpl("checkout", [
		'cart'=>$cart->getValues(),
		'address'=>$address->getValues(),
		'products'=>$cart->getProducts(),
		'error'=>Address::getMsgError()
	]);

});

$app->post("/checkout", function(){

	User::verifyLogin(false);

	if (!isset($_POST['zipcode']) || $_POST['zipcode'] === '') {
		Address::setMsgError("Informe o CEP.");
		header('Location: /checkout');
		exit;
	}

	if (!isset($_POST['desaddress']) || $_POST['desaddress'] === '') {
		Address::setMsgError("Informe o endereço.");
		header('Location: /checkout');
		exit;
	}

	if (!isset($_POST['desdistrict']) || $_POST['desdistrict'] === '') {
		Address::setMsgError("Informe o bairro.");
		header('Location: /checkout');
		exit;
	}

	if (!isset($_POST['descity']) || $_POST['descity'] === '') {
		Address::setMsgError("Informe a cidade.");
		header('Location: /checkout');
		exit;
	}

	if (!isset($_POST['desstate']) || $_POST['desstate'] === '') {
		Address::setMsgError("Informe o estado.");
		header('Location: /checkout');
		exit;
	}

	if (!isset($_POST['descountry']) || $_POST['descountry'] === '') {
		Address::setMsgError("Informe o país.");
		header('Location: /checkout');
		exit;
	}

	$user = User::getFromSession();

	$address = new Address();

	$_POST['deszipcode'] = $_POST['zipcode'];
	$_POST['idperson'] = $user->getidperson();

	$address->setData($_POST);

	$address->save();

	$cart = Cart::getFromSession();

	$cart->getCalculateTotal();

	$order = new Order();

	$order->setData([
		'idcart'=>$cart->getidcart(),
		'idaddress'=>$address->getidaddress(),
		'iduser'=>$user->getiduser(),
		'idstatus'=>OrderStatus::EM_ABERTO,
		'vltotal'=>$cart->getvltotal()
	]);

	$order->save();

	header("Location: /order/".$order->getidorder());

/*	switch ((int)$_POST['payment-method']) {

		case 1:
		header("Location: /order/".$order->getidorder()."/pagseguro");
		break;

		case 2:
		header("Location: /order/".$order->getidorder()."/paypal");
		break;

	}*/

	exit;

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
		'despassword'=>password_hash($_POST["password"], PASSWORD_BCRYPT),
		'nrphone'=>$_POST['phone']
	]);

	$user->save();

	$_POST['despassword'] = password_hash($_POST["password"], PASSWORD_BCRYPT);


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


$app->get("/profile", function(){

	//o "false" é para mostrar que não é administrativo. Aqui está sendo forçado a não ser administrativo
	User::verifyLogin(false); 

	$user = User::getFromSession();

	$page = new Page();

	$page->setTpl("profile", [
		'user'=>$user->getValues(),
		'profileMsg'=>User::getSuccess(),
		'profileError'=>User::getError()
	]);

});


$app->post("/profile", function(){

	User::verifyLogin(false);

	//se o 'desperson' do POST não existir ou for igual a vazio
	if (!isset($_POST['desperson']) || $_POST['desperson'] === ''){
		User::setError("Preencha o seu nome");
		header("Location: /profile");
		exit;
	}

	//se o 'desemail' do POST não existir ou for igual a vazio
	if (!isset($_POST['desemail']) || $_POST['desemail'] === ''){
		User::setError("Preencha o seu e-mail");
		header("Location: /profile");
		exit;
	}

	$user = User::getFromSession();

	//Verificando se o usuário alterou o email, comparando o 'desemail' do POST com o existente no BD
	if ($_POST['desemail'] !== $user->getdesemail()){

		//Verificando se o email já está sendo utilizado
		if (User::checkLoginExist($_POST['desemail']) === true){

			User::setError("Este endereço de e-mail já está cadastrado.");
			header("Location: /profile");
			exit;
		}
	}	  


	//a sobrecarga abaixo do $_POST é para que o usuário não passe o campo inadmin ou a senha, senão, muda o profile para o do administrador
	//Não importa que o usuário digite, o 'inadin' e o 'despassword' da variáfel $_POST irão recebre os valores da sessão.
	$_POST['inadmin'] = $user->getinadmin();
	$_POST['despassword'] = $user->getdespassword();
	$_POST['deslogin'] = $_POST['desemail']; //definindo o login como sendo o próprio e-mail do usuário

	$user->setData($_POST);

	//use o método $user->update() ao invés de save(), para que os dados do usuário sejam atualizados e não crie um usuário novo
	$user->update();
	//$user->save();

	//se a execução chegou até aqui, pode setar a mensagem de sucesso
	User::setSuccess("Dados alterados com sucesso.");

	header('Location: /profile');

	exit;



});

$app->get("/order/:idorder", function($idorder){

	User::verifyLogin(false);

	$order = new Order();

	$order->get((int)$idorder);

	$page = new Page();

	$page->setTpl("payment", [
		'order'=>$order->getValues()
	]);

});

$app->get("/boleto/:idorder", function($idorder){

	User::verifyLogin(false);

	$order = new Order();

	$order->get((int)$idorder);

	// DADOS DO BOLETO PARA O SEU CLIENTE
	$dias_de_prazo_para_pagamento = 10;
	$taxa_boleto = 5.00;
	$data_venc = date("d/m/Y", time() + ($dias_de_prazo_para_pagamento * 86400));  // Prazo de X dias OU informe data: "13/04/2006"; 

	$valor_cobrado = formatPrice($order->getvltotal()); // Valor - REGRA: Sem pontos na milhar e tanto faz com "." ou "," ou com 1 ou 2 ou sem casa decimal
	$valor_cobrado = str_replace(".", "", $valor_cobrado);
	$valor_cobrado = str_replace(",", ".",$valor_cobrado);
	$valor_boleto=number_format($valor_cobrado+$taxa_boleto, 2, ',', '');

	$dadosboleto["nosso_numero"] = $order->getidorder();  // Nosso numero - REGRA: Máximo de 8 caracteres!
	$dadosboleto["numero_documento"] = $order->getidorder();	// Num do pedido ou nosso numero
	$dadosboleto["data_vencimento"] = $data_venc; // Data de Vencimento do Boleto - REGRA: Formato DD/MM/AAAA
	$dadosboleto["data_documento"] = date("d/m/Y"); // Data de emissão do Boleto
	$dadosboleto["data_processamento"] = date("d/m/Y"); // Data de processamento do boleto (opcional)
	$dadosboleto["valor_boleto"] = $valor_boleto; 	// Valor do Boleto - REGRA: Com vírgula e sempre com duas casas depois da virgula

	// DADOS DO SEU CLIENTE
	$dadosboleto["sacado"] = $order->getdesperson();
	$dadosboleto["endereco1"] = $order->getdesaddress() . " " . $order->getdesdistrict();
	$dadosboleto["endereco2"] = $order->getdescity() . " - " . $order->getdesstate() . " - " . $order->getdescountry() . " -  CEP: " . $order->getdeszipcode();

	// INFORMACOES PARA O CLIENTE
	$dadosboleto["demonstrativo1"] = "Pagamento de Compra na Loja Hcode E-commerce";
	$dadosboleto["demonstrativo2"] = "Taxa bancária - R$ 0,00";
	$dadosboleto["demonstrativo3"] = "";
	$dadosboleto["instrucoes1"] = "- Sr. Caixa, cobrar multa de 2% após o vencimento";
	$dadosboleto["instrucoes2"] = "- Receber até 10 dias após o vencimento";
	$dadosboleto["instrucoes3"] = "- Em caso de dúvidas entre em contato conosco: suporte@hcode.com.br";
	$dadosboleto["instrucoes4"] = "&nbsp; Emitido pelo sistema Projeto Loja Hcode E-commerce - www.hcode.com.br";

	// DADOS OPCIONAIS DE ACORDO COM O BANCO OU CLIENTE
	$dadosboleto["quantidade"] = "";
	$dadosboleto["valor_unitario"] = "";
	$dadosboleto["aceite"] = "";		
	$dadosboleto["especie"] = "R$";
	$dadosboleto["especie_doc"] = "";


	// ---------------------- DADOS FIXOS DE CONFIGURAÇÃO DO SEU BOLETO --------------- //


	// DADOS DA SUA CONTA - ITAÚ
	$dadosboleto["agencia"] = "1690"; // Num da agencia, sem digito
	$dadosboleto["conta"] = "48781";	// Num da conta, sem digito
	$dadosboleto["conta_dv"] = "2"; 	// Digito do Num da conta

	// DADOS PERSONALIZADOS - ITAÚ
	$dadosboleto["carteira"] = "175";  // Código da Carteira: pode ser 175, 174, 104, 109, 178, ou 157

	// SEUS DADOS
	$dadosboleto["identificacao"] = "Hcode Treinamentos";
	$dadosboleto["cpf_cnpj"] = "24.700.731/0001-08";
	$dadosboleto["endereco"] = "Rua Ademar Saraiva Leão, 234 - Alvarenga, 09853-120";
	$dadosboleto["cidade_uf"] = "São Bernardo do Campo - SP";
	$dadosboleto["cedente"] = "HCODE TREINAMENTOS LTDA - ME";

	// NÃO ALTERAR!
	$path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "res" . DIRECTORY_SEPARATOR . "boletophp" . DIRECTORY_SEPARATOR . "include" . DIRECTORY_SEPARATOR;

	require_once($path . "funcoes_itau.php");
	require_once($path . "layout_itau.php");

	});



$app->get("/profile/orders", function(){

	User::verifyLogin(false);

	$user = User::getFromSession();

	$page = new Page();

	$page->setTpl("profile-orders", [
		'orders'=>$user->getOrders()
	]);

});

$app->get("/profile/orders/:idorder", function($idorder){

	User::verifyLogin(false);

	$order = new Order();

	$order->get((int)$idorder);

	//não pega o cart da sessão pois o correto é o carte deste pedido
	$cart = new Cart();

	$cart->get((int)$order->getidcart());

	$cart->getCalculateTotal();

	$page = new Page();

	$page->setTpl("profile-orders-detail", [
		'order'=>$order->getValues(),
		'cart'=>$cart->getValues(),
		'products'=>$cart->getProducts()
	]);	

});


$app->get("/profile/change-password", function(){

	User::verifyLogin(false);

	$page = new Page();

	$page->setTpl("profile-change-password", [
		'changePassError'=>User::getError(),
		'changePassSuccess'=>User::getSuccess()
	]);
});

$app->post("/profile/change-password", function(){

	User::verifyLogin(false);

	if (!isset($_POST['current_pass']) || $_POST['current_pass'] === ''){

		User::setError("Digite a senha atual.");
		header("Location: /profile/change-password");
		exit;
	}

	if (!isset($_POST['new_pass']) || $_POST['new_pass'] === ''){

		User::setError("Digite a nova senha.");
		header("Location: /profile/change-password");
		exit;
	}

	if (!isset($_POST['new_pass_confirm']) || $_POST['new_pass_confirm'] === ''){

		User::setError("Confirme a nova senha.");
		header("Location: /profile/change-password");
		exit;
	}

	if ($_POST['current_pass'] === $_POST['new_pass']){

		User::setError("A sua nova senha deve ser diferente da atual.");
		header("Location: /profile/change-password");
		exit;
	}

	$user = User::getFromSession();

	if(!password_verify($_POST['current_pass'], $user->getdespassword())){

		User::setError("A senha está inválida.");
		header("Location: /profile/change-password");
		exit;
	}

	$user->setdespassword(User::getPasswordHash($_POST['new_pass']));

	$user->update();

	User::setSuccess("Senha alterada com sucesso!");

	header("Location: /profile/change-password");
	
	exit;
});
?>

