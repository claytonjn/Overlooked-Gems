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
	$readISBN = pullReadingHistory('1124032', $sierraDNAconn);

    $zolaRecommendations =  zolaRecommendations($zolaSignature, $readISBN, NULL, NULL, "BB,BC,BH,WW,BK,AC", "TRUE");
    $recommendations = json_decode($zolaRecommendations, true)['data']['list'];

    $recommendedISBNS = array();
    foreach ($recommendations as $recommendation) {
        foreach($recommendation['versions'] as $versions) {
            array_push($recommendedISBNS, $versions['isbn']);
        }
    }

    echo "<pre>";
    var_dump($recommendedISBNS);
    echo "</pre>";

?>