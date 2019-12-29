<?php


	function formatPrice(float $vlprice){

		//O separador da casa decimal será a vírgual ","
		//O separador da casa de milhar será o ponto "."
		return number_format($vlprice, 2, ",", ".");

	}
	
?>