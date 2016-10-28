<?PHP
include_once('config.inc.php');
$data = json_decode(file_get_contents('php://input'), true);
$result = array('success' => false);
$appId = $data['appId'];
$newData = $data['newData'];
$targetTable = $data['targetTable'];
$assignPhrase = '';
if(count($newData) > 0){
	
	
	if(strcmp($targetTable, 'app') === 0){
		foreach ($newData as $key => $value){
			if(strlen($assignPhrase) > 0){
				$assignPhrase .= ', ';	
			}
			$assignPhrase .= $key." = '".addslashes($value)."' ";
		}
		$query = "Select * from app where app_id = '".$appId."' ";
		$rs = $db->Execute($query);
		if($row = $rs->FetchRow()){
			$query = "Update app set ".$assignPhrase." Where app_id = '".$appId."' ";
		}
		else{
			$query = "Insert into app set app_id = '".$appId."', ".$assignPhrase;	
		}
		$db->Execute($query);
		$result['query'] = $query;
		$result['success'] = true;
	}
	else if(strcmp($targetTable, 'app_additional_attrs') === 0){
		$query = "Delete from app_additional_attrs "
				."Where app_id = '".$appId."' ";
		$db->Execute($query);
		
		foreach ($newData as $key => $value){
			
			$query = "Insert into app_additional_attrs "
					."Set app_id = '".$appId."', "
					."attr_name = '".$key."', "
					."attr_values = '".$value."' ";
			$db->Execute($query);
			
		}
		$result['success'] = true;
		
	}
}


echo json_encode($result);
$db->Close();
?>