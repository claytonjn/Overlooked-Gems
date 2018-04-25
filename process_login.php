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
		$_SESSION['LoggedIn']=0;
		$_SESSION['FirstName']="";
		$_SESSION['ParentID']="";
		
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

	$patronQuery = "SELECT 		PRF.last_name, PV.record_num
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
echo "HERE";
die;
			//Set Session Variables
			$_SESSION['LoggedIn']=1;
			
			$PNumber = $row['record_num'];
			
			$accountQuery = "	SELECT 	*
								FROM 	blt_parent
								WHERE 	Pnumber = '$PNumber';";

			$accountResult = mysqli_query($webConnect, $accountQuery) or die('Query failed: ' . mysqli_error());
			
			$rowCount =  mysqli_num_rows($accountResult);
			
			if($rowCount == 0) {
				
				//Patron doesn't exist yet
				$insertQuery = "	INSERT INTO blt_parent (PNumber, Entry_Date)
									VALUES ('$PNumber', NOW());";

				$insertResult = mysqli_query($webConnect, $insertQuery) or die('Query failed: ' . mysqli_error());
				
			}
				
			//Patron does exist
			$firstnameQuery = "	SELECT 		PRF.first_name
								FROM 		sierra_view.patron_view AS PV
								LEFT JOIN	sierra_view.patron_record_fullname AS PRF ON PRF.patron_record_id = PV.id
								WHERE 		PV.record_num = '{$PNumber}';";

			$firstnameResult = pg_query($sierraDNAconn, $firstnameQuery) or die('Query failed: ' . pg_last_error());
			
			$firstnameRow = pg_fetch_array($firstnameResult);
			
			$_SESSION['FirstName'] = $firstnameRow['first_name'];

			
			//Re-query the MySQL table to get the patron ID in our system
			$accountQuery = "	SELECT 	*
								FROM 	blt_parent
								WHERE 	Pnumber = '$PNumber';";

			$accountResult = mysqli_query($webConnect, $accountQuery) or die('Query failed: ' . mysqli_error());
			
			$infoRow = mysqli_fetch_array($accountResult);

			$_SESSION['ParentID'] = $infoRow['ParentID'];
			
			header("Location: child_selection.php");
			exit;
		}
	}
	
	header("Location: index.php?error=$error&lastname=$lastname&cardno=$cardno");
	exit;


?>