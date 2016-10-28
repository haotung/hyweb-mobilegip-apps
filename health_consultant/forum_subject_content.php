<?PHP
include_once('header.inc.php');
include_once('account.inc.php');
include_once('functions.inc.php');
$result['error_code'] = 0;


$phone_number = addslashes($_POST['phone_number']);
$token = addslashes($_POST['token']);



if(isValidToken($phone_number, $token)){
			
	if(isAllParamsValid($_POST, array(0 => 'subject_id'), false)){
		
		if(is_numeric($_POST['subject_id'])){
			//echo "subject_id = ".$_POST['subject_id'];
			
			$subject_id = $_POST['subject_id'];
			$query = "Select subject, content, post_time, member.name "
					."From forum_subject "
					."Left Join member "
					."On member.phone = forum_subject.author_phone "
					."Where subject_id = ".$subject_id." "
					."And forum_subject.deleted = 0 ";
			
			$rs = $db->Execute($query);
			if($row = $rs->FetchRow()){
				$result['subject'] = $row['subject'];
				$result['content'] = $row['content'];
				$result['post_time'] = $row['post_time'];
				$result['author'] = $row['name'];
				
				
				$articles = array();
				$query = "Select article_id, subject_id, member.name, content, post_time "
						."From forum_article "
						."Left Join member "
						."on member.phone = forum_article.author_phone "
						."Where forum_article.deleted = 0 "
						."And subject_id = ".$subject_id." "
						."Order by post_time desc ";
				if(array_key_exists('start_index', $_POST) && is_numeric($_POST['start_index'])){
					$query .= "Limit ".$_POST['start_index'].", 100 ";
				}
				else{
					$query .= "Limit 0, 100 ";
				}
				$rs = $db->Execute($query);
				while($row = $rs->FetchRow()){
					$articles[] = $row;	
				}
				$result['replies'] = $articles;
				
				
			}
			else{
				$result['error_code'] = 302;
				$result['error_msg'] = '無此主題';
			}
			
			
		}
		else{
			$result['error_code'] = 301;
			$result['error_msg'] = '主題代碼錯誤';
				
		}
	}
	else{
		$result['error_code'] = 301;
		$result['error_msg'] = '主題代碼錯誤';
	}
			
	
	
}


echo json_encode($result);
$db->Close();



?>
