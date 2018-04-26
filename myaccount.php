<?php
 
 //Keep login Information
	session_start();

	//Set local include path
	$include_path="..";
	
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
	
	if($_SESSION['LoggedIn']==1) {
		
		//Clear session variables and go to Index Page
		$pnumber = $_SESSION['pnumber'];
			
		$preferenceQuery = "	SELECT	pickup_location, frequency
								FROM	`2018_patrons`
								WHERE	pnumber = '{$pnumber}'";

		$preferenceResult = mysqli_query($overlookedGemsLink, $preferenceQuery) or die(mysqli_error($overlookedGemsLink));
	
		$preference = mysqli_fetch_assoc($preferenceResult);
		
		//Set Default Values
		$frequency = $preference['frequency'];
		$pickup_location = $preference['pickup_location'];
		
		switch($frequency) {
			case 'weekly':
				$frequency_text = 'Weekly';
				break;
			case 'biweekly':
				$frequency_text = 'Bi-Weekly';
				break;
			case 'monthly':
				$frequency_text = 'Monthly';
				break;
			case 'bimonthly':
				$frequency_text = 'Bi-Monthly';
				break;
			case 'quarterly':
				$frequency_text = 'Quarterly';
				break;
			case 'biannually':
				$frequency_text = 'Bi-Annually';
				break;
			case 'annually':
				$frequency_text = 'Annually';
				break;
		}
				 
		switch($pickup_location) {
			case 'drive':
				$pickup_location_text = 'Drive Up Window';
				break;
			case 'west':
				$pickup_location_text = 'Main Library Lobby';
				break;
			case 'wacr':
				$pickup_location_text = 'Westacres Lobby';
				break;
		}
		
		switch($filter) {
			case '':
				$filter_text = 'Drive Up Window';
				break;
			case '0':
				$filter_text = 'Main Library Lobby';
				break;
			case '1':
				$filter_text = 'Westacres Lobby';
				break;
			case '-1':
				$filter_text = 'Westacres Lobby';
				break;
		}		
				 
		echo <<< HTML
		  <!doctype html>
		  <html lang="en">
		  <head>
			<meta charset="utf-8">
			<title>Overlooked Gems</title>
		 
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
		 
			<!--[if lt IE 9]>
			<script src="https://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.js"></script>
			<![endif]-->
		 
			<link href="//maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css" rel="stylesheet">
			<link href="//fonts.googleapis.com/css?family=Lato:300,400,700" rel="stylesheet">
			<link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css" rel="stylesheet">
			<link href="//cdn.materialdesignicons.com/2.3.54/css/materialdesignicons.min.css" rel="stylesheet">
			<link rel="stylesheet" href="css/og.css?v=1.8">
		 
		 
			<script src="//code.jquery.com/jquery-latest.min.js" type="text/javascript"></script>
		 
		  </head>
		 
		  <body>
		 
		 
		  <header>
		  <div id="patnum" type="hidden" data-val="{$pnumber}">
		  <div class="dropdown" id="frequency" data-val="{$frequency}">
			  <span style="color:#FFF; font-weight:bold;">Frequency: </span>
			<button class="btn btn-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				{$frequency_text}
			</button>
			<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
			<a class="dropdown-item" data-frequency="Weekly" href="#">Weekly</a>
			<a class="dropdown-item" data-frequency="Biweekly" href="#">Bi-Weekly</a>
			<a class="dropdown-item" data-frequency="Monthly" href="#">Monthly</a>
			 <a class="dropdown-item" data-frequency="Bimonthly" href="#">Bi-Monthly</a>
			 <a class="dropdown-item" data-frequency="Quarterly" href="#">Quarterly</a>
			 <a class="dropdown-item" data-frequency="Biannually" href="#">Bi-Annually</a>
			 <a class="dropdown-item" data-frequency="Annually" href="#">Annually</a>
			</div>
		  </div>
		 
		 
		 
		  <div class="dropdown" id="pickup_location" data-val="{$pickup_location}">
		  `<span style="color:#FFF; font-weight:bold;">Pickup Location: </span>
			<button class="btn btn-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				{$pickup_location_text}
			</button>
			<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
			<a class="dropdown-item" data-pickup="drive" href="#">Drive Up Window</a>
			<a class="dropdown-item" data-pickup="west" href="#">Main Library Lobby</a>
			<a class="dropdown-item" data-pickup="wacr" href="#">Westacres Lobby</a>
			</div>
		  </div>
		  
		  <div class="dropdown" id="filter" data-val="{$filter}">
		  `<span style="color:#FFF; font-weight:bold;">Filter By: </span>
			<button class="btn btn-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
				{$filter_text}
			</button>
			<div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
			<a class="dropdown-item" data-pickup="" href="#">No Filter</a>
			<a class="dropdown-item" data-pickup="0" href="#">No Rating</a>
			<a class="dropdown-item" data-pickup="1" href="#">Thumbs Up</a>
			<a class="dropdown-item" data-pickup="-1" href="#">Thumbs Down</a>
			</div>
		  </div>
		 
		  <div class="dropdown" id="patron_id" data-val="null" style="float:right;">
			<a href="./process_logout.php" style="color:#FFF;">
				<button style="background-color:#c00; font-size:16px; padding:6px;">
				Logout
				</button>
			</a>
		  </div>
		 
		 
		  </header>
		 
		 
		 
		 
		 
		 
		  <ul id="og-list">
		 
		 
		 
		  </ul>
		 
		 
		   <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js" integrity="sha512-K1qjQ+NcF2TYO/eI3M6v8EiNYZfA95pQumfvcVrTHtwQVDG+aHRqLi/ETn2uB+1JqwYqVG3LIvdm9lj6imS/pQ==" crossorigin="anonymous"></script>
		 
		 
		 
			<script src="js/og.js?v=1.1"></script>
		  </body>
		  </html>
HTML;
 
	}
	else {
		header("Location: index.php");
		exit;
	}
?>