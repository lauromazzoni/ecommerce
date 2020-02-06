<?php 

session_start();

require_once("vendor/autoload.php");

use \Slim\Slim;

/*$aux = $_SERVER['QUERY_STRING'];

function UrlAtual(){
 $dominio= $_SERVER['HTTP_HOST'] . "=======";
 $url = "http://" . $dominio. $_SERVER['REQUEST_URI'];
 return $url;
 }
 echo "a url atual é: " . UrlAtual() . "\n";*/

$app = new Slim();

$app->config('debug', true); //se der erro ele avisa

require_once("functions.php");
require_once("site.php");
require_once("admin.php");
require_once("admin-users.php");
require_once("admin-categories.php");
require_once("admin-products.php");
require_once("admin-orders.php");

$app->run(); //é o que faz rodar tudo que está acima

 ?>
