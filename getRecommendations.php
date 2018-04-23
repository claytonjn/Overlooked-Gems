<?php

	ini_set ("display_errors", "1");
	error_reporting(E_ALL);

	//**This include file establishes the database connections
	include_once ("./includes/db_connect.inc");

	//**This include file includes functions, such as pipe cleaner
	include_once ("./includes/functions.php");

	//Connect to SierraDNA
	$sierraDNAconn = db_sierradna();

	$pnumber = '1051149';

    $zolaSignature = zolaSignature();
	$readISBNS = pullReadingHistory($pnumber, $sierraDNAconn);
	shuffle($readISBNS);

	foreach($readISBNS as $isbn) {
		$zolaRecommendations = zolaRecommendations($zolaSignature, $isbn, 100, NULL, "BB,BC,BH,WW,BK,AC", "TRUE");
		$recommendations = json_decode($zolaRecommendations, true);
		if($recommendations['status'] == "success") {
			$recommendations = $recommendations['data']['list'];

			$recommendedISBNS = array();
		    foreach($recommendations as $recommendation) {
		        foreach($recommendation['versions'] as $versions) {
					if(in_array($versions['raw_form'], ["BB", "BC", "BH", "WW", "BK", "AC"])) {
						array_push($recommendedISBNS, $versions['isbn']);
					}
		        }
		    }

			shuffle($recommendedISBNS);
			$sierraResult = checkSierraForHit($recommendedISBNS, $pnumber, $sierraDNAconn);

			if(pg_num_rows($sierraResult) > 0) {
				while ($row = pg_fetch_assoc($sierraResult)) {
					echo $row['record_id']."<br>";
				}
				exit; //got a result, don't keep trying
			}
		}
	}

?>