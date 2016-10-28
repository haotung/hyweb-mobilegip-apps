<?PHP
include_once('header.inc.php');
$result['error_code'] = 0;


$phone_number = addslashes($_POST['phone_number']);
$validation_code = addslashes($_POST['validation_code']);
$id_number = addslashes($_POST['id_number']);
$name = addslashes($_POST['name']);
$pwd = addslashes($_POST['pwd']);




$query = "Select * from member where phone = '".$phone_number."' ";
$rs = $db->Execute($query);
if($row = $rs->FetchRow()){
	if($row['account_status'] == 1){
		if($row['validation_code_expired_time'] >= date("Y-m-d H:i:s")){
			if(strcmp($validation_code, $row['validation_code']) === 0){
				if(strlen($name) == 0 || strlen($pwd) == 0){
					$result['error_code'] = 1;
					$result['error_msg'] = '资料填写不完整';
				}
				else{
					$token = md5($phone_number.date('YmdHis').rand(10001,99999));
					$query = "Update member set log_in_token = '".$token."', name = '".$name."', pwd = PASSWORD('".$phone_number.$pwd."'), id_number = '".$id_number."', account_status = 2, validation_code = '', validation_code_expired_time = '0000-00-00 00:00:00' where phone = '".$phone_number."' ";
					$db->Execute($query);
					$result['token'] = $token;
				}
			}
			else{
				$result['error_code'] = 1;
				$result['error_msg'] = '验证码错误';
			}
		}
		else{
			$result['error_code'] = 1;
			$result['error_msg'] = '验证码已过期';
		}
	}
	else{
		$result['error_code'] = 1;
		$result['error_msg'] = '此帐号已验证';	
	}
}
else{
	$result['error_code'] = 201;
	$result['error_msg'] = '帐号不存在';	
}



echo json_encode($result);
$db->Close();



?>
