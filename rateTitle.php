<?php

	ini_set ("display_errors", "1");
	error_reporting(E_ALL);

    if(!isset($_GET['pnumber'])) {
        echo "No patron information was sent - title can't be rated!";
    } else {
        $pnumber = $_GET['pnumber'];
    }
    if(!isset($_GET['bib_record_metadata_id'])) {
        echo "No title information was sent - title can't be rated!";
    } else {
        $bib_record_metadata_id = $_GET['bib_record_metadata_id'];
    }
    if(!isset($_GET['rating'])) {
        echo "No rating information was sent - title can't be rated!";
    } else {
        $rating = $_GET['rating'];
    }

	//Database connections
	include_once ("./includes/db_connect.inc");

    //Connect to the overlooked_gems database
	$overlookedGemsLink = db_overlooked_gems() or die ("Cannot connect to server");

    $rateQuery = "  UPDATE  `2018_reading_history`
                    SET     rating = '{$rating}'
                    WHERE   pnumber = {$pnumber}
                    AND     bib_record_metadata_id = {$bib_record_metadata_id};";

    $rateResult = mysqli_query($overlookedGemsLink, $rateQuery) or die(mysqli_error($overlookedGemsLink));

	if($rateResult === TRUE) {
		echo $rating;
	} else {
		echo "RATING UNSUCESSFUL";
	}

	mysqli_close($overlookedGemsLink);
	unset($overlookedGemsLink);

?>