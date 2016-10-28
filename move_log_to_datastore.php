<?PHP
include_once('config.inc.php');
require_once 'datastore_config.php';
require_once 'GoogleDatastoreService.php';
use google\appengine\api\users\User;
use google\appengine\api\users\UserService;

ini_set('memory_limit', '1024M');
$DS = new GoogleDatastoreService($google_api_config);

$allMovingEntities = 0;
while($allMovingEntities < 30){
	$query = "Select * "
			."From `push_log_transfer_queue` "
			."Where transfer_finish_time = '0000-00-00 00:00:00' "
			."And task_finish_time <= '".date('Y-m-d H:i:s', strtotime('-2 minute'))."' "
			."And transferring = 0 "
			."Order by task_finish_time limit 0, 1";
	$rs = $db->Execute($query);
	if($row = $rs->FetchRow()){
		$taskAutoId = $row['task_auto_id'];
		echo $taskAutoId."<br/>\n";
		syslog(LOG_DEBUG, 'start task '.$taskAutoId.' (queued at '.$row['task_finish_time'].')');
		$query = "Update push_log_transfer_queue set transferring = 1 Where task_auto_id = ".$taskAutoId;
		$db->Execute($query);
		
		$query = "SELECT * FROM `push_log_tmp` where task_auto_id = ".$taskAutoId." ";
		$rs = $db->Execute($query);
		$count = 0;
		$tokenEntities = array();
		while($row = $rs->FetchRow()){
			$kvs = array();
			$kvs[] = array('key' => 'task_auto_id', 'value' => $taskAutoId, 'type' => PropertyType::Integer);
			$kvs[] = array('key' => 'token_id', 'value' => $row['token_id'], 'type' => PropertyType::String);
			$kvs[] = array('key' => 'user_id', 'value' => $row['account'], 'type' => PropertyType::String);
				
			$tokenEntities[] = $DS->buildEntity('push_log', $kvs);
			$kvs = null;
			$count++;
			if($count >= 500){
				$DS->saveEntities($tokenEntities, true);
				$allMovingEntities += count($tokenEntities);
				$tokenEntities = array();
				echo 'move '.$count." entities<br/>\n";
				$count = 0;
			}
		}
		if($count > 0){
			$DS->saveEntities($tokenEntities, true);
			$allMovingEntities += count($tokenEntities);
			$tokenEntities = array();
			echo 'move '.$count." entities<br/>\n";
			$count = 0;
		}
		$query = "Update push_log_transfer_queue set transfer_finish_time = '".date("Y-m-d H:i:s")."', transferring = 0 Where task_auto_id = ".$taskAutoId;
		$db->Execute($query);
		
		$query = "Delete from push_log_tmp where task_auto_id = ".$taskAutoId." ";
		$db->Execute($query);
		syslog(LOG_DEBUG, 'transfer task finished at '.date("Y-m-d H:i:s"));
		
	}
	else{
		syslog(LOG_DEBUG, 'no entry to transfer');
		break;
	}
}

$db->Close();
?>