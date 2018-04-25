<?php

	function cleanFromSierra($field, $string) {
		switch ($field) {
			case "author":
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

	function prepareIdentTempTable($sierraDNAconn) {

		$query =	"	DROP TABLE IF EXISTS varfields;";
		$query .=	"	CREATE TEMP TABLE varfields
						(
							record_id bigint,
							occ_num int,
							field_content varchar(20001)
						);";
		$query .=	"	INSERT INTO	varfields
						SELECT 		record_id, occ_num, SUBSTRING(field_content from 0 for 16) as field_content
						FROM		sierra_view.varfield
						WHERE		marc_tag = '020'
						AND			varfield_type_code = 'i';";

		return pg_query($sierraDNAconn, $query) or die('Query failed: ' . pg_last_error());

	}

	function prepareReadingHistoryTempTable($pnumber, $sierraDNAconn) {

		$query =	"	DROP TABLE IF EXISTS reading_histories;";
		$query .=	"	CREATE TEMP TABLE reading_histories
						(
							bib_record_metadata_id bigint,
							checkout_gmt timestamp
						);";
		$query .=	"	INSERT INTO reading_histories
						SELECT		bib_record_metadata_id, checkout_gmt
						FROM		sierra_view.reading_history rh
						LEFT JOIN	sierra_view.patron_view pv
									ON rh.patron_record_metadata_id = pv.id
						WHERE		pv.record_num = '{$pnumber}';";

		return pg_query($sierraDNAconn, $query) or die('Query failed: ' . pg_last_error());

	}

	function importReadingHistory($pnumber, $sierraDNAconn, $overlookedGemsLink) {
		//Pull books from patron's reading history into MySQL
		$sierraQuery = "	SELECT		bib_record_metadata_id
							FROM		reading_histories rh
							LEFT JOIN	sierra_view.bib_record_property brp
										ON rh.bib_record_metadata_id = brp.bib_record_id
							WHERE		(	brp.material_code = 'b'	/*	Book on CD (5)		*/
										OR	brp.material_code = 'a'	/*	Book (7)			*/
										OR	brp.material_code = 'l'	/*	Large Print (10)	*/
										OR	brp.material_code = 'k' /*	eAudio (13)			*/
										OR	brp.material_code = 'i'	/*	Book on Tape (15)	*/
										OR	brp.material_code = 'p'	/*	Book on MP3 (23)	*/
										OR	brp.material_code = 'z'	/*	eBook (24)			*/
										OR	brp.material_code = '$'	/*	Rental (25)			*/	);";

		$sierraResult = pg_query($sierraDNAconn, $sierraQuery) or die('Query failed: ' . pg_last_error());

		if(pg_num_rows($sierraResult) > 0) {
			$mysqlQuery = "	INSERT IGNORE INTO	`2018_reading_history` (`pnumber`, `bib_record_metadata_id`)
							VALUES				";
			while($row=pg_fetch_assoc($sierraResult)) {
				$mysqlQuery .= "({$pnumber}, {$row['bib_record_metadata_id']}), ";
			}
			$mysqlQuery = rtrim($mysqlQuery,", ") . ";";
			return mysqli_query($overlookedGemsLink, $mysqlQuery) or die(mysqli_error($overlookedGemsLink));
		} else {
			return false;
		}
	}

	function pullPrioritizedHistory($pnumber, $overlookedGemsLink, $sierraDNAconn) {
		//Pull records from patron's rated reading history
		$mysqlQuery = "	SELECT		bib_record_metadata_id
						FROM		`2018_reading_history`
						WHERE		pnumber = '{$pnumber}'
						ORDER BY	rating DESC, RAND()";
		$mysqlResult = mysqli_query($overlookedGemsLink, $mysqlQuery) or die(mysqli_error($overlookedGemsLink));

		if(mysqli_num_rows($mysqlResult) > 0) {
			//Pull ISBNs
			$sierraQuery = "	DROP TABLE IF EXISTS prioritized_history;";
			$sierraQuery .= "	CREATE TEMP TABLE prioritized_history
								(
									bib_record_metadata_id bigint
								);";
			$sierraQuery .= "	INSERT INTO prioritized_history (bib_record_metadata_id) VALUES ";
			while($row=mysqli_fetch_assoc($mysqlResult)) {
				$sierraQuery .= "({$row['bib_record_metadata_id']}), ";
			}
			$sierraQuery = rtrim($sierraQuery,", ") . ";";
			$sierraQuery .= "	SELECT		(	SELECT 		DISTINCT ON (v.record_id) v.field_content
												FROM 		varfields v
												WHERE 		v.record_id = ph.bib_record_metadata_id
												ORDER BY	v.record_id, v.occ_num ASC	) AS ident
								FROM		prioritized_history ph";
			$sierraResult = pg_query($sierraDNAconn, $sierraQuery) or die('Query failed: ' . pg_last_error());

			return $sierraResult;
		}
	}

	function checkSierraForHit($recommendedISBNS, $sierraDNAconn) {

		$query = "	DROP TABLE IF EXISTS recommendations;";
		$query .= "	CREATE TEMP TABLE recommendations
					(
						ident varchar(15)
					);";
		$query .= "	INSERT INTO recommendations (ident) VALUES ";
		foreach($recommendedISBNS as $isbn) {
			$query .= "('|a{$isbn}'), ";
		}
		$query = rtrim($query,", ") . ";";
		$query .=	"	SELECT		bv.record_num as bib_num
		 				FROM		varfields v
						INNER JOIN	recommendations r
									ON v.field_content = r.ident
						LEFT JOIN	reading_histories rh
									ON v.record_id = rh.bib_record_metadata_id
						LEFT JOIN	sierra_view.bib_record_property brp
									ON v.record_id = brp.bib_record_id
						LEFT JOIN	sierra_view.bib_view bv
									ON v.record_id = bv.id
						LEFT JOIN	sierra_view.bib_record_item_record_link brirl
									ON v.record_id = brirl.bib_record_id
						LEFT JOIN	sierra_view.item_view iv
									ON brirl.item_record_id = iv.id
						LEFT JOIN	sierra_view.checkout c
									ON brirl.item_record_id = c.item_record_id
						LEFT JOIN	sierra_view.hold h
									ON 	(	v.record_id = h.record_id
										OR	brirl.item_record_id = h.record_id	)
						WHERE		rh.bib_record_metadata_id IS NULL
						AND			brp.material_code = 'a'	/*	Book (7)	*/
						AND			iv.item_status_code = '-'
						AND			c.item_record_id IS NULL
						AND			h.record_id IS NULL
						LIMIT		1;";

		$sierraResult = pg_query($sierraDNAconn, $query) or die('Query failed: ' . pg_last_error());
		return $sierraResult;
	}

	function zolaSignature() {
		include "constants.php";

		$timestamp = gmdate('U'); // 1200603038
		$signature = md5($zolaKey . $zolaSecret . $timestamp);
		return $signature;
	}

	function zolaRecommendations($signature, $isbn, $limit = NULL, $offset = NULL, $preferred_format = NULL, $restrict_format = NULL) {
		include "constants.php";

		$url = "https://api.zo.la/v4/recommendation/rec?action=get&key={$zolaKey}&signature={$signature}&isbn={$isbn}&limit={$limit}&offset={$offset}&preferred_format={$preferred_format}&restrict_format={$restrict_format}";

		try {
		    $ch = curl_init();

		    if (FALSE === $ch)
		        throw new Exception('failed to initialize');

		    curl_setopt($ch, CURLOPT_URL, $url);
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //TODO: Verify SSL

		    $content = curl_exec($ch);

		    if (FALSE === $content)
		        throw new Exception(curl_error($ch), curl_errno($ch));

		    // ...process $content now
		} catch(Exception $e) {

		    trigger_error(sprintf(
		        'Curl failed with error #%d: %s',
		        $e->getCode(), $e->getMessage()),
		        E_USER_ERROR);

		}

		// Close request to clear up some resources
		curl_close($ch);

		return $content;
	}

	function getAccessToken() {
		include "constants.php";

		// Get cURL resource
		$curl = curl_init();
		curl_setopt_array($curl, array(
				CURLOPT_POST => TRUE,
				CURLOPT_RETURNTRANSFER => 1,
				CURLOPT_SSL_VERIFYHOST => 0,
				CURLOPT_SSL_VERIFYPEER => 0,
				CURLOPT_URL => "{$apiurl}token",
				CURLOPT_HTTPHEADER => array(
						'Host: '.$hosturl,
						'Authorization: Basic '.$encauth,
						'Content-Type: application/x-www-form-urlencoded'
				),
				CURLOPT_POSTFIELDS => "grant_type=client_credentials"
		));

				// Send the request & save response to $resp
		$resp = curl_exec($curl);

		//Check if CURL REQUEST FAILED TO PROCESS
		$err = NULL;
		if($resp === FALSE)
			$err = curl_error($curl);

		// Close request to clear up some resources
		curl_close($curl);

		if($err)
			return $err;
		else {
			$tokenData = json_decode($resp, true);
			if(is_null($tokenData)) {
				echo "Could not retrieve token from server.<br>";
				return false;
			}

			return $tokenData["access_token"];	//Sends back Token
		}

	}

	function placeHold($token, $id, $body) {
		include "constants.php";

		// Get cURL resource
		$curl = curl_init();
		// Set some options - we are passing in a useragent too here
		curl_setopt_array($curl, array(
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_SSL_VERIFYHOST=> 0,
			CURLOPT_SSL_VERIFYPEER=> 0,
			CURLOPT_URL => "{$apiurl}patrons/{$id}/holds/requests",
			CURLOPT_HTTPHEADER => array(
					'Host: '.$hosturl,
					'Authorization: Bearer '.$token,
					'Content-Type: application/json',
					'Content-Length: ' . strlen($body)
			),
			CURLOPT_POSTFIELDS => $body
		));
		// Send the request & save response to $resp
		$resp = curl_exec($curl);

		//Check if CURL REQUEST FAILED TO PROCESS
		$err = NULL;
		if($resp === FALSE)
			$err = curl_error($curl);

		// Close request to clear up some resources
		curl_close($curl);

		if($err)
			return $err;
		else
			return $resp;

	}


?>
