<?PHP
include_once('header.inc.php');
include_once('account.inc.php');
$result['error_code'] = 0;


$phone_number = addslashes($_POST['phone_number']);
$token = addslashes($_POST['token']);



if(isValidToken($phone_number, $token)){
	
	$query = "select so.csts_id, max(so.expiry_date) as expiry_date, cso.csts_id, cso.csts_name, cso.csts_desc, team.cstt_id, team.cstt_name "
			."from solution_order as so "
			."left join consultation_solution as cso "
			."on cso.csts_id = so.csts_id "
			."left join consultant_team team "
			."on team.cstt_id = cso.cstt_id "
			."where so.phone_number = '".$phone_number."' "
			."group by cso.csts_id "
			."order by expiry_date desc ";
	$rs = $db->Execute($query);
	$order_list = array();
	$today = date('Y-m-d');
	
	$valid_csts_ids = array();
	while($row = $rs->FetchRow()){
		$row['status'] = 'OK';
		if(strcmp($row['expiry_date'], $today) < 0){
			$row['status'] = 'expired';	
		}
		else{
			$valid_csts_ids[] = $row['csts_id'];
		}
		$order_list[] = $row;
	}
	$result['order_list'] = $order_list;
	
	$board_list = array();
	if(count($valid_csts_ids) > 0){
		$query = "SELECT DISTINCT sb.board_id, fb.board_title FROM `solution_board` as sb "
				."left join forum_board as fb "
				."on fb.board_id = sb.board_id "
				."where csts_id in (".implode(',', $valid_csts_ids).") ";
		$rs = $db->Execute($query);
		while($row = $rs->FetchRow()){
			$board_list[] = $row;
		}
	}
	
	
	$result['board_list'] = $board_list;
	
}


echo json_encode($result);
$db->Close();



?>
