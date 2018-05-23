<?php
$baseurl = $_SERVER['REQUEST_URI'];

require('inc/inc.dbconnect.php');

if($_SERVER['REQUEST_METHOD'] == 'POST'){
	if(!empty($_POST['Signup'])){
		// Obtain post array
		$cust = $_POST['Signup'];
		
		include('inc/class.signup.php');
		
		$signup = new SignupModel();
		
		
		// Check Duplicate Company Name
		$chkDup = $signup->chkCompany($cust);
		if($chkDup == TRUE){
			include('inc/exist.comp.php');
		}
		// Check Duplicate Email Address
		$chkDup2 = $signup->chkEmail($cust);
		if($chkDup2 == TRUE){
			include('inc/exist.email.php');
		}
		// Check Duplicate Username
		$chkDup3 = $signup->chkUser($cust);
		if($chkDup3 == TRUE){
			include('inc/exist.user.php');
		}
		if($chkDup == FALSE && $chkDup2 == FALSE && $chkDup3 == FALSE){
			// Create Team Record and retrieve Team ID
			$tid = $signup->crtTeam($cust);
			// Create User Record and retrieve User ID
			$uid = $signup->crtUser($cust, $tid);
			// Assign User to Team
			$ut = $signup->assUser($uid, $tid);
			// Assign Role to user and Team
			$rtu = $signup->assRole($uid, $tid);
			
			if($rtu){
				// Success
				echo "Success!";
			}
		}

	}
}
?>