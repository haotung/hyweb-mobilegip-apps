<?PHP
include_once('header.inc.php');
include_once('account.inc.php');
$result['error_code'] = 0;


$phone_number = addslashes($_POST['phone_number']);
$token = addslashes($_POST['token']);
$team_id = addslashes($_POST['team_id']);
$score = addslashes($_POST['score']);
$description = addslashes($_POST['description']);

if(isValidToken($phone_number, $token)){
	$query = "Select * From team_credit_record "
			."Where phone_number = '".$phone_number."' "
			."And cstt_id = ".$team_id." ";
	$rs = $db->Execute($query);
	if($row = $rs->FetchRow()){
		$result['error_code'] = 1;
		$result['error_msg'] = "已對此團隊評分過";	
	}
	else{
		
		$query = "Insert into team_credit_record "
				."Set cstt_id = ".$team_id.", "
				."score = ".$score.", "
				."phone_number = '".$phone_number."', "
				."description = '".$description."', "
				."input_time = '".date('Y-m-d H:i:s')."' ";
		$db->Execute($query);
		
		$query = "select sum(score) as all_score, count(*) as record_count "
				."From team_credit_record "
				."Where cstt_id = ".$team_id." "
				."group by cstt_id ";
		$rs = $db->Execute($query);
		if($row = $rs->FetchRow()){
			if(intval($row['record_count']) > 0){
				$credit = round($row['all_score'] / $row['record_count'], 1);
				$query = "Update consultant_team "
						."Set cstt_credit = ".$credit." "
						."Where cstt_id = ".$team_id." ";
				$db->Execute($query);
			}
		}
		
	}
}


echo json_encode($result);
$db->Close();



?>
