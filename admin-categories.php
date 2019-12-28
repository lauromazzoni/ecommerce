<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Category;
use \Hcode\Page;


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






?>