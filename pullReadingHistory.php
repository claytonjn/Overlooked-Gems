<?php

	ini_set ("display_errors", "1");
	error_reporting(E_ALL);

	//**This include file establishes the database connections
	include_once ("./includes/db_connect.inc");

	//**This include file includes functions, such as pipe cleaner
	include_once ("./includes/functions.php");

	//Connect to SierraDNA
	$sierraDNAconn = db_sierradna();
	
	
		//Create temp table with unpagable holds filtered out at the bib level
	$query = "	SELECT		(SELECT 	DISTINCT ON (v.record_id) v.field_content
							FROM 		sierra_view.varfield v
							WHERE 		v.record_id = RH.bib_record_metadata_id
							AND		v.marc_tag = '020'
							ORDER BY 	v.record_id, v.marc_tag DESC, v.occ_num ASC) AS ident
				FROM		sierra_view.reading_history AS RH
				LEFT JOIN	sierra_view.patron_view ON rh.patron_record_metadata_id = patron_view.id
				LEFT JOIN	sierra_view.bib_record_property AS BRP ON RH.bib_record_metadata_id = BRP.bib_record_id
				WHERE		patron_view.record_num = '1124032'
				AND		(	   brp.material_code = 'b'	/*	Book on CD (5)		*/
							OR brp.material_code = 'a'	/*	Book (7)		*/
							OR brp.material_code = 'l'	/*	Large Print (10)	*/
							OR brp.material_code = 'k' 	/*	eAudio (13)		*/
							OR brp.material_code = 'i'	/*	Book on Tape (15)	*/
							OR brp.material_code = 'p'	/*	Book on MP3 (23)	*/
							OR brp.material_code = 'z'	/*	eBook (24)		*/
							OR brp.material_code = '$'	/*	Rental (25)		*/ )";
							
	$sierraResult = pg_query($sierraDNAconn, $query) or die('Query failed: ' . pg_last_error());
							
							
	while($row=pg_fetch_assoc($sierraResult))
	{
		$isbnString=cleanFromSierra("ident", $row['ident']).",";
	}