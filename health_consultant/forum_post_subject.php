<?PHP
include_once('header.inc.php');
include_once('account.inc.php');
include_once('functions.inc.php');
$result['error_code'] = 0;


$phone_number = addslashes($_POST['phone_number']);
$token = addslashes($_POST['token']);

if(isValidToken($phone_number, $token)){
	if(isAllParamsValid($_POST, array('board_id', 'subject', 'content'), false)){
		
		$board_id = addslashes($_POST['board_id']);
		$subject = addslashes($_POST['subject']);
		$content = addslashes($_POST['content']);
		$query = "Insert into forum_subject "
				."Set author_phone = '".$phone_number."', "
				."board_id = ".$board_id.", "
				."subject = '".$subject."', "
				."content = '".$content."', "
				."post_time = '".date("Y-m-d H:i:s")."', "
				."last_reply_time = '".date("Y-m-d H:i:s")."' ";
		$db->Execute($query);
		
	}
	else{
		$result['error_code'] = 301;
		$result['error_msg'] = '欄位未填寫完整';
	}
	
}


echo json_encode($result);
$db->Close();



?>
