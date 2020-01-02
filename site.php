<?php

use \Hcode\Page;
use \Hcode\Model\Product;
use \Hcode\Model\Category;
use \Hcode\Model\Cart;

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
		'products'=>$cart->getProducts()
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






?>