<?PHP
include_once('header.inc.php');
include_once('account.inc.php');
$result['error_code'] = 0;


$phone_number = addslashes($_POST['phone_number']);
$token = addslashes($_POST['token']);



if(isValidToken($phone_number, $token)){
	if(array_key_exists('solution_id', $_POST)
	&& array_key_exists('trade_price', $_POST)
	&& array_key_exists('payment_way', $_POST)){
		$solution_id = addslashes($_POST['solution_id']);
		$trade_price = addslashes($_POST['trade_price']);
		$payment_way = addslashes($_POST['payment_way']);
		doTransaction($phone_number, $solution_id, $trade_price, $payment_way);
	}
	else{
		$result['error_code'] = 303;
		
	}
	
	
	
	
}


echo json_encode($result);
$db->Close();

function gen_new_expiry_date($cur_expiry_date, $extend_months){
	$offset_str = '+'.$extend_monthes.' months';
	if($extend_months % 12 == 0){
		$offset_str = '+'.($extend_months / 12).' years';
	}
	$new_expiry_date = date('Y-m-d', strtotime($offset_str, strtotime($cur_expiry_date)));
	return date('Y-m-d', strtotime('-1 days', strtotime($new_expiry_date)));
}

function doTransaction($phone_number, $solution_id, $trade_price, $payment_way){
	global $db;
	global $result;
	$query = "SELECT duration_in_month, max(expiry_date) as current_expiry_date "
			."FROM `consultation_solution` "
			."left join solution_order "
			."on solution_order.csts_id = consultation_solution.csts_id "
			."where consultation_solution.csts_id = ".$solution_id." ";
	$rs = $db->Execute($query);
	if($row = $rs->FetchRow()){
		$duration_in_month = $row['duration_in_month'];
		if(is_null($row['current_expiry_date'])){
			$begin_date = date('Y-m-d');
		}	
		else{
			$begin_date = date('Y-m-d', strtotime('+1 days', strtotime($row['current_expiry_date'])));
		}
		$new_expiry_date = gen_new_expiry_date($begin_date, $duration_in_month);
		
		$query = "Insert into solution_order "
				."Set phone_number = '".$phone_number."', "
				."csts_id = ".$solution_id.", "
				."begin_date = '".$begin_date."', "
				."expiry_date = '".$new_expiry_date."', "
				."trade_price = ".$trade_price.", "
				."payment_way = '".$payment_way."', "
				."payment_time = '".date("Y-m-d H:i:s")."', "
				."payment_sn = '1234' ";
		$db->Execute($query);		
		
	}
	else{
		$result['error_code'] = 1;
		$result['error_msg'] = '無法購買此套餐';	
	}
	
	
	
}

?>
