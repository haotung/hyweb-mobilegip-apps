<?PHP
include_once('config.inc.php');
require_once 'Zend/Http/Client.php';
error_reporting(E_ERROR );

if (!empty($_SERVER['HTTP_CLIENT_IP']))
    $ip=$_SERVER['HTTP_CLIENT_IP'];
else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
    $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
else
    $ip=$_SERVER['REMOTE_ADDR'];

//echo 'received data:';
$data = json_decode(file_get_contents('php://input'), true);
//$data = json_decode('{"AppId": "GIP_TaiWater","targetDevices": "AllDevices","isSilent": true,"targetOS": [    "iOS",    "Android"],"message": "您訂閱或所在位置附近有台灣自來水公司停水通知","badge": 1,"forDevelopment": true,"additionalData": {    "zipcodes": ["304","238","901","239","640","652","238","238","701","334","368","950","433","804","633","655","636","635","632","634","633","655","636","635","632","634","717","510","600","324","510","625","727","852","947","265","364","540","811","300","338","710","503","411","204","401","709","845","604","338","806","600","407","351","946","404","333","621","427","966","510","243","606","411","269","407","310","426","351","806","334","320","201","813","411","522","514","522","514","709","732","612","320","825","944","433"    ]}}', true);
header('Content-Type: application/json; charset=utf-8');
$response['results'] = array();
$targetOS = $data['targetOS'];
for($i = 0; $i < count($targetOS); $i++){
	$result = array();
	$result['targetOS'] = $targetOS[$i];
	
	if($targetOS[$i] == 'iOS'){
		$query = "Insert into push_task set app_id = '".$data['AppId']."', OS = 'iOS', message = '"
				.addslashes($data['message'])."', for_development = ".(($data['forDevelopment'] == true)?1:0).", "
				."all_parameters = '".addslashes(file_get_contents('php://input'))."', "
				."IP = '".$ip."', time = '".date('Y-m-d H:i:s')."' ";
		//$result['log_query'] = $query;
		$db->Execute($query);
		$taskId = $db->Insert_ID();
		$sentCount = pushToiOS($result, $taskId);
		if($sentCount == null){
			$sentCount = 0;
		}
		$db->Execute('Update push_task set sent_count = '.$sentCount.' Where task_id = '.$taskId.' ');
	}
	else if($targetOS[$i] == 'Android'){
		$query = "Insert into push_task set app_id = '".$data['AppId']."', OS = 'Android', message = '"
				.addslashes($data['message'])."', for_development = ".(($data['forDevelopment'] == true)?1:0).", "
				."all_parameters = '".addslashes(file_get_contents('php://input'))."', "
				."IP = '".$ip."', time = '".date('Y-m-d H:i:s')."' ";
		$db->Execute($query);
		$taskId = $db->Insert_ID();
		$sentCount = pushToAndroid($result, $taskId);
		if($sentCount == null){
			$sentCount = 0;
		}
		$db->Execute('Update push_task set sent_count = '.$sentCount.' Where task_id = '.$taskId.' ');
		//$result['status'] = 'failed (not implemented)';
	}
	else if($targetOS[$i] == 'Windows Phone'){
		$result['status'] = 'failed (not implemented)';
	
	}
	else{
		$result['status'] = 'failed (unknown OS)';
	}
	
	$response['results'][] = $result;
}

function pushToAndroid(&$result, $taskId){
	global $db;
	global $data;
	$result['status'] = 'success';
	$AppId = $data['AppId'];
	$query = "Select gcm_key "
			."From app "
			."Where app_id = '".$AppId."' ";
	$rs = $db->Execute($query);
	$tmpTableName = '';
	$sentDeviceCount = 0;
	if($row = $rs->FetchRow()){
		$gcmKey = $row['gcm_key'];
		$targetDevices = $data['targetDevices'];
		if($targetDevices == 'AllDevices'){
			$query = "Select token_id, user_id "
					."From device "
					."Where app_id = '".$AppId."' "
					."And OS = 'Android' "
					."Order by device.update_time desc ";
		}
		else if($targetDevices == 'AllRegisteredAccounts'){
			$query = "Select token_id, user_id "
					."From device "
					."Where app_id = '".$AppId."' "
					."And OS = 'Android' "
					."And user_id > '' "
					."Order by device.update_time desc ";
		}
		else if($targetDevices == 'SpecifiedAccounts'){
			if($data['specAccounts'] != null){
				$tmpTableName = "temp".date('YmdHis').'_android';
				$query2 = "create table ".$tmpTableName." ("
						."`account` varchar(64)) "
						."ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ";
				//echo $query2;
				$db->Execute($query2);
				for($a = 0; $a < count($data['specAccounts']); $a++){
					if(strlen($data['specAccounts'][$a]) == 0){
						continue;
					}
					$query2 = "Insert into ".$tmpTableName." "
							."set account = '".$data['specAccounts'][$a]."' ";
					$db->Execute($query2);
				}
				
				$query = "Select token_id, device.user_id "
					."From device, ".$tmpTableName." "
					."Where app_id = '".$AppId."' "
					."And OS = 'Android' "
					."And device.user_id = ".$tmpTableName.".account "
					."Order by device.update_time desc ";
			}
		}
		
		
		if(strlen($query) > 0){
			//echo $query;
			/*
			if($data['isSilent']){
				$payload['aps'] = array('content-available' => 1, 'badge'=>$data['badge'], 'sound'=>'', 'message' => $data['message'], 'additionalData' => $data['additionalData']);
			}
			else{
				$payload['aps'] = array('alert'=>$data['message'], 'badge'=>$data['badge'], 'sound'=>'');
			}
			*/
			//$payload['aps'] = array('alert'=>'台水test', 'badge'=>3, 'sound'=>'');
			//$output = json_encode($payload);
			$rs = $db->Execute($query);
			//$deviceTokens = array();
			$queryLogs = array();
			$tokens = array();
			while($row = $rs->FetchRow()){
			
				$queryLog = "Insert into push_log set task_id = ".$taskId.", token_id = '".$row['token_id']."', account = '".$row['user_id']."' ";
				array_push($queryLogs, $queryLog);
				array_push($tokens, $row['token_id']);
				if(count($tokens) >= 1000){
					postToGoogleGCM($gcmKey, $tokens, $data, $result);
					$sentDeviceCount += count($tokens);
					$tokens = array();
				}
			}
			if(count($tokens) > 0){
				postToGoogleGCM($gcmKey, $tokens, $data, $result);
				$sentDeviceCount += count($tokens);
				
				for($i = 0; $i < count($queryLogs); $i++){
					$db->Execute($queryLogs[$i]);
				}
				
			}
			
			$result['sentDeviceCount'] = $sentDeviceCount;
		}
	}
	else{
		$result['status'] = 'failed (unknown App)';
		$response['results'][] = $result;
	}
	if(strlen($tmpTableName) > 0){
		$dropQuery = 'Drop table '.$tmpTableName;
		$db->Execute($dropQuery);
	}
	return $sentDeviceCount;
}

function postToGoogleGCM($gcmKey, $tokenIds, &$data, &$result){
	// Set POST variables
    $url = 'https://android.googleapis.com/gcm/send';
 
    // 要發送的訊息內容
    // 例如我要發送 message, campaigndate, title, description 四樣資訊
    // 就將這 4 個組成陣列
    // 您可依您自己的需求修改
    $fields = array('registration_ids'  => $tokenIds,
                    'data'              => array( 
												'message' => $data['message'],
												'additionalData' => $data['additionalData']
                                                )
                );
 
    $headers = array('Content-Type: application/json',
                     'Authorization: key='.$gcmKey
                    );
	/*	
	$client = new Zend_Http_Client($url);
	$client->setMethod('POST');
	$client->setHeaders($headers);
	$client->setRawData(json_encode($data));
	$request = $client->request('POST');
	
	$result['tokenIds'] = $tokenIds;
	$result['postResults'][] = json_decode($request->getBody(),true);
	*/
			
	// Open connection
    $ch = curl_init();
    // Set the url, number of POST vars, POST data
    curl_setopt( $ch, CURLOPT_URL, $url );
    curl_setopt( $ch, CURLOPT_POST, true );
    curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
	//curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false);
	//curl_setopt ($ch, CURLOPT_CAINFO, dirname(__FILE__).'\\cert\\cacert.cer');
	
    // 送出 post, 並接收回應, 存入 $result
    $execResult = curl_exec($ch);
	if ($execResult=== FALSE) {
	  echo('error curl: ' . curl_error($ch));
	}
	$result['gcmKey'] = $gcmKey;
	$result['tokenIds'] = $tokenIds;
	$result['postResults'][] = json_decode($execResult,true);
	// Close connection
    curl_close($ch);
    
	// GCM end -------
    unset($tokenIds);
	
}

function pushToiOS(&$result, $taskId){
	global $db;
	global $data;
	$result['status'] = 'success';
	$AppId = $data['AppId'];
	$query = "Select iOS_dev_cert, iOS_dev_pass, iOS_prdt_cert, iOS_prdt_pass "
			."From app "
			."Where app_id = '".$AppId."' ";
	$rs = $db->Execute($query);
	$sentDeviceCount = 0;
	if($row = $rs->FetchRow()){
		$certFileName = '';			
		$certPassPhrase = '';
		$apnsHost = 'ssl://gateway.push.apple.com:2195';
		$devDeviceCondition = '';
		if($data['forDevelopment']){
			$certFileName = $row['iOS_dev_cert'];
			$certPassPhrase = $row['iOS_dev_pass'];
			$apnsHost = 'ssl://gateway.sandbox.push.apple.com:2195';
			$devDeviceCondition = ' and is_development = 1 ';
		}
		else{
			$certFileName = $row['iOS_prdt_cert'];
			$certPassPhrase = $row['iOS_prdt_pass'];
		}
		if(strlen($certFileName) == 0){
			$result['status'] = "failed (certification file haven't been set)";
			$response['results'][] = $result;
			return 0;
		}
		
		if(!file_exists('cert/'.$certFileName)){
			$result['status'] = "failed (certification file doesn't exist)";
			$response['results'][] = $result;
			return 0;
		}
		
		//建立SSL connection
		$streamContext = stream_context_create();
		stream_context_set_option($streamContext, 'ssl', 'local_cert', dirname(__FILE__).'\\cert\\'.$certFileName);
		stream_context_set_option($streamContext, 'ssl', 'passphrase', $certPassPhrase);
		$fp = stream_socket_client(
			$apnsHost, $err,
			$errstr, 20, STREAM_CLIENT_CONNECT, $streamContext
		);
		if (!$fp){
			$result['status'] = 'failed (Failed to connect)';	
			$response['results'][] = $result;			
			return;
		}
		
		//撈tokens傳送
		$query = '';
		$tmpTableName = '';
		$targetDevices = $data['targetDevices'];
		if($targetDevices == 'AllDevices'){
			$query = "Select token_id, user_id "
					."From device "
					."Where app_id = '".$AppId."' "
					."And OS = 'iOS' "
					.$devDeviceCondition
					."Order by device.update_time desc ";
		}
		else if($targetDevices == 'AllRegisteredAccounts'){
			$query = "Select token_id, user_id "
					."From device "
					."Where app_id = '".$AppId."' "
					."And OS = 'iOS' "
					."And user_id > '' "
					.$devDeviceCondition
					."Order by device.update_time desc ";
		}
		else if($targetDevices == 'SpecifiedAccounts'){
			if($data['specAccounts'] != null){
				$tmpTableName = "temp".date('YmdHis');
				$query2 = "create table ".$tmpTableName." ("
						."`account` varchar(64)) "
						."ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ";
				//echo $query2;
				$db->Execute($query2);
				for($a = 0; $a < count($data['specAccounts']); $a++){
					if(strlen($data['specAccounts'][$a]) == 0){
						continue;
					}
					$query2 = "Insert into ".$tmpTableName." "
							."set account = '".$data['specAccounts'][$a]."' ";
					$db->Execute($query2);
				}
				
				$query = "Select token_id, device.user_id "
					."From device, ".$tmpTableName." "
					."Where app_id = '".$AppId."' "
					."And OS = 'iOS' "
					."And device.user_id = ".$tmpTableName.".account "
					.$devDeviceCondition
					."Order by device.update_time desc ";
			}
		}
		
		if(strlen($query) > 0){
			//echo $query;
			if($data['isSilent']){
				$payload['aps'] = array('content-available' => 1, 'badge'=>$data['badge'], 'sound'=>'', 'message' => $data['message'], 'additionalData' => $data['additionalData']);
			}
			else{
				$payload['aps'] = array('content-available' => 1, 'alert'=>$data['message'], 'badge'=>$data['badge'], 'sound'=>'');
			}
			
			//$payload['aps'] = array('alert'=>'台水test', 'badge'=>3, 'sound'=>'');
			$output = json_encode($payload);
			$rs = $db->Execute($query);
			$queryLogs = array();
			$deviceTokens = array();
			while($row = $rs->FetchRow()){
				$deviceToken = str_replace(' ', '', $row['token_id']);
				$queryLog = "Insert into push_log set task_id = ".$taskId.", token_id = '".$deviceToken."', account = '".$row['user_id']."' ";
				array_push($queryLogs, $queryLog);
				$deviceTokens[] = $deviceToken;
				$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($output)) . $output;

				// Send it to the server
				$sendResult = fwrite($fp, $msg, strlen($msg));
				if ($sendResult){
					$sentDeviceCount++;
				}
			}
			if($sentDeviceCount > 0){
				for($i = 0; $i < count($queryLogs); $i++){
					$db->Execute($queryLogs[$i]);
				}
			}
		}
		$result['sentDeviceCount'] = $sentDeviceCount;
		$result['payload'] = $payload;
		if($sentDeviceCount < 10){
			$result['deviceTokens'] = $deviceTokens;
		}
		$result['tmpTableName'] = $tmpTableName;
		if(strlen($tmpTableName) > 0){
			sleep(1);
			$query = "Drop table ".$tmpTableName." ";
			$result['dropTableQuery'] = $query;
			$db->Execute($query);
		}
	}
	else{
		$result['status'] = 'failed (unknown App)';
		$response['results'][] = $result;
	}
	return $sentDeviceCount;
}





echo json_encode($response);

?>