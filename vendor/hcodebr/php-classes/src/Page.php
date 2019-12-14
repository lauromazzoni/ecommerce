<?php

namespace Hcode;

use Rain\Tpl;


class Page {

	//IMPORTANTE:
	//VERIFICAR O ARQUIVO "vendor/rain/raintpl" POIS ELE MOSTRA COMO UTILIZAR A VARIÁVEL $tpl
	private $tpl;
	private $options = [];
	private $defaults = [
		"header" => true,
		"footer" => true,
		"data" => []
	];

	//criando o método mágico construct e destruct
	public function __construct($opts = array(), $tpl_dir = "/views/"){
		//var_dump($opts);
		//echo "=====<br>" . $tpl_dir . "     ---- class Page";
		$this -> options = array_merge($this->defaults, $opts);

		// configurando o template. É preciso saber o diretório onde irá pegar os arquivos e um diretório de cache
		$config = array(
			//$_SERVER["DOCUMENT_ROOT"]  ==> pega a pasta raiz do projeto no servidor
			"tpl_dir"       => $_SERVER["DOCUMENT_ROOT"] . $tpl_dir,
			"cache_dir"     => $_SERVER["DOCUMENT_ROOT"] . "views-cache/",
			"debug"         => true // set to false to improve the speed
		   );

		Tpl::configure( $config );

		$this -> tpl = new Tpl;

		$this -> setData($this -> options["data"]);

		if ($this->options["header"] === true) $this -> tpl -> draw("header");

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


		if ($this->options["footer"] === true) $this -> tpl -> draw("footer");

	}



}











?>