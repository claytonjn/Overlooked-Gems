<?php

	ini_set ("display_errors", "1");
	error_reporting(E_ALL);
	
	//Keep login Information
	session_start();

	//**This include file establishes the database connections
	include_once ("./includes/db_connect.inc");

	//**This include file includes functions, such as pipe cleaner
	include_once ("./includes/functions.php");

	//**This include file includes header
	include_once ("./includes/header.php");

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
	
	//Checking for Errors
	if (isset($_GET['lastname']))
		$lastname = strtolower($_GET['lastname']);
	else
		$lastname = "";

	if (isset($_GET['cardno']))
		$cardno = $_GET['cardno'];
	else
		$cardno = "";

	if(isset($_GET['error']))
		$error = $_GET['error'];
	else {
		$error = 0;
		$errorDisplay = "";
	}
		
	
	if($error==1) {
		$errorDisplay="Sorry, you were not found in the system.<br>";
	}
	elseif($error==2) {
		$errorDisplay="In order to use this service, you must have your reading history turned on.<br>To do this, please login to \"My Account\", select Reading History and \"Opt In\".<br>";
	}
	
	echo <<< Text
		
		<div id="headertext">
			Sign-up Form
		</div>
		
		<div id="forminfo">
			<span style="color:#C00;">{$errorDisplay}</span>
			<form action="./process_login.php" method="POST" name="SIGNUP">
				<div id="textbox">
					Last Name:
					<input type="text" name="lastname" value="{$lastname}">
				</div>
				<div id="textbox">
					Barcode:
					<input type="password" name="cardno" value="{$cardno}">
				</div>
				<div id="textbox">
					<input type="submit" name="submit">
				</div>
			</form>
		</div>
		
		
Text;
	
?>