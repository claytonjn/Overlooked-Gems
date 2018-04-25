<?php

    if(!isset($_GET['pnumber'])) {
        echo "No patron information was sent - Reading History cannot be loaded!";
    } else {
        $pnumber = $_GET['pnumber'];
    }

    //**This include file establishes the database connections
    include_once ("./includes/db_connect.inc");

    //**This include file includes functions, such as check digit generator
	include_once ("./includes/functions.php");

    //Connect to the overlooked_gems database
	$overlookedGemsLink = db_overlooked_gems() or die ("Cannot connect to server");

    //Connect to SierraDNA
	$sierraDNAconn = db_sierradna();

    //Create temp-table with patron's reading history for efficiency
    try {
        $tempTablesResult = prepareReadingHistoryTempTable($pnumber, $sierraDNAconn);
        if ($tempTablesResult === FALSE)
            throw new Exception('failed to create reading history temp table');
    } catch(Exception $e) {
        echo $e;
    }

    //Update reading history in MySQL (to ensure we have current data)
    try {
        $importResult = importReadingHistory($pnumber, $sierraDNAconn, $overlookedGemsLink);
        if ($importResult === FALSE)
            throw new Exception('failed to import reading history');
    } catch(Exception $e) {
        echo $e;
    }

    $mysqlQuery = " SELECT  bib_record_metadata_id, rating
                    FROM    `2018_reading_history`
                    WHERE   pnumber = {$pnumber}";

    if(isset($_GET['filter'])) {
		if($_GET['filter'] != "") {
			$mysqlQuery .= "    AND rating = {$_GET['filter']}";
		}
    }

    $mysqlResult = mysqli_query($overlookedGemsLink, $mysqlQuery) or die(mysqli_error($overlookedGemsLink));

    if(mysqli_num_rows($mysqlResult) > 0) {
        //Create temp-table with ISBNs for efficiency
    	try {
    		$tempTablesResult = prepareIdentTempTable($sierraDNAconn);
    		if ($tempTablesResult === FALSE)
    			throw new Exception('failed to create ident temp table');
    	} catch(Exception $e) {
    		echo $e;
    	}

        $sierraQuery = "	DROP TABLE IF EXISTS prioritized_history;";
        $sierraQuery .= "	CREATE TEMP TABLE prioritized_history
                            (
                                bib_record_metadata_id bigint,
                                rating int
                            );";
        $sierraQuery .= "	INSERT INTO prioritized_history (bib_record_metadata_id, rating) VALUES ";
        while($row=mysqli_fetch_assoc($mysqlResult)) {
            $sierraQuery .= "({$row['bib_record_metadata_id']}, {$row['rating']}), ";
        }
        $sierraQuery = rtrim($sierraQuery,", ") . ";";
        $sierraQuery .= "   SELECT      bv.record_num,
                                		(	SELECT 		DISTINCT ON (v.record_id) v.field_content
                                			FROM 		varfields v
                                			WHERE 		v.record_id = ph.bib_record_metadata_id
                                			ORDER BY	v.record_id, v.occ_num ASC	) AS ident,
                                		CASE WHEN	vt.title != ''
                                			THEN	vt.title
                                			ELSE	CONCAT('|a', brp.best_title)
                                		END AS title,
                        		        brp.best_author AS author
                            FROM		prioritized_history ph
                            LEFT JOIN	sierra_view.bib_view bv
                            		    ON ph.bib_record_metadata_id = bv.id
                            LEFT JOIN	sierra_view.bib_record_property AS brp
                                        ON ph.bib_record_metadata_id = brp.bib_record_id
                            LEFT JOIN	(	SELECT 	    DISTINCT ON (record_id) record_id, field_content AS title
                                			FROM 		sierra_view.varfield
                                			WHERE 		marc_tag = '245'
                                			ORDER BY 	record_id, occ_num ASC	) vt
                            		    ON ph.bib_record_metadata_id = vt.record_id
                            LEFT JOIN   reading_histories rh
                                        ON ph.bib_record_metadata_id = rh.bib_record_metadata_id
                            ORDER BY    ";
        if(isset($_GET['sort'])) {
			if($_GET['sort']!= "") {
				$sierraQuery .= "ph.rating ";
				switch ($_GET['sort']) {
					case -1:
						$sierraQuery .= "ASC";
						break;
					case 1:
						$sierraQuery .= "DESC";
						break;
				}
				$sierraQuery .= ", ";
			}
        }
        $sierraQuery .= "rh.checkout_gmt DESC;";

        $sierraResult = pg_query($sierraDNAconn, $sierraQuery) or die('Query failed: ' . pg_last_error());

        pg_close($sierraDNAconn);

        $data = [];

        while ($row = pg_fetch_assoc($sierraResult)) {
            $row['ident'] = cleanFromSierra("ident", $row['ident']);
            $row['author'] = cleanFromSierra("author", $row['author']);
    		$row['title'] = cleanFromSierra("title", $row['title']);
            array_push($data, $row);
        }

        $json = json_encode($data);

        header('Content-Type: application/json');

        echo $json;
    } else {
        echo "No reading history found!";
    }

?>