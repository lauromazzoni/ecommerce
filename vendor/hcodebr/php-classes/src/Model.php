<?php


namespace Hcode;

class Model {


private $values = [];

public function __call($name, $args){  //$name --> nome do método que chamou $args --> são os parâmetros passados

	//substr --> pega 3 caracteres da palavra ($name) desde a posição inicial (0);
	$method = substr($name, 0, 3);
	//substr --> pega da terceira posição até o fim
	$fieldName = substr($name, 3, strlen($name));

	switch ($method) {
		case "get":
			//se for get, como irá pegar o valor no banco, eu só preciso saber o nome da coluna desejada
			return $this->values[$fieldName];
		break;

		case "set":
			//se for set, é preciso saber o valor a ser setado no banco.
			return $this->values[$fieldName] = $args[0];
		break;
	}
}

public function setData($data = array()){

	foreach ($data as $key => $value) {
		
		//TUDO QUE FOR CRIADO DINAMICAMENTE NO PHP TEM QUE USAR CHAVES " { } ".
		$this -> {"set".$key}($value);
	}
}

public function getValues(){

	return $this->values;
}



}
?>