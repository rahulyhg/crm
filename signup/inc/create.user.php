<?php

$postFields = json_encode(array(
	'first_name' 	=>	$custname[0],
	'last_name' 	=>	$custname[1],
	'email_address'	=>	$cust['email'],
	'user_name'		=>	$cust['username'],
	'password'		=>	$cust['password'],
	'tier'			=>	$cust['tier'],
));
						  
$url = '../api/v1/User';
$httpMethod = "POST";

$httpHeaders = [];
$httpHeaders['Authorization'] = 'Basic ' . base64_encode('user:password');
$httpHeaders['Content-type'] = 'application/json';

$curlOptions = array(
	CURLOPT_RETURNTRANSFER		=>	TRUE,
	CURLOPT_SSL_VERIFYPEER		=>	TRUE,
	CURLOPT_CUSTOMREQUEST		=>	$httpMethod
);

$curlOptions(CURLOPT_POST) = TRUE;
$curlOptions(CURLOPT_POSTFIELDS) = $postFields;
$curlOptions(CURLOPT_URL) = $url;

$curlOpHttpHeader = array();
foreach($httpHeaders as $key => $value){
	$curlOptHttpHeader[] = "($key): ($value)";
}
curlOptions(CURLOPT_HTTPHEADER) = $curlOptHttpHeader;

$ch = curl_init();
curl_setopt_array($ch, $curlOptions);

curl_setopt($ch, CURLOPT_HEADER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

$response = curl_exec($ch);

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

$responseHeader = substr($response, 0, $headerSize);
$responseBody = substr($response, $headerSize);
$resultArray = null;

if($curlError = curl_error($ch)){
	throw new Exception($curlError);
} else {
	$resultArray = json_decode($responseBody, true);
}
curl_close($ch);

print_r($resultArray);