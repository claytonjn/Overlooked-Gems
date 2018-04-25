<?php

	ini_set ("display_errors", "1");
	error_reporting(E_ALL);
	
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
		
	//GET THIS INFORMATION FROM SENDING PAGE USING POST OR GET DATA
	$pnumber = '11000024032';
	$pickup_location = 'west';
	

	if(isset($_POST['step']))
		$step = $_POST['step'];
	else
		$step = 1;
	
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
	
	if($step==1) {
		
		echo <<< Text
			
			<div id="header">
				Sign-up Form
			</div>
			
			<div id="FormInfo">
				<span style="color:#C00;">{$error}</span>
				<form action="" method="POST" name="SIGNUP">
					<input type="hidden" value="2" name="step"
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
	else if($step==2) {
		
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
			
			//Set the Pnumber from the PG Query
			$pnumber = $row['record_num'];

			//Set Session Variables
			$_SESSION['PNumber']=$pnumber;
			
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
						To do this, please login to "My Account", select Reading History and "Opt In".		
NoReadingHistory;
				}
				elseif($rhRow['rhbool']=='t') {
					echo <<< Frequency
						<div id="FormInfo">
							<form action="" method="POST" name="FrequencyForm">
								<input type="hidden" value="3" name="step"
								<div id="Select">
									Pick your hold Frequency:
									<select name="frequency">
										<option value='weekly'>Weekly</option>
										<option value='biweekly'>Bi-weekly</option>
										<option value='monthly' SELECTED>Monthly</option>
										<option value='bimonthly'>Bi-monthly</option>
										<option value='quarterly'>Quarterly</option>
										<option value='biannually'>Bi-annually</option>
										<option value='annually'>Annually</option>
									</select>
								</div>
								<div id="Select">
									Select your pick up location:
									<select name="pickup_location">
										<option value='drive'>Main Drive-up</option>
										<option value='west' SELECTED>Main Lobby</option>
										<option value='wacr'>Westacres</option>
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
	else if($step==3) {
		
		if(isset($_POST['frequency']))
			$frequency = $_POST['frequency'];
		else
			$frequency = "monthly";
		
		if(isset($_POST['pickup_location']))
			$pickup_location = $_POST['pickup_location'];
		else
			$pickup_location = "west";
		
		date_default_timezone_set('America/Detroit');
		$next_trigger=date("Y-m-d",strtotime("tomorrow"));
		
		
		$query = "	INSERT INTO 2018_patrons (pnumber, pickup_location, frequency, next_trigger)
					 VALUES ('{$_SESSION['PNumber']}', '{$pickup_location}', '{$frequency}', '{$next_trigger}')
					 ON DUPLICATE KEY UPDATE
					 pickup_location	= '{$pickup_location}',
					 frequency 			= '{$frequency}',
					 next_trigger		= '{$next_trigger}';";
		
		$result = mysqli_query($overlookedGemsLink, $query) or die(mysqli_error($overlookedGemsLink));
		
		echo $next_trigger;
	}
	
	
?>