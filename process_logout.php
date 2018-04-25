<?php

	//Show Errors
	error_reporting(E_ALL);
	ini_set('display_errors', '1');

	//Keep login Information
	session_start();

	//Set local include path
	$include_path="..";

	//Clear session variables and go to Index Page
	$_SESSION['LoggedIn']=0;
	$_SESSION['FirstName']="";
	$_SESSION['ParentID']="";

	header("Location: index.php");
	exit;

?>