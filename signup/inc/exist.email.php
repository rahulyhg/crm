<?php
$url = 'https://crm.samex.online/base/signup';

$fields = array(
	'fullname' 		=>	$cust['fullname'],
	'companyname' 	=>	$cust['companyname'],
	'email'			=>	$cust['email'],
	'username'		=>	$cust['username'],
	'password'		=>	$cust['password'],
	'tier'			=>	$cust['tier'],
	'companyexists'	=>	TRUE,
);

$postvars = http_build_query($fields);

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, count($fields));
curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);

// $result = curl_exec($ch);

curl_close($ch);

header("Location:". $url . "?eex=1&f=".$cust['fullname']."&c=".$cust['companyname']."&e=".$cust['email']."&u=".$cust['username']."&t=".$cust['tier']."");

?>