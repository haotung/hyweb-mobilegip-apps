<?PHP
include_once('header.inc.php');
include_once('account.inc.php');
$result['error_code'] = 0;


$phone_number = addslashes($_POST['phone_number']);
$token = addslashes($_POST['token']);



if(isValidToken($phone_number, $token)){
	
	$query = "select distinct solution_board.board_id, forum_board.board_title "
			." FROM ( "
			."    select solution_order.csts_id, max(solution_order.expiry_date) as expiry_date  "
			."    from solution_order, solution_board "
			."    where solution_order.phone_number = '".$phone_number."' "
			."    and expiry_date >= '".date("Y-m-d")."' "
			."    and solution_board.csts_id = solution_order.csts_id "
			."    group by solution_order.csts_id "
			.") as valid_orders,  "
			."solution_board "
			."left join forum_board "
			."on forum_board.board_id = solution_board.board_id "
			."where valid_orders.csts_id = solution_board.csts_id ";
	$rs = $db->Execute($query);
	$board_list = array();
	
	
	while($row = $rs->FetchRow()){
		$board_list[] = $row;
	}
	
	
	$board_count = count($board_list);
	$result['board_count'] = $board_count;
	for($i = 0; $i < $board_count; $i++){
		$board = $board_list[$i];
		$board_id = $board['board_id'];
		$result['board_ids'][] = $board_id;
		$subjects = array();
		$query = "Select s.subject_id, m.name, s.subject, s.content, s.post_time, s.last_reply_time, IFNULL(articles.reply_count, 0) as reply_count "
				."from forum_subject as s "
				."left join member as m "
				."on m.phone = s.author_phone "
				."left join ( "
				."	select subject_id, count(*) as reply_count "
				."	from forum_article  "
				."	where deleted = 0 "
				."  group by subject_id "
				.") as articles "
				."on articles.subject_id = s.subject_id "
				."Where s.deleted = 0 "
				."And s.board_id = ".$board_id." "
				."Order by last_reply_time desc "
				."Limit 0, 3 ";
		$rs = $db->Execute($query);
		while($row = $rs->FetchRow()){
			$subjects[] = $row;	
		}
		$board_list[$i]['subjects'] = $subjects;
		
	}
	
	
	$result['board_list'] = $board_list;
	
	
}


echo json_encode($result);
$db->Close();



?>
