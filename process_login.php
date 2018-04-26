<?php

	//Show Errors
	error_reporting(E_ALL);
	ini_set('display_errors', '1');

	//Keep login Information
	session_start();

	//**This include file establishes the database connections
	include_once ("./includes/db_connect.inc");

	//**This include file includes functions, such as pipe cleaner
	include_once ("./includes/functions.php");

	//Connect to SierraDNA
	$sierraDNAconn = db_sierradna();
	
	//Connect to MySQL
	$overlookedGemsLink = db_overlooked_gems() or die ("Cannot connect to server");
		
	//Check Session Variables
	if(isset($_POST['Logout']))
	{
		$_SESSION['LoggedIn']	= 0;
		$_SESSION['pnumber']	= "";
		$_SESSION['pid'] 		= "";
	}

	//Check Session Variables
	if(!isset($_SESSION['LoggedIn']))
		$_SESSION['LoggedIn']=0;
	
	//Checking for Errors with Posting of Registration Data
	if (isset($_POST['lastname']))
		$lastname = strtolower($_POST['lastname']);
	else
		$lastname = "";

	if (isset($_POST['cardno']))
		$cardno = $_POST['cardno'];
	else
		$cardno = "";

	//Connect to SierraDNA
	$sierraDNAconn = db_sierradna();

	$patronQuery = "SELECT 		PRF.last_name, PV.record_num, PV.id
					FROM 		sierra_view.varfield AS V
					LEFT JOIN	sierra_view.patron_view AS PV ON PV.id = V.record_id
					LEFT JOIN	sierra_view.patron_record_fullname AS PRF ON PRF.patron_record_id = V.record_id
					WHERE 		V.field_content = '$cardno';";

	$sierraPatronResult = pg_query($sierraDNAconn, $patronQuery) or die('Query failed: ' . pg_last_error());
	
	$resultCount =  pg_num_rows($sierraPatronResult);
	
	if($resultCount == 0) 
		$error = 1;
	elseif($resultCount == 1) {		
	
		//BARCODE EXISTS IN SYSTEM IN SINGLE ENTRY

		//Get the Single Row of Information
		$row = pg_fetch_assoc($sierraPatronResult);
		
		//Grab the lastname from the PG Query
		$pgLastname = strtolower(str_replace( "*", "", $row['last_name'] ));

		//Compare names to see if login is valid
		if($pgLastname != $lastname)				
		{
			//NOT FOUND
			$error = 1;
			header("Location: index.php?error=$error&lastname=$lastname&cardno=$cardno");
			exit;
		}
		else if($pgLastname == $lastname) {

			//MATCH FOUND
			$pnumber = $row['record_num'];
			$pid = $row['id'];
			
			$patronRHQuery = "	SELECT	is_reading_history_opt_in AS rhbool
								FROM	sierra_view.patron_record AS PR
								WHERE	PR.record_id = '{$pid}';";
				 
			$sierraPatronRHResult = pg_query($sierraDNAconn, $patronRHQuery) or die('Query failed: ' . pg_last_error());
			
			$rhRow = pg_fetch_assoc($sierraPatronRHResult);
			
			if($rhRow['rhbool']=='f') {
				$error = 2;
				header("Location: index.php?error=$error&lastname=$lastname&cardno=$cardno");
				exit;
			}	
			
			date_default_timezone_set('America/Detroit');
			$next_trigger=date("Y-m-d",strtotime("tomorrow"));
			
			//Insert the patron into the MySQL table if they don't already exist
			$query = "  INSERT IGNORE INTO 2018_patrons (pnumber, next_trigger)
						VALUES ('{$pnumber}', '{$next_trigger}');";
			
			$result = mysqli_query($overlookedGemsLink, $query) or die(mysqli_error($overlookedGemsLink));			
			
			//Set Session Variables
			$_SESSION['LoggedIn']	= 1;
			$_SESSION['pnumber']	= $pnumber;
			$_SESSION['pid'] 		= $pid;
			
			header("Location: myaccount.php");
			exit;
		}
	}
	
	header("Location: index.php?error=$error&lastname=$lastname&cardno=$cardno");
	exit;


?>