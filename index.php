<?php
	/*
	(c) 2015 Ben McLean, Arctro Pty Ltd
	API Processer
	*/
	
	//Import API
	require('water_watch.php');
	
	//Encryption Keys, Link
	require('secret.php');
	
	//New Water_Watch API
	$Water_Watch = new Water_WatchAPI($link);
	
	//Arctro Base
	$base = new ArctroBase($link);
	
	//Get and merge GET and POST
	$_REQUEST = array_merge($_GET, $_POST);
	
	//Remove possible SQL Injection, HTML Tags
	foreach ($_REQUEST as $key => $value) {
		$_REQUEST[$key] = $base->string_safe($value);
	}
	
	//Run API
	$result = $Water_Watch->handle_api($_REQUEST);
	
	echo json_encode($result);
?>
