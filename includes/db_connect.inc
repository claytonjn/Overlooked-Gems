<?php
	//**This include file contains the database
	//**connection details

	function db_sierradna() {

		$host =		"endmo-db.iii.com";
		$port =		"1032";
		$dbname =	"iii";
		$user =		"gem";
		$pass =		"IUG2017";

		$conn = pg_connect("host=".$host." port=".$port." dbname=".$dbname." user=".$user." password=".$pass." sslmode=require")
			or die('Could not connect to SierraDNA: ' . pg_last_error());

		if (!$conn)
		{
			// Check if valid connection
			// NO, some error occurred
			echo "Error Connecting to SierraDNA<br>";
			exit;
		}

		return $conn;
	}

?>