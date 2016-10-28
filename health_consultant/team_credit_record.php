<?PHP
include_once('header.inc.php');
$result['error_code'] = 0;

if(array_key_exists('team_id', $_GET) && strlen($_GET['team_id'])){
	$team_id = addslashes($_GET['team_id']);
	if(intval($team_id) > 0){
		fetch_record();	
	}
	else{
		$result['error_code'] = 301;
	}
}
else{
	$result['error_code'] = 303;
}




echo json_encode($result);
$db->Close();

function fetch_record(){
	global $db;
	global $team_id;
	global $result;
	$query = "SELECT 'all_count' as count_type, count(*) as count_value "
			."FROM `team_credit_record` "
			."where cstt_id = ".$team_id." "
			."UNION ALL "
			."SELECT 'great_count', count(*) as count_value "
			."FROM `team_credit_record` "
			."where cstt_id = ".$team_id." "
			."And score > 6 "
			."UNION ALL "
			."SELECT 'normal_count', count(*) as count_value "
			."FROM `team_credit_record` "
			."where cstt_id = ".$team_id." "
			."And score = 6 "
			."UNION ALL "
			."SELECT 'bad_count', count(*) as count_value " 
			."FROM `team_credit_record` "
			."where cstt_id = ".$team_id." "
			."And score < 6 ";
	$rs = $db->Execute($query);
	$result['count_values'] = array();
	while($row = $rs->FetchRow()){
		$result['count_values'][$row['count_type']] = $row['count_value'];
	}
	
	$query = "select member.name, score, team_credit_record.description, input_time "
			."from team_credit_record "
			."left join member "
			."on team_credit_record.phone_number = member.phone "
			."where cstt_id = ".$team_id." "
			."and length(team_credit_record.description) > 0 "
			."and member.name is NOT NULL "
			."Order by input_time desc "
			."limit 0, 50 ";
	$rs = $db->Execute($query);
	$records = array();
	while($row = $rs->FetchRow()){
		$records[] = $row;
	}
	$result['records'] = $records;
	
}


?>
