<?PHP
include_once('header.inc.php');
$result['error_code'] = 0;

$hospital_id = 1;
if(array_key_exists('hospital_id', $_GET)){
	$hospital_id = $_GET['hospital_id'];	
}

$query = "Select hspt_name, hspt_desc "
		."From hospital "
		."Where hspt_id = ".$hospital_id." ";
$rs = $db->Execute($query);
if($row = $rs->FetchRow()){
	$hospital = array();
	$hospital['id'] = $hospital_id;
	$hospital['name'] = $row['hspt_name'];
	$hospital['description'] = $row['hspt_desc'];
	generateList();
}
else{
	$result['error_code'] = 302;	
}





echo json_encode($result);
$db->Close();

function generateList(){
	
	global $db;
	global $hospital_id;
	global $hospital;

	$query = "Select dept.dept_id, dept.dept_name, team.cstt_id, team.cstt_name, team.cstt_desc, team.cstt_credit, leader.cst_id, leader.cst_name, leader.cst_title "
			."From department as dept "
			."LEFT JOIN dept_cstt "
			."ON dept.dept_id = dept_cstt.dept_id "
			."LEFT JOIN consultant_team as team "
			."ON dept_cstt.cstt_id = team.cstt_id "
			."LEFT JOIN consultant as leader "
			."ON leader.cst_id = team.leader_cts_id "
			."Where dept.hspt_id = ".$hospital_id." ";
	$rs = $db->Execute($query);
	$lastDeptId = 0;
	$departments = array();
	$teams = array();
	$curDept = null;
	$all_consultants = array();
	while($row = $rs->FetchRow()){
		if($lastDeptId != $row['dept_id']){
			if(!is_null($curDept)){
				$departments[] = $curDept;	
			}
			$curDept = array();
			$curDept['dept_id'] = $row['dept_id'];
			$curDept['dept_name'] = $row['dept_name'];
			$curDept['teams'] = array();
		}
		if(!is_null($row['cstt_id'])){
			$team = array();
			$team['team_id'] = $row['cstt_id'];
			$team['team_name'] = $row['cstt_name'];
			$team['team_desc'] = $row['cstt_desc'];
			$team['team_credit'] = $row['cstt_credit'];
			$team['leader_id'] = $row['cst_id'];
			$team['leader_name'] = $row['cst_name'];
			$team['leader_title'] = $row['cst_title'];
			$team['solutions'] = array();
			
			$query = "Select mem.cst_id, mem.cst_name, mem.cst_title "
					."From consultant as mem, consultant_team_member "
					."Where consultant_team_member.cst_id = mem.cst_id "
					."And consultant_team_member.cstt_id = ".$team['team_id']." ";
			$memRs = $db->Execute($query);
			while($memRow = $memRs->FetchRow()){
				$memberId = $memRow['cst_id'];
				$member = array();
				$member['member_id'] = $memberId;
				$member['member_name'] = $memRow['cst_name'];
				$member['member_title'] = $memRow['cst_title'];
				if(!array_key_exists($memberId, $all_consultants)){
					$all_consultants[$memberId] = $member;
				}	
				$team['memberIds'][] = $memberId;
			}
			
			$query = "Select csts_id, csts_name, csts_desc, csts_price, csts_discounted_price, duration_in_month "
					."From consultation_solution "
					."Where cstt_id = ".$row['cstt_id']." ";
			$solRs = $db->Execute($query);
			while($solRow = $solRs->FetchRow()){
				$solution = array();
				$solution['sol_id'] = $solRow['csts_id'];
				$solution['sol_name'] = $solRow['csts_name'];
				$solution['sol_description'] = $solRow['csts_desc'];
				$solution['sol_price'] = $solRow['csts_price'];
				$solution['sol_discounted_price'] = $solRow['csts_discounted_price'];
				$solution['sol_duration_in_month'] = $solRow['duration_in_month'];	
				$team['solutions'][] = $solution;
			}
			
			
			$curDept['teams'][] = $team;
		}
		$lastDeptId = $row['dept_id'];
		//echo $row['dept_id']." ".$row['dept_name']." ".$row['cstt_name']."\n";	
	}
	if(!is_null($curDept)){
		$departments[] = $curDept;	
	}
	
	$hospital['departments'] = $departments;
	$hospital['all_consultants'] = $all_consultants;
	$hospital_json = json_encode($hospital);
	$query = "Insert into all_consultant_cache "
			."Set hspt_id = ".$hospital_id.", "
			."consultant_list_json = '".addslashes($hospital_json)."', "
			."update_time = '".date("Y-m-d H:i:s")."' ";
	$db->Execute($query);
}

?>
