<?php
set_time_limit(14400);

$host	= '107.143.141.99';
$user	= 'samexadmin';
$pass	= 'qkkGx4!982';
$data	= 'SAMEXCRM';

$conn = new mysqli($host, $user, $pass, $data);

if($conn->connect_error){
	die("Connection failed: " . $conn->connect_errno . ' ' . $conn->connect_error);
}
?>