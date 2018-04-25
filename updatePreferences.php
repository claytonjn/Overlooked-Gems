<?php

    //**This include file establishes the database connections
    include_once ("./includes/db_connect.inc");

    //**This include file includes functions, such as check digit generator
	include_once ("./includes/functions.php");

    //Connect to MySQL
	$overlookedGemsLink = db_overlooked_gems() or die ("Cannot connect to server");

	//Get Next Trigger Date
	date_default_timezone_set('America/Detroit');
	$next_trigger="'".date("Y-m-d",strtotime("tomorrow"))."'";

	if(isset($_GET['frequency']))
		$frequency = $_GET['frequency'];
		
	if(isset($_GET['patron_num']))
		$patron_num = $_GET['patron_num'];
	
	if(isset($_GET['pickup_location']))
		$pickup_location = $_GET['pickup_location'];
	
	if(isset($_GET['preference']))
		$preference = $_GET['preference'];
	
	if($preference == 'pickup_location')
		$next_trigger = "next_trigger";


	//Update Query
	$query = "	INSERT INTO 2018_patrons (pnumber, pickup_location, frequency, next_trigger)
				 VALUES ({$patron_num}, '{$pickup_location}', '{$frequency}', {$next_trigger})
				 ON DUPLICATE KEY UPDATE
				 pickup_location	= '{$pickup_location}',
				 frequency 			= '{$frequency}',
				 next_trigger		= {$next_trigger};";
		
	$result = mysqli_query($overlookedGemsLink, $query) or die(mysqli_error($overlookedGemsLink));

  

?>