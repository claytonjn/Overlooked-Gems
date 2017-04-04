<?php

    //**This include file establishes the database connections
    include_once ("./includes/db_connect.inc");

    //**This include file includes functions, such as check digit generator
	include_once ("./includes/functions.php");

    //Connect to SierraDNA
	$sierraDNAconn = db_sierradna();

    //mat_type

    //Initialize variables
    $limit = "100";
    $offset = "0";
    $extraJoins = "";
    $extraWheres = "";

    if(isset($_GET['limit'])) {
        $limit = $_GET['limit'];
    }
    if(isset($_GET['offset'])) {
        $offset = $_GET['offset'];
    }

    if(isset(`$_GET['format']`)) {
        $format = $_GET['format'];
        if(strcasecmp($format, 'Book') == 0) {
            $extraWheres .= "   AND (iv.location_code = 'bfic'
                                OR  iv.location_code = 'bmyst'
                                OR  iv.location_code = 'bnba'
                                OR  iv.location_code = 'broma'
                                OR  iv.location_code = 'bscfi'
                                OR  iv.location_code = 'mfic'
                                OR  iv.location_code = 'mmyst'
                                OR  iv.location_code = 'mnba'
                                OR  iv.location_code = 'mscfi')";
        } elseif(strcasecmp($format, 'BookCD') == 0) {
            $extraWheres .= "   AND (iv.location_code = 'bcdb'
                                OR  iv.location_code = 'mcdb')";
        } elseif(strcasecmp($format, 'CD') == 0) {
            $extraWheres .= "   AND (iv.location_code = 'bcd'
                                OR  iv.location_code = 'mcd')";
        } elseif(strcasecmp($format, 'DVD') == 0) {
            $extraWheres .= "   AND (iv.location_code = 'bdvd'
                                OR  iv.location_code = 'bndvd'
                                OR  iv.location_code = 'mdvd'
                                OR  iv.location_code = 'mndvd')";
        } elseif(strcasecmp($format, 'Magazine') == 0) {
            $extraWheres .= "   AND (iv.location_code = 'bper'
                                OR  iv.location_code = 'mper')";
        }
    }

    if(isset($_GET['available'])) {
        if($_GET['available'] == 'y') {
            $extraJoins .= "    LEFT JOIN   sierra_view.checkout c
                                ON          c.item_record_id = brirl.item_record_id";
            $extraWheres .= "   AND c.item_record_id IS NULL
                                AND iv.item_status_code = '-'
				                AND iv.record_creation_date_gmt < (CURRENT_TIMESTAMP - INTERVAL '1 DAY')";
        }
    }
    if(isset($_GET['patron_record_num'])) {
        $extraJoins .= "    LEFT JOIN	(SELECT		reading_history.bib_record_metadata_id
                                		FROM		sierra_view.reading_history
                                		LEFT JOIN	sierra_view.patron_view
                                		ON 		    reading_history.patron_record_metadata_id = patron_view.id
                                		WHERE		patron_view.record_num = '{$_GET['patron_record_num']}') AS rh
                            ON		    bv.id = rh.bib_record_metadata_id";
        $extraWheres .= "    AND		    rh.bib_record_metadata_id IS NULL";
    }

    //Pull things
    $pullQuery = "  DROP TABLE IF EXISTS things;";
    $pullQuery .= " CREATE TEMP TABLE things
                    (
                    	bib_record_id bigint,
                    	bcode2 varchar(3),
                    	bib_record_num int,
                    	best_author varchar(1000),
                    	best_title varchar(1000)
                    );";
    $pullQuery .= " INSERT INTO	things
                    SELECT		bv.id AS bib_record_id, bv.bcode2, bv.record_num AS bib_record_num, brp.best_author, brp.best_title
                    FROM		sierra_view.bib_view bv
                    LEFT JOIN	sierra_view.bib_record_property AS brp
                    ON		    bv.id = brp.bib_record_id
                    LEFT JOIN	sierra_view.bib_record_item_record_link brirl
                    ON		    bv.id = brirl.bib_record_id
                    LEFT JOIN	sierra_view.item_view iv
                    ON		    brirl.item_record_id = iv.id
                    {$extraJoins}
                    WHERE		bv.record_creation_date_gmt IS NOT NULL
                    AND		    iv.item_message_code != 'f'
                    {$extraWheres}
                    GROUP BY	bv.id, bv.bcode2, bv.record_num, brp.best_author, brp.best_title, bv.record_creation_date_gmt
                    ORDER BY	bv.record_creation_date_gmt DESC
                    LIMIT		{$limit} OFFSET {$offset};";
    $pullQuery .= " DROP TABLE IF EXISTS varfields;";
    $pullQuery .= " CREATE TEMP TABLE varfields
                    (
                    	record_id bigint,
                    	varfield_type_code char(1),
                    	marc_tag varchar(3),
                    	occ_num int,
                    	field_content varchar(20001)
                    );";
    $pullQuery .= " INSERT INTO varfields
                    SELECT		v.record_id, v.varfield_type_code, v.marc_tag, v.occ_num, v.field_content
                    FROM		sierra_view.varfield v
                    INNER JOIN	(SELECT	DISTINCT(bib_record_id)
                    		    FROM	things) AS t
            		ON          t.bib_record_id = v.record_id;";
    $pullQuery .= " SELECT		t.bib_record_num,
                        		CASE WHEN	t.bcode2 = 'b'		/*	Book on CD (5)			*/
                            				OR t.bcode2 = 'a'	/*	Book (7)		     	*/
                            				OR t.bcode2 = 'l'	/*	Large Print (10)		*/
                            				OR t.bcode2 = 'k' 	/*	eAudio (13)			    */
                            				OR t.bcode2 = 'i'	/*	Book on Tape (15)		*/
                            				OR t.bcode2 = 'p'	/*	Book on MP3 (23)		*/
                            				OR t.bcode2 = 'z'	/*	eBook (24)			    */
                            				OR t.bcode2 = 'm'	/*	Discovery Tablet		*/
                            				OR t.bcode2 = 'o'	/*	ReadAlong			    */
                            				OR t.bcode2 = '$'	/*	Rental (25)			    */
                        		THEN
                        				(SELECT 	DISTINCT ON (v.record_id) v.field_content
                        				FROM 		varfields v
                        				WHERE 		v.record_id = t.bib_record_id
                        				AND			v.marc_tag = '020'
                        				ORDER BY 	v.record_id, v.occ_num ASC)
                        		ELSE
                        				(SELECT 	DISTINCT ON (v.record_id) v.field_content
                        				FROM 		varfields v
                        				WHERE 		v.record_id = t.bib_record_id
                        				AND			v.marc_tag = '024'
                        				ORDER BY 	v.record_id, v.occ_num ASC)
                        		END AS ident,
                        		CASE WHEN	vt.title != ''
                        		THEN		vt.title
                        		ELSE		CONCAT('|a', t.best_title)
                        		END AS title, t.best_author AS author
                    FROM		things AS t
                    LEFT JOIN	(SELECT 	DISTINCT ON (record_id) record_id, field_content AS title
                        		FROM 		varfields
                        		WHERE 		marc_tag = '245'
                        		ORDER BY 	record_id, occ_num ASC) vt
                    ON          t.bib_record_id = vt.record_id;";

    $sierraResult = pg_query($sierraDNAconn, $pullQuery) or die('Query failed: ' . pg_last_error());

    $json = "{\"things\":{\"thing\":[";

    while ($row = pg_fetch_assoc($sierraResult)) {
        $row['bib_record_num'] = "b" . $row['bib_record_num'] . getCheckDigit($row['bib_record_num']);
        $row['ident'] = cleanFromSierra("ident", $row['ident']);
        $row['author'] = cleanFromSierra("author", $row['author']);
		$row['title'] = cleanFromSierra("title", $row['title']);
        $json .= "{\"bib_record_num\":\"{$row['bib_record_num']}\",\"ident\":\"{$row['ident']}\",\"title\":\"{$row['title']}\",\"author\":\"{$row['author']}\"},";
    }

    $json = substr($json, 0, -1);

    $json .= "]}}";

    echo $json;

    pg_close($sierraDNAconn);

?>