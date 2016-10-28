<?PHP
include_once('header.inc.php');
$result['error_code'] = 0;

$phone_number = addslashes($_POST['phone_number']);
$isForChangePassword = false;
if(array_key_exists('purpose', $_POST)){
	if(strcmp($_POST['purpose'], 'change_password') === 0){
		$isForChangePassword = true;	
	}
}

$query = "Select * from member where phone = '".$phone_number."' ";
$rs = $db->Execute($query);
if($row = $rs->FetchRow()){
	if($row['account_status'] == 1
	||($row['account_status'] == 2 && $isForChangePassword)){
		//重新取得驗證碼
		assignValidationCode(false);
	}
	else{
		$result['error_code'] = 1;
		$result['error_msg'] = '此帐号已存在';	
	}
}
else{
	if($isForChangePassword){
		$result['error_code'] = 1;
		$result['error_msg'] = '此帐号不存在';	
	}
	else{
		//建立帳號並產生驗證碼
		assignValidationCode(true);
	}	
}



echo json_encode($result);
$db->Close();




function assignValidationCode($create){
	global $db;
	global $phone_number;
	global $isForChangePassword;
	$code = rand(1234567,9994321);
	$fifteenMinutesLater = date("Y-m-d H:i:s", strtotime('+15 minute'));
	if($create){
		$query = "Insert into member set phone = '".$phone_number."', account_status = 1, validation_code = '".$code."', validation_code_expired_time = '".$fifteenMinutesLater."' ";
	}
	else{
		$query = "Update member set account_status = ".(($isForChangePassword)?2:1).", validation_code = '".$code."', validation_code_expired_time = '".$fifteenMinutesLater."' where phone = '".$phone_number."' ";
	}
	$db->Execute($query);
	
}

?>
