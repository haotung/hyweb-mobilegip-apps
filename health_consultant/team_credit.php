<?PHP
include_once('header.inc.php');
$result['error_code'] = 0;

if(array_key_exists('team_id', $_GET) && strlen($_GET['team_id'])){
	$team_id = addslashes($_GET['team_id']);
	if(intval($team_id) > 0){
		fetch_record($team_id);	
	}
	else{
		fetch_record(0);	
	}
}
else{
	fetch_record(0);	
}


echo json_encode($result);
$db->Close();

function fetch_record($team_id){
	global $db;
	global $result;
	$query = "Select cstt_id, cstt_credit "
			."From consultant_team ";
	if($team_id > 0){
		$query .= "Where cstt_id = ".$team_id." ";
	}
	$rs = $db->Execute($query);
	
	$scores = array();
	while($row = $rs->FetchRow()){
		$scores[] = array('team_id' => $row['cstt_id'], 'credit' => $row['cstt_credit']);
	}
	
	$result['scores'] = $scores;
	
}


?>
