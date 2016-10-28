<?PHP
include_once('config.inc.php');

header('Content-Type: application/json; charset=utf-8');
$query = "Select app.app_id, android_package_name, itunes_url, l1.last_update_time as android_update_time, l2.last_update_time as ios_update_time "
		."from app "
		."left join last_update as l1 "
		."on l1.app_id = app.app_id "
		."and l1.update_item = 'android_latest_version' "
		."left join last_update as l2 "
		."on l2.app_id = app.app_id "
		."and l2.update_item = 'ios_latest_version' "
		."where android_package_name > '' Or itunes_url > '' ";
		
		
$rs = $db->Execute($query);
while($row = $rs->FetchRow()){
	$appId = $row['app_id'];
	$androidPackageName = $row['android_package_name'];
	$itunesUrl = $row['itunes_url'];
	if(strlen($androidPackageName) && needUpdate($row['android_update_time'])){
		$retryCount = 0;
		$version = '';
		while($retryCount < 5 && strlen($version) === 0){
			$version = fetchLatestVersionOnGooglePlay($appId, $androidPackageName);
			$retryCount++;
			echo 'retry '.$appId." android<br/>\n";
		}
	}
	if(strlen($itunesUrl) && needUpdate($row['ios_update_time'])){
		$retryCount = 0;
		$version = '';
		while($retryCount < 5 && strlen($version) === 0){
			$version = fetchLatestVersionOnAppStore($appId, $itunesUrl);
			$retryCount++;
			echo 'retry '.$appId." iOS<br/>\n";
			sleep(1);
		}
	}
}

function needUpdate($lastUpdateTime){
	$ourHourAgo = date('Y-m-d H:i:s', strtotime('-1 hour'));
	if(is_null($lastUpdateTime)){
		return true;
	}
	else{
		return ($lastUpdateTime < $ourHourAgo);
	}
}



function fetchLatestVersionOnGooglePlay($appId, $packageName){
	global $db;
	$url = 'https://play.google.com/store/apps/details?id='.$packageName;
	$context = [
	  'http' => [
	    'method' => 'GET'
	  ]
	];
	$context = stream_context_create($context);
	echo currentMicroTime().' fetching '.$packageName."<br/>\n";
	$result = file_get_contents($url, false, $context);
	echo currentMicroTime().' '.$packageName." fetched<br/>\n";
	$doc = new DOMDocument();
	$doc->loadHTML($result);
	echo currentMicroTime().' '.$packageName." parsed<br/>\n";
	$version = '';
	foreach($doc->getElementsByTagName('div') as $divTag){
		//echo $divTag->getAttribute('itemprop')."<br/>\n";
		if(strcmp($divTag->getAttribute('itemprop'), 'softwareVersion') === 0){
			$version = trim($divTag->textContent);	
			break;
		}
	}
	if(strlen($version) > 0){
		$query = "Update app "
				."Set android_latest_version = '".$version."' "
				."Where app_id = '".$appId."' ";
		$db->Execute($query);	
		echo currentMicroTime().' '.$packageName." verion gotten.<br/>\n";
		echo currentMicroTime().' '.$packageName." verion ".$version." saved.<br/>\n";
		
		$query = "Replace into last_update "
				."(app_id, update_item, last_update_time) "
				."values ('".$appId."', 'android_latest_version', '".date('Y-m-d H:i:s')."' )";
		$db->Execute($query);
		//echo $query."<br/>\n";
		
		
	}
	
	
	return $version;
}


function fetchLatestVersionOnAppStore($appId, $iTunesUrl){
	global $db;
	
	$context = [
	  'https' => [
	    'method' => 'GET'
	  ]
	];
	$context = stream_context_create($context);
	echo currentMicroTime().' fetching '.$iTunesUrl."<br/>\n";
	$result = file_get_contents($iTunesUrl, false, $context);
	//echo $result;
	//$result = 'afojiaweoifj aowei f<body aaa=bbb> owei foiwej foiwej fowjf wei f</body> wefojweofi';
	$bodyStart = strpos($result, '<body');
	$bodyEnd = strpos($result, '</body');
	if($bodyStart !== false && $bodyEnd !== false){
		$result = substr($result, $bodyStart, $bodyEnd + 7 - $bodyStart);
		//echo $result."<br/>\n";
		
	}
	
	
	echo currentMicroTime().' '.$appId." fetched<br/>\n";
	$doc = new DOMDocument();
	$doc->loadHTML($result);
	echo currentMicroTime().' '.$appId." parsed<br/>\n";
	$version = '';
	foreach($doc->getElementsByTagName('div') as $divTag){
		//echo "div class = ".$divTag->getAttribute('class')."<br/>\n";
		$divClasses = $divTag->getAttribute('class');
		if(strpos($divClasses, 'product') !== false){
			//echo "div tag:class = ".$divClasses."<br/>\n";
			
			$childNodes = $divTag->childNodes;
			
			foreach($childNodes as $childNode){	
				//echo '　　'.$childNode->nodeName."<br/>\n";
				if(strcmp($childNode->nodeName, 'ul') === 0){
					//echo "in ul<br/>\n";
					$listItemNodes = $childNode->childNodes;
					foreach($listItemNodes as $listItemNode){
						//echo 'listItemNode name:'.$listItemNode->nodeName."<br/>\n";
						if(strcmp($listItemNode->nodeName, 'li') === 0){
							//echo "li <br/>\n";
							$innerDoc = new DOMDocument();
							$innerDoc->loadHTML('<li>'.DOMinnerHTML($listItemNode).'</li>');
							foreach($innerDoc->getElementsByTagName('span') as $spanTag){
								if(strcmp($spanTag->getAttribute('itemprop'), 'softwareVersion') === 0){
									$version = $spanTag->textContent;
									break;
								}
							}
									
						}
					}
					
				}
			}
		}
	}
	if(strlen($version) > 0){
		echo currentMicroTime().' '.$appId." verion gotten.<br/>\n";
		$query = "Update app "
				."Set ios_latest_version = '".$version."' "
				."Where app_id = '".$appId."' ";
		$db->Execute($query);
		echo currentMicroTime().' '.$appId." verion ".$version." saved.<br/>\n";
		
		
		
		$query = "Replace into last_update "
				."(app_id, update_item, last_update_time) "
				."values ('".$appId."', 'ios_latest_version', '".date('Y-m-d H:i:s')."' )";
		//echo $query."<br/>\n";
		$db->Execute($query);
		
	}
	return $version;
}

function currentMicroTime(){
	$t = microtime(true);
	$micro = sprintf("%06d",($t - floor($t)) * 1000000);
	$d = new DateTime( date('Y-m-d H:i:s.'.$micro, $t) );
	return $d->format('H:i:s.u');	
}


function DOMinnerHTML(DOMNode $element) 
{ 
    $innerHTML = ""; 
    $children  = $element->childNodes;

    foreach ($children as $child) 
    { 
        $innerHTML .= $element->ownerDocument->saveHTML($child);
    }

    return $innerHTML; 
} 

$db->Close();

?>