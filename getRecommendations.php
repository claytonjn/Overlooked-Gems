<?php

	ini_set ("display_errors", "1");
	error_reporting(E_ALL);

	//**This include file establishes the database connections
	include_once ("./includes/db_connect.inc");

	//**This include file includes functions, such as pipe cleaner
	include_once ("./includes/functions.php");

	//Connect to SierraDNA
	$sierraDNAconn = db_sierradna();

    $zolaSignature = zolaSignature();
	$readISBN = pullReadingHistory('1076015', $sierraDNAconn);

    echo zolaRecommendations($zolaSignature, $readISBN);

?>