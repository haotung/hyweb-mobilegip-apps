<?PHP
include_once('config.inc.php');
require_once 'datastore_config.php';
require_once 'GoogleDatastoreService.php';
$DS = new GoogleDatastoreService($google_api_config);


$query = "SELECT task_auto_id, count(*) FROM `push_subtask` group by task_auto_id";
$taskAutoIds = array();
$rs = $db->Execute($query);
while($row = $rs->FetchRow()){
	$taskAutoIds[] = $row['task_auto_id'];	
}

$idCount = count($taskAutoIds);
$fifteenMinutesAgo = strtotime('-90 minutes');
echo "90 mins ago:".date("Y-m-d H:i:s", $fifteenMinutesAgo)."<br/>\n";
$deadlineTimeStr = date("Y-m-d H:i:s", $fifteenMinutesAgo);
$killedTasks = 0;
for($i = 0; $i < $idCount; $i++){
	$taskAutoId = $taskAutoIds[$i];
	
	
	$gql = "SELECT * FROM push_task where  __key__ = KEY(push_task, ".$taskAutoId.")";	
	$entities = $DS->queryEntitiesWithGQL($gql);
	if(count($entities) > 0){
		$entity = $entities[0];
		
		
		$entityRow = $DS->entityJSONToRowFormat($entity);
		
		
		$time = strtotime($entityRow['time']);
		$timeStr = date('Y-m-d H:i:s', $time);
		echo $taskAutoId.' started at '.$timeStr." ".strcmp($timeStr, $deadlineTimeStr)." <br/>\n";
		
		if(strcmp($timeStr, $deadlineTimeStr) < 0){
			$killedTasks++;
			$query = "Select task_auto_id, subtask_id "
					."From push_subtask "
					."Where task_auto_id = ".$taskAutoId." "
					."Order by subtask_id ";
			 
			$rs = $db->Execute($query);
			while($row = $rs->FetchRow()){
				$subTaskId = $row['subtask_id'];
				$storePath = 'gs://hyweb-mobilegip-apps/push_subtask_tmp/'.$taskAutoId.'_'.$subTaskId.'.token';
				if(file_exists($storePath)){
					unlink($storePath);
					echo $taskAutoId.'_'.$subTaskId.".token has been deleted.<br/>\n";
				}
			}
			
			$query = "Delete from push_subtask Where task_auto_id = ".$taskAutoId;
			$db->Execute($query);
			if($killedTasks >= 5){
				break;	
			}
		}
	}
	
}


$dir = dir("gs://hyweb-mobilegip-apps/push_subtask_tmp");

//列出 images 目录中的文件
$fileCount = 0;
$preTaskAutoId = '';
$shouldBeDeleted = false;
while (($file = $dir->read()) !== false)
{
	$taskAutoId = substr($file, 0, 16);
	if(strcmp($taskAutoId, $preTaskAutoId) != 0){
		$query = "Select * from push_subtask Where task_auto_id = ".$taskAutoId;
		$rs = $db->Execute($query);
		if($row = $rs->FetchRow()){
			$shouldBeDeleted = false;	
			echo ">>>> ".$taskAutoId." should not be deleted.<br/>\n";
		}
		else{
			$shouldBeDeleted = true;
			echo ">>>> ".$taskAutoId." should be deleted.<br/>\n"; 
		}
	}
	
	if($shouldBeDeleted){
		$storePath = 'gs://hyweb-mobilegip-apps/push_subtask_tmp/'.$file;
		unlink($storePath);
		echo "delete filename: " . $file . " (".$taskAutoId.")<br />";
	}
	$preTaskAutoId = $taskAutoId;
	$fileCount++;
}


$dir->close();


?>