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

	function pullReadingHistory($pnumber, $sierraDNAconn) {
		//Pull books from patron's reading history
		$query = "	SELECT		(	SELECT 		DISTINCT ON (v.record_id) v.field_content
									FROM 		sierra_view.varfield v
									WHERE 		v.record_id = RH.bib_record_metadata_id
									AND			v.marc_tag = '020'
									ORDER BY	v.record_id, v.marc_tag DESC, v.occ_num ASC	) AS ident
					FROM		sierra_view.reading_history AS RH
					LEFT JOIN	sierra_view.patron_view ON rh.patron_record_metadata_id = patron_view.id
					LEFT JOIN	sierra_view.bib_record_property AS BRP ON RH.bib_record_metadata_id = BRP.bib_record_id
					WHERE		patron_view.record_num = '{$pnumber}'
					AND			(	brp.material_code = 'b'	/*	Book on CD (5)		*/
								OR	brp.material_code = 'a'	/*	Book (7)			*/
								OR	brp.material_code = 'l'	/*	Large Print (10)	*/
								OR	brp.material_code = 'k' /*	eAudio (13)			*/
								OR	brp.material_code = 'i'	/*	Book on Tape (15)	*/
								OR	brp.material_code = 'p'	/*	Book on MP3 (23)	*/
								OR	brp.material_code = 'z'	/*	eBook (24)			*/
								OR	brp.material_code = '$'	/*	Rental (25)			*/	);";

		$sierraResult = pg_query($sierraDNAconn, $query) or die('Query failed: ' . pg_last_error());

		$readISBNS = [];
		while($row=pg_fetch_assoc($sierraResult)) {
			array_push($readISBNS, cleanFromSierra("ident", $row['ident']));
		}
		return $readISBNS;
	}

	function checkSierraForHit($recommendedISBNS, $pnumber, $sierraDNAconn) {

		$query =	"	DROP TABLE IF EXISTS varfields;";
		$query .=	"	CREATE TEMP TABLE varfields
						(
							record_id bigint,
							field_content varchar(20001)
						);";
		$query .=	"	INSERT INTO	varfields
						SELECT 		record_id, field_content
						FROM		sierra_view.varfield
						WHERE		marc_tag = '020'
						AND			varfield_type_code = 'i';";
		$query .=	"	DROP TABLE IF EXISTS bibRecords;";
		$query .=	"	DROP TABLE IF EXISTS reading_histories;";
		$query .=	"	CREATE TEMP TABLE reading_histories
						(
							bib_record_metadata_id bigint
						);";
		$query .=	"	INSERT INTO reading_histories
						SELECT		bib_record_metadata_id
						FROM		sierra_view.reading_history rh
						LEFT JOIN	sierra_view.patron_view pv
									ON rh.patron_record_metadata_id = pv.id
						WHERE		pv.record_num = '{$pnumber}';";
		$query .=	"	CREATE TEMP TABLE bibRecords
						(
							record_id bigint
						);";
		$query .=	"	INSERT INTO bibRecords
						SELECT		v.record_id
						FROM		varfields v
						LEFT JOIN	reading_histories rh
									ON v.record_id = rh.bib_record_metadata_id
						LEFT JOIN	sierra_view.bib_record_property brp
									ON v.record_id = brp.bib_record_id
						LEFT JOIN	sierra_view.bib_record_item_record_link brirl
									ON v.record_id = brirl.bib_record_id
						LEFT JOIN	sierra_view.item_view iv
									ON brirl.item_record_id = iv.id
						LEFT JOIN	sierra_view.checkout c
									ON brirl.item_record_id = c.item_record_id
						WHERE		rh.bib_record_metadata_id IS NULL
						AND			brp.material_code = 'a'	/*	Book (7)	*/
						AND			iv.item_status_code = '-'
						AND			c.item_record_id IS NULL
						AND			";
		$query .=	"(";
		foreach($recommendedISBNS as $isbn) {
			$query .= "v.field_content LIKE '%{$isbn}%' OR ";
		}
		$query = rtrim($query," OR ") . ")";
		$query .=	"	GROUP BY	v.record_id;";
		$query .= "	SELECT * FROM bibRecords";

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
