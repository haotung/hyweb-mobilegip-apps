<?PHP
include_once('config.inc.php');
header('Content-Type: application/json; charset=utf-8');
$output['success'] = 'YES';
$output['time'] = date('Y-m-d H:i:s');
main();
$db->Close();
echo json_encode($output);


function main(){
	global $output;
	global $db;
	$data = json_decode(file_get_contents('php://input'), true);
	$tokenId = $data['tokenId'];
	$nodeIds = $data['nodeIds'];
	
	//for debug_backtrace
	/*
	$tokenId = 'ABCDEQWFWEF';
	$nodeIds[] = '1234';
	$nodeIds[] = '5678';
	$nodeIds[] = '999';
	*/
	
	//delete original data;
	$query = "Delete from prefer_node "
			."Where token_id = '".$tokenId."' ";
	$db->Execute($query);
	
	//insert new nodeIds
	$nodeCount = count($nodeIds);
	for($i = 0; $i < $nodeCount; $i++){
		$query = "Insert into prefer_node "
				."Set token_id = '".$tokenId."', "
				."node_id = '".$nodeIds[$i]."' ";
		$db->Execute($query);
	}
}
	
	
?>