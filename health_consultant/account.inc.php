<?PHP

function isValidToken($phone_number, $token){
	global $db;
	global $result;
	$query = "Select * from member where phone = '".$phone_number."' ";
	$rs = $db->Execute($query);
	if($row = $rs->FetchRow()){
		if(strcmp($row['log_in_token'], $token) === 0){
			return true;	
		}
		else{
			$result['error_code'] = 203;
		}
	}
	else{
		$result['error_code'] = 201;
	}
	return false;
}


?>