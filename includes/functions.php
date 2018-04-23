<?php

	function zolaSignature() {
		include "constants.php";

		$timestamp = gmdate('U'); // 1200603038
		$signature = md5($zolaKey . $zolaSecret . $timestamp);
		return $signature;
	}

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
		//Create temp table with unpagable holds filtered out at the bib level
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
								OR	brp.material_code = '$'	/*	Rental (25)			*/	)";

		$sierraResult = pg_query($sierraDNAconn, $query) or die('Query failed: ' . pg_last_error());

		$readISBNS = "";
		while($row=pg_fetch_assoc($sierraResult)) {
			$readISBNS .= cleanFromSierra("ident", $row['ident']).",";
		}
		return rtrim($readISBNS,",");
	}

?>
