<?php

	function cleanFromSierra($field, $string) {
		switch ($field) {
			case "best_author":
				$pattern = "/([^\d,\.]*,?[^\d,\.]*)(?:,|\.)?.*/";
				$replacement = "$1";
				break;
			case "title":
				$pattern = "/\|a(((?!\/\||\||\/$).)*)\/?|\|b(((?!\/\||\||\/$).)*)\/?|\|c(((?!\/\||\||\/$).)*)\/?|\|f(((?!\/\||\||\/$).)*)\/?|\|g(((?!\/\||\||\/$).)*)\/?|\|h(((?!\/\||\||\/$).)*)\/?|\|k(((?!\/\||\||\/$).)*)\/?|\|n(((?!\/\||\||\/$).)*)\/?|\|p(((?!\/\||\||\/$).)*)\/?|\|s(((?!\/\||\||\/$).)*)\/?/";
				$replacement = "$1 $3 $11 $15 $17 $19";
				break;
			case "ident":
				$pattern = "/\|a(\w*).*|\|c(\w*).*|\|d(\w*).*|\|z(\w*).*/";
				$replacement = "$1";
				break;
			case "edition":
				$pattern = "/\|a(((?!\|).)*)|\|b(((?!\|).)*)/";
				$replacement = "$1 $3";
				break;
		}

		$string = preg_replace($pattern, $replacement, $string);
		$string = preg_replace('/\s+/S', " ", $string); //collapse multiple spaces
		$string = trim($string);
		return $string;
	}

?>
