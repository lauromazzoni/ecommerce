<?php


namespace Hcode;

use Rain\Tpl;


class Page {

	//IMPORTANTE:
	//VERIFICAR O ARQUIVO "vendor/rain/raintpl" POIS ELE MOSTRA COMO UTILIZAR A VARIÁVEL $tpl
	private $tpl;
	private $options = [];
	private $defaults = [
		"data" => []
	];

	//criando o método mágico construct e destruct
	public function __construct($opts = array()){

		$this -> options = array_merge($this->defaults, $opts);

		// configurando o template. É preciso saber o diretório onde irá pegar os arquivos e um diretório de cache
		$config = array(
			//$_SERVER["DOCUMENT_ROOT"]  ==> pega a pasta raiz do projeto no servidor
			"tpl_dir"       => $_SERVER["DOCUMENT_ROOT"] . "/views/",
			"cache_dir"     => $_SERVER["DOCUMENT_ROOT"]  . "views-cache/",
			"debug"         => false // set to false to improve the speed
		   );

		Tpl::configure( $config );

		$this -> tpl = new Tpl;

		$this -> setData($this -> options["data"]);

		$this -> tpl -> draw("header");

	}

	private function setData($data = array()){
		foreach ($data as $key => $value) {
			$this -> tpl -> assign($key, $value);
		}
	
	}

	public function setTpl($name, $data = array(), $returnHTML = false){

		$this -> setData($data); 

		return $this -> tpl -> draw($name, $returnHTML);
	}

	public function __destruct(){


		$this -> tpl -> draw("footer");

	}



}











?>