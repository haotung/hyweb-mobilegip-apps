<?PHP
include_once('header.inc.php');
include_once('account.inc.php');
include_once('functions.inc.php');
error_reporting(E_ALL);
$result['error_code'] = 0;


$phone_number = addslashes($_POST['phone_number']);
$token = addslashes($_POST['token']);

if(isValidToken($phone_number, $token)){
	if(isAllParamsValid($_POST, array('subject_id', 'content'), false)){
		
		$subject_id = addslashes($_POST['subject_id']);
		$content = addslashes($_POST['content']);
		
		
		//先確定原主題是否有被刪除
		$query = "Select * "
				."From forum_subject "
				."Where subject_id = ".$subject_id." "
				."And deleted = 0 ";
		$rs = $db->Execute($query);
		if($row = $rs->FetchRow()){
			$query = "Insert into forum_article "
					."Set author_phone = '".$phone_number."', "
					."subject_id = ".$subject_id.", "
					."content = '".$content."', "
					."post_time = '".date("Y-m-d H:i:s")."' ";
			$db->Execute($query);
			
			$query = "Update forum_subject "
					."Set last_reply_time = '".date("Y-m-d H:i:s")."' "
					."Where subject_id = ".$subject_id." ";
			$db->Execute($query);
		}
		
		
		
		
	}
	else{
		$result['error_code'] = 301;
		$result['error_msg'] = '欄位未填寫完整';
	}
	
}


echo json_encode($result);
$db->Close();



?>
