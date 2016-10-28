<?PHP
include_once('header.inc.php');
include_once('account.inc.php');
$result['error_code'] = 0;

$phone_number = addslashes($_POST['phone_number']);
$validation_code = addslashes($_POST['validation_code']);
$new_password = addslashes($_POST['new_password']);

$query = "Select * from member where phone = '".$phone_number."' ";
$rs = $db->Execute($query);
if($row = $rs->FetchRow()){
	if($row['account_status'] == 1){
		$result['error_code'] = 204;
	}
	else{
		if($row['validation_code_expired_time'] >= date("Y-m-d H:i:s")){
			if(strcmp($validation_code, $row['validation_code']) === 0){
				
				$token = md5($phone_number.date('YmdHis').rand(10001,99999));
				
				$query = "Update member set pwd = PASSWORD('".$phone_number.$new_password."'), validation_code = '', validation_code_expired_time = '0000-00-00 00:00:00' where phone = '".$phone_number."' ";
				$result['query'] = $query;
				$db->Execute($query);
				//$result['token'] = $token;
			
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
}
else{
	$result['error_code'] = 201;
}

echo json_encode($result);
$db->Close();
?>
