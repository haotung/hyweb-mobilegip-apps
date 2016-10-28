<?PHP
include_once('header.inc.php');
$result['error_code'] = 0;
/**********

此為開發期間的後門，上線務必刪除
*********/

$phone_number = addslashes($_GET['phone_number']);

$query = "Select * from member where phone = '".$phone_number."' ";
$rs = $db->Execute($query);
if($row = $rs->FetchRow()){
		if($row['validation_code_expired_time'] >= date("Y-m-d H:i:s")){
			$result['code'] = $row['validation_code'];
		}
		else{
			$result['error_code'] = 1;
			$result['error_msg'] = '验证码已过期';
		}
}
else{
	$result['error_code'] = 1;
	$result['error_msg'] = '帐号不存在';	
}



echo json_encode($result);
$db->Close();



?>
