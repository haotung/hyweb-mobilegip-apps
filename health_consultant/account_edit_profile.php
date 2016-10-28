<?PHP
include_once('header.inc.php');
include_once('account.inc.php');
$result['error_code'] = 0;


$phone_number = addslashes($_POST['phone_number']);
$token = addslashes($_POST['token']);
$birth_year = addslashes($_POST['birth_year']);
$height = addslashes($_POST['height']);
$sex = addslashes($_POST['sex']);
$description = addslashes($_POST['description']);



if(isValidToken($phone_number, $token)){
	if(!is_numeric($birth_year)){
		$result['error_code'] = 301;
		$result['error_msg'] = '出生年格式有误';
	}
	else if($birth_year < 1890 || $birth_year > intval(date('Y'))){
		$result['error_code'] = 301;
		$result['error_msg'] = '出生年有误';
	}
	else if(!is_numeric($height)){
		$result['error_code'] = 301;
		$result['error_msg'] = '身高格式有误';
	}
	else{
		$query = "Update member "
				."set birth_year = ".$birth_year.", height = ".$height.", sex = ".$sex.", description = '".$description."' "
				."Where phone = '".$phone_number."' ";
		$db->Execute($query); 	
	}
}


echo json_encode($result);
$db->Close();



?>
