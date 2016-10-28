<?PHP
include_once('header.inc.php');
include_once('account.inc.php');
include_once('functions.inc.php');
$result['error_code'] = 0;


$phone_number = addslashes($_POST['phone_number']);
$token = addslashes($_POST['token']);



if(isValidToken($phone_number, $token)){
	if(isAllParamsValid($_POST, array(0 => 'board_id'), false)){
		if(is_numeric($_POST['board_id'])){
			$board_id = $_POST['board_id'];
			$query = "select distinct solution_board.board_id "
					." FROM ( "
					."    select solution_order.csts_id, max(solution_order.expiry_date) as expiry_date  "
					."    from solution_order, solution_board "
					."    where solution_order.phone_number = '".$phone_number."' "
					."    and expiry_date >= '".date("Y-m-d")."' "
					."    and solution_board.csts_id = solution_order.csts_id "
					."    group by solution_order.csts_id "
					.") as valid_orders,  "
					."solution_board "
					."where valid_orders.csts_id = solution_board.csts_id "
					."and solution_board.board_id = ".$board_id." ";
			
			$rs = $db->Execute($query);
			if($rs->FetchRow()){
				$subjects = array();
				$query = "Select s.subject_id, m.name, s.subject, s.content, s.post_time, s.last_reply_time, IFNULL(articles.reply_count, 0) as reply_count "
						."from forum_subject as s "
						."left join ( "
						."	select subject_id, count(*) as reply_count "
						."	from forum_article  "
						."	where deleted = 0 "
						."  group by subject_id "
						.") as articles "
						."on articles.subject_id = s.subject_id "
						."left join member as m "
						."on m.phone = s.author_phone "
						."Where s.deleted = 0 "
						."And s.board_id = ".$board_id." "
						."Order by last_reply_time desc ";
				if(array_key_exists('start_index', $_POST) && is_numeric($_POST['start_index'])){
					$query .= "Limit ".$_POST['start_index'].", 100 ";
				}
				else{
					$query .= "Limit 0, 100 ";
				}
				$rs = $db->Execute($query);
				while($row = $rs->FetchRow()){
					$subjects[] = $row;	
				}
				$result['subjects'] = $subjects;
			
			}
			else{	
				$result['error_code'] = 301;
				$result['error_msg'] = '您沒有權限閱讀此健康圈';
			}
			
		}
		else{
			$result['error_code'] = 301;
			$result['error_msg'] = '健康圈代碼錯誤';
				
		}
		
	}
	else{
		$result['error_code'] = 301;
		$result['error_msg'] = '健康圈代碼錯誤';
	}
	
	
}


echo json_encode($result);
$db->Close();



?>
