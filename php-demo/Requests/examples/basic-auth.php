<?php

// First, include Requests
include('../library/Requests.php');

// Next, make sure Requests can load internal classes
Requests::register_autoloader();

// Now let's make a request!
$options = array(
	'admin' => 'admin'
);
$request = Requests::get('http://localhost:8080/axelor-app/login.jsp', array(), $options);

var_dump($request->headers);

$response = Requests::get('http://localhost:8080/axelor-app/ws/rest/com.axelor.apps.supplychain.db.LocationLine/2', $request->headers);

var_dump($response);
// Check what we received
//var_dump($request);