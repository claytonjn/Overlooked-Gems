<?php

	ini_set ("display_errors", "1");
	error_reporting(E_ALL);

	//**This include file establishes the database connections
	include_once ("./includes/db_connect.inc");

	//**This include file includes functions, such as pipe cleaner
	include_once ("./includes/functions.php");

	//Connect to SierraDNA
	$sierraDNAconn = db_sierradna();
	
	//GET THIS INFORMATION FROM SENDING PAGE USING POST OR GET DATA
	$pnumber = '1051149';
	$pickupLocation = 'west';
	

	if(isset($_POST['loginBox']))
		$loginBox = $_POST['loginBox'];
	else
		$loginBox = 1;
	
	if(isset($_POST['lastname']))
		$lastname = $_POST['lastname'];
	elseif(isset($_GET['lastname']))
		$lastname = $_GET['lastname'];
	else
		$lastname = "";
	
	if(isset($_POST['barcode']))
		$barcode = $_POST['barcode'];
	elseif(isset($_GET['barcode']))
		$barcode = $_GET['barcode'];
	else
		$barcode = "";
	
	if(isset($_GET['error']))
		$error = "Sorry, you were not found in the system.<br>";
	else
		$error = "";
	
	if($loginBox==1) {
		
		echo <<< Text
			
			<div id="header">
				Sign-up Form
			</div>
			
			<div id="FormInfo">
				<span style="color:#C00;">{$error}</span>
				<form action="" method="POST" name="SIGNUP">
					<input type="hidden" value="0" name="loginBox"
					<div id="textbox">
						Last Name:
						<input type="text" name="lastname" value="{$lastname}">
					</div>
					<div id="textbox">
						Barcode:
						<input type="password" name="barcode" value="{$barcode}">
					</div>
					<div id="textbox">
						<input type="submit" name="submit">
					</div>
				</form>
			</div>
			
		
Text;
	}
	else if($loginBox==0) {
		
		$patronQuery = "SELECT 		PRF.last_name, PV.record_num, PV.id
						FROM 		sierra_view.varfield AS V
						LEFT JOIN	sierra_view.patron_view AS PV ON PV.id = V.record_id
						LEFT JOIN	sierra_view.patron_record_fullname AS PRF ON PRF.patron_record_id = V.record_id
						WHERE 		V.field_content = '$barcode';";

		$sierraPatronResult = pg_query($sierraDNAconn, $patronQuery) or die('Query failed: ' . pg_last_error());
		
		$resultCount =  pg_num_rows($sierraPatronResult);
		
		if($resultCount == 0) {
			$error = 1;
			header("Location index.php?error=$error");
			die;
		}
		elseif($resultCount == 1) {	

			//Get the Single Row of Information
			$row = pg_fetch_assoc($sierraPatronResult);
			
			//Grab the lastname from the PG Query
			$pgLastname = strtolower(str_replace( "*", "", $row['last_name'] ));

			//Compare names to see if login is valid
			if($pgLastname != $lastname)				
			{
				//NOT FOUND
				$error = 1;
				header("Location: index.php?error=$error&lastname=$lastname&barcode=$barcode");
				exit;
			}
			else if($pgLastname == $lastname) {		

				$patronRHQuery = "	SELECT 		is_reading_history_opt_in AS rhbool
									FROM 		sierra_view.patron_record AS PR
									WHERE 		PR.record_id = '{$row['id']}';";

				$sierraPatronRHResult = pg_query($sierraDNAconn, $patronRHQuery) or die('Query failed: ' . pg_last_error());
				
				$rhRow = pg_fetch_assoc($sierraPatronRHResult);
				
				if($rhRow['rhbool']=='f') {
					echo <<< NoReadingHistory
						In order to use this service, you must have your reading history turned on.<br>
						To do this, please login to "My Account", select Reading History and "Opt In".<br>
						You can continue setting up your account, but after 3 attempts with Reading History<br>
						turned off, your account will be removed and you will need to set it up again.				
NoReadingHistory;
				}
				elseif($rhRow['rhbool']=='t') {
					echo <<< Frequency
						<div id="FormInfo">
							<form action="" method="POST" name="FrequencyForm">
								<input type="hidden" value="0" name="loginBox"
								<div id="Select">
									Pick your hold Frequency:
									<select name="frequency">
										<option value=''></option>
										<option value=''>Weekly</option>
										<option value=''>Bi-weekly</option>
										<option value=''>Monthly</option>
										<option value=''>Bi-monthly</option>
										<option value=''>Quarterly</option>
										<option value=''>Bi-annually</option>
										<option value=''>Annually</option>
									</select>
								</div>
								
								<div id="textbox">
									<input type="submit" name="submit">
								</div>
							</form>
						</div>
Frequency;
				}
					
				
				
			}
		}
	}
	
	
?>