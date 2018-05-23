<?php

class SignupModel{
	// Check to see if company name already exists
	public function chkCompany($c){
		// Cleanup Company Name & Convert to standard format
		$name = $this->clnName($c[companyname], 1); 
		// Check for duplicate company name
		$result = $this->execSql($this->getSql(1, $name));
		
		if($result->num_rows > 0){
			// Record already exists
			return TRUE;
		} else {
			return FALSE;
		}
		
	}
	// Check to see if email already exists
	public function chkEmail($c){
		// Check for duplicate email address
		$result = $this->execSql($this->getSql(3, strtolower($c['email'])));
		
		if($result->num_rows > 0){
			return TRUE;
		} else {
			return FALSE;
		}
	}
	// Check to see if username already exists
	public function chkUser($c){
		// Cleanup username & convert to standard format
		$name = $this->clnName($c[username], 2);
		// Check for duplicate user name
		$result = $this->execSql($this->getSql(2, $name));
		
		if($result->num_rows > 0){
			return TRUE;
		} else {
			return FALSE;
		}
	}
	// Create company name as team
	public function crtTeam($c){
		// Create ID
		$id = $this->genId();
		// Define insert variables
		$uname		= $c['username'];
		$team		= $this->clnName($c['companyname'], 1);
		
		$table 		= 'team';
		$columns 	= '(`id`, `name`, `created_at`)';
		$values 	= "('$id', '$team', NOW())";
		
		$table 		.= ' ' . $columns;
		// Construct & execute query
		$result = $this->execSql($this->insSql(1, $table, $values));
		
		if($result){
			return $id;
		} else {
			return FALSE;
		}
	}
	// Create user account
	public function crtUser($c, $tid){
		// Create ID
		$id = $this->genId();
		// Hash User password for DB 
		$pass = $this->hshPw($c['password']);
		// Define insert variables
		$uname 		= $c['username'];
		$user		= explode(' ', $c['fullname']);
		$fname		= $user[0];
		$lname		= $user[1];
		$table 		= 'user';
		$columns 	= '(`id`, `is_admin`, `user_name`, `password`, `first_name`, `last_name`, `is_active`, `is_portal_user`, `default_team_id`, `created_at`)';
		$values		= "('$id', 0, '$uname', '$pass', '$fname', '$lname', 1, 0, '$tid', NOW())";
		
		$table 		.= ' ' . $columns;
		// Construct & execute query
		$result = $this->execSql($this->insSql(1, $table, $values));
		
		if($result){
			return $id;
		} else {
			return FALSE;
		}
		
	}
	// User & Team Association
	public function assUser($uid, $tid){
		// Define insert variables
		$table 		= 'team_user';
		$columns	= '(`team_id`, `user_id`)';
		$values		= "('$tid', '$uid')";
		
		$table		.= ' ' . $columns;
		// Construct & execute query
		$result = $this->execSql($this->insSql(1, $table, $values));
		
		if($result){
			return TRUE;
		} else {
			return FALSE;
		}
	}
	// Rolse Aasociation
	public function assRole($uid, $tid){
		$errno = 0; 	$error = '';
		// Define insert variables for Role/Team
		$rid		= $this->roleId();
		$table		= 'role_team';
		$columns	= '(`role_id`, `team_id`)';
		$values		= "('$rid', '$tid')";
		
		$table		.= ' ' . $columns;
		// Construct and execute query
		$result = $this->execSql($this->insSql(1, $table, $values));
		
		if(!$result) { $errno++; $error .= $result->error; }
		
		// Define insert variables for Role/User
		$table		= 'role_user';
		$columns 	= '(`role_id`, `user_id`)';
		$values 	= "('$rid', '$uid')";
		
		$table 		.= ' ' . $columns;
		// Construct and execute query
		$result = $this->execSql($this->insSql(1, $table, $values));
		
		if(!$result) { $errno++; $error .= $result->error; }
		
		if($errno == 0){
			return TRUE;
		} else{
			return $error;
		}
	}
	// Company name cleanup
	private function clnName($n, $t){
		// Remove spaces from name
		$team = str_replace(' ', '', $n);
		// Remove whitespaces
		$team = preg_replace('/\s+/', '', $team);
		if($t == 1){
			// Company Name: Convert to uppercase
			$team = strtoupper($team);
		}
		if($t == 2){
			// Username: Convert to lowercase
			$team = strtolower($team);
		}
		
		return $team;
	}
	private function genId(){
		return uniqid() . substr(md5(rand()), 0, 4);
	}
	private function roleId(){
		return "5a1a3865817c9f933";
	}
	private function hshPw($p){
		$salt = '2fd1555f5242dc8b';
		$salt = $this->normalizeSalt($salt);
		
		$pass = md5('thematrix');
		
		$hash = crypt($pass, $salt);
		$hash = str_replace($salt, '', $hash);
		
		return $hash;
	}
	private function normalizeSalt($salt){
		$saltFormat = '$6${0}$';
		
		return str_replace("{0}", $salt, $saltFormat);
	}
	// SQL Retrieve Codes
	private function getSql($s, $k){
		switch($s){
			case 1:
				$sql = "SELECT * FROM team WHERE name = '$k'";
				break;
			case 2:
				$sql = "SELECT * FROM user WHERE user_name = '$k'";
				break;
			case 3:
				$sql = "SELECT * FROM email_address WHERE lower = '$k'";
				break;
		} 
		return $sql;
	}
	// SQL Insert Codes
	private function insSql($s, $t, $v){
		switch($s){
			case 1:
				$sql = "INSERT INTO $t VALUES $v";
				break;
		}
		return $sql;
	}
	// Execute Queries
	private function execSql($q){
		global $conn;
		
		$result = $conn->query($q);
		
		return $result;
	}
}
?>