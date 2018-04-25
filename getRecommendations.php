<?php

	ini_set ("display_errors", "1");
	error_reporting(E_ALL);

	//Database connections
	include_once ("./includes/db_connect.inc");

	//General functions (data cleanup, API calls, etc)
	include_once ("./includes/functions.php");

	//Connect to SierraDNA
	$sierraDNAconn = db_sierradna();

	//Generature signature for Zola API
	$zolaSignature = zolaSignature();

	//Create temp-table with ISBNs for efficiency
	try {
		$tempTablesResult = prepareIdentTempTable($sierraDNAconn);
		if ($tempTablesResult === FALSE)
			throw new Exception('failed to create ident temp table');
	} catch(Exception $e) {
		echo $e;
	}

	//Connect to the overlooked_gems database
	$overlookedGemsLink = db_overlooked_gems() or die ("Cannot connect to server");

	$patronsQuery = "	SELECT	pnumber, pickup_location, frequency
						FROM	`2018_patrons`
						WHERE	next_trigger <= CURDATE()";

	$patronsResult = mysqli_query($overlookedGemsLink, $patronsQuery) or die(mysqli_error($overlookedGemsLink));

	//Set Time Limit for Execution Time
	set_time_limit(600+(mysqli_num_rows($patronsResult) * 6)); //Allow time for processing all items

	while($patron = mysqli_fetch_assoc($patronsResult)) {
		//Create temp-table with patron's reading history for efficiency
		try {
			$tempTablesResult = prepareReadingHistoryTempTable($patron['pnumber'], $sierraDNAconn);
			if ($tempTablesResult === FALSE)
				throw new Exception('failed to create reading history temp table');
		} catch(Exception $e) {
			echo $e;
		}

		//Update reading history in MySQL (to ensure we have current data)
		try {
			$importResult = importReadingHistory($patron['pnumber'], $sierraDNAconn, $overlookedGemsLink);
			if ($importResult === FALSE)
				throw new Exception('failed to import reading history');
		} catch(Exception $e) {
			echo $e;
		}

		$prioritizedISBNS = pullReadingHistory($patron['pnumber'], $overlookedGemsLink, $sierraDNAconn);

		while($isbn=pg_fetch_assoc($prioritizedISBNS)) {
			$zolaRecommendations = zolaRecommendations($zolaSignature, cleanFromSierra("ident", $isbn['ident']), 100, NULL, "BB,BC,BH,WW,BK,AC", "TRUE");
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

				$sierraResult = checkSierraForHit($recommendedISBNS, $sierraDNAconn);

				if(pg_num_rows($sierraResult) > 0) {
					$row = pg_fetch_assoc($sierraResult);
					//Token for Sierra API access (get new token for each user, otherwise it might timeout)
					$token = getAccessToken();

					//Place the hold on the item for the patron
					$data = array("recordType" => "b", "recordNumber" => intval($row['bib_num']), "pickupLocation" => $patron['pickup_location']);
					$data_string = json_encode($data);
					$holdResult = placeHold($token, $patron['pnumber'], $data_string);
					if($holdResult == "") {
						$updateDateQuery = "	UPDATE	`2018_patrons`
												SET 	next_trigger = CASE frequency
															WHEN	'weekly'		THEN	(CURRENT_DATE + INTERVAL '1' week)
												            WHEN	'biweekly'		THEN	(CURRENT_DATE + INTERVAL '2' week)
												            WHEN	'monthly'		THEN	(CURRENT_DATE + INTERVAL '1' month)
												            WHEN	'bimonthly'		THEN	(CURRENT_DATE + INTERVAL '2' month)
												            WHEN	'quarterly'		THEN	(CURRENT_DATE + INTERVAL '3' month)
												            WHEN	'biannually'	THEN	(CURRENT_DATE + INTERVAL '6' month)
												            WHEN	'annually'		THEN	(CURRENT_DATE + INTERVAL '1' year)
														END
												WHERE	pnumber = '{$patron['pnumber']}'";
						mysqli_query($overlookedGemsLink, $updateDateQuery) or die(mysqli_error($overlookedGemsLink));
						break; //got a result, hold sucessfully placed, don't keep trying
					}
				}
			}
		}
	}

	pg_close($sierraDNAconn);
	mysqli_close($overlookedGemsLink);
	unset($overlookedGemsLink);

?>