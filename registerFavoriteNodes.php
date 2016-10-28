<?PHP
include_once('config.inc.php');
$deviceToken = $_POST['deviceToken'];
$nodes = $_POST['nodes'];

if(strlen($deviceToken) > 0 && $nodes != null){
	$query = "Delete from favorite_node "
			."Where token_id = '".$deviceToken."' ";
	$db->Execute($query);
	
	$nodeCount = count($nodes);
	for($i = 0; $i < $nodeCount; $i++){
		$nodeId = $nodes[$i];
		$query = "Insert into favorite_node "
				."Set token_id = '".$deviceToken."', "
				."node_id = '".$nodeId."' ";
		$db->Execute($query);
	}
	echo 'OK:deviceToken = '.$deviceToken."\nnodes = ".$nodes;
}
else{
	echo 'error:deviceToken = '.$deviceToken."\nnodes = ".$nodes;	
}

?>