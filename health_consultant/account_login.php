<?PHP
include_once('header.inc.php');
include_once('account.inc.php');
$result['error_code'] = 0;


$phone_number = addslashes($_POST['phone_number']);
$pwd = addslashes($_POST['pwd']);

$query = "Select * from member where phone = '".$phone_number."' and pwd = PASSWORD('".$phone_number.$pwd."') ";
$rs = $db->Execute($query);
if($row = $rs->FetchRow()){
	$token = md5($phone_number.date('YmdHis').rand(10001,99999));
	$query = "Update member set log_in_token = '".$token."' where phone = '".$phone_number."' ";
	$db->Execute($query);
	$result['token'] = $token;
	$result['name'] = $row['name'];
	$result['phone_number'] = $row['phone'];
	$result['sex'] = $row['sex'];
	$result['birth_year'] = $row['birth_year'];
	$result['height'] = $row['height'];
	$result['description'] = $row['description'];
	
	
	$query = "select so.csts_id, max(so.expiry_date) as expiry_date, cso.csts_id, cso.csts_name, cso.csts_desc, team.cstt_id, team.cstt_name "
			."from solution_order as so "
			."left join consultation_solution as cso "
			."on cso.csts_id = so.csts_id "
			."left join consultant_team team "
			."on team.cstt_id = cso.cstt_id "
			."where so.phone_number = '".$phone_number."' "
			."group by cso.csts_id ";
	$rs = $db->Execute($query);
	$order_list = array();
	$today = date('Y-m-d');
	while($row = $rs->FetchRow()){
		$row['status'] = 'OK';
		if(strcmp($row['expiry_date'], $today) < 0){
			$row['status'] = 'expired';	
		}
		$order_list[] = $row;
	}
	$result['order_list'] = $order_list;
	
}
else{
	$result['error_code'] = 202;
}


echo json_encode($result);
$db->Close();



?>
