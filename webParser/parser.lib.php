<?PHP

/******** Function List ****************
getAndLoad($host, $path, $referer)
postAndLoad($host, $path, $data)

****************************************/


function getAndLoad($host, $path, $referer){
	$header = "";
	$header .= "GET ".$path." HTTP/1.1\r\n";
	$header .= "Host: ".$host."\r\n";
	if(strlen($referer) > 0){
		$header .= "Referer: ".$referer."\r\n\r\n";
	}
	$header .= "\r\n";
	
	$fp = fsockopen($host, 80);
	fputs($fp, $header);
	
	$result = ''; 
	// receive the results of the request
	while(!feof($fp)) {
		$result .= fgets($fp, 128);
	}
 
	// close the socket connection:
	fclose($fp);
	
	// split the result header from the content
	$result = explode("\r\n\r\n", $result, 2); 
	$header = isset($result[0]) ? $result[0] : '';
	$content = isset($result[1]) ? $result[1] : '';
 
	// return as array:
	return array($header, $content);
}

function getAndLoad2($host, $path, $data){
	//create cURL connection
	$curl_connection = curl_init("http://".$host.$path.'?'.$data);	
	
	//set options
	curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
	curl_setopt($curl_connection, CURLOPT_USERAGENT, 
	  "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
	curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, 1);
	//curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $data);
	$result = curl_exec($curl_connection);
	//show information regarding the request
	//print_r(curl_getinfo($curl_connection));
	//echo curl_errno($curl_connection) . '-' . curl_error($curl_connection);
	 
	//close the connection
	curl_close($curl_connection);
 	return $result;
	
}


function postAndLoad($host, $path, $data, $referer){
	$fp = fsockopen($host, 80);
	
	$header = "";
	$header .= "POST ".$path." HTTP/1.1\r\n";
	//$header .= "POST /tw/TimeTable/SearchResult HTTP/1.1\r\n";
	$header .= "Accept: */*\r\n";
	//$header .= "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8\r\n";
	$header .= "Accept-Language: zh-TW,zh;q=0.8,en-US;q=0.6,en;q=0.4\r\n";
	$header .= "Origin: http://www.thsrc.com.tw\r\n";
	$header .= "Referer: ".$referer."\r\n";
	$header .= "Content-Type: application/x-www-form-urlencoded\r\n";
	$header .= "Content-Length: ".strlen($data)."\r\n";
	$header .= "Accept-Encoding: gzip,deflate,sdch\r\n";
	$header .= "User-Agent: Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; GTB6.4; .NET CLR 2.0.50727; .NET CLR 3.0.04506.648; .NET CLR 3.5.21022; InfoPath.2; .NET CLR 3.0.4506.2152; .NET CLR 3.5.30729)\r\n";
	$header .= "Host: ".$host."\r\n";
	//$header .= "Pragma: no-cache\r\n\r\n";
	


																																																																																																																																																																				
	fputs($fp, $header);		
	fputs($fp, $data);
	
	$result = ''; 
	// receive the results of the request
	while(!feof($fp)) {
		$result .= fgets($fp, 128);
	}
 
	// close the socket connection:
	fclose($fp);
	
	// split the result header from the content
	$result = explode("\r\n\r\n", $result, 2);
 
	$header = isset($result[0]) ? $result[0] : '';
	$content = isset($result[1]) ? $result[1] : '';
 
	// return as array:
	return array($header, $content);
}


function calcTimeDuration($startTime, $endTime){
	$startTimeNums = split(":", $startTime);
	$startHour = intval($startTimeNums[0]);
	$startMinute = intval($startTimeNums[1]);
	$endTimeNums = split(":", $endTime);
	$endHour = intval($endTimeNums[0]);
	$endMinute = intval($endTimeNums[1]);
	
	$totalMinutes = 0;
	if($endHour < $startHour){	//跨天
		$endHour += 24;
	}
	$totalMinutes = ($endHour - $startHour) * 60 + ($endMinute - $startMinute);
	return floor($totalMinutes / 60).":".addzero($totalMinutes % 60);
}

function addzero($num){
	return ($num < 10)?"0".$num:"".$num;	
}

/*
function getInnerHTML($node) { 	//get innerHTML of a DOMNode
	$innerHTML= ''; 
	$children = $node->childNodes;
	foreach ($children as $child) { 
		$innerHTML .= $child->ownerDocument->saveXML($child); 
	}
	return $innerHTML; 
}
*/

//get innerHTML of a DOMNode
function getInnerHTML($element) 
{ 
    $innerHTML = ""; 
    $children = $element->childNodes; 
    foreach ($children as $child) 
    { 
        $tmp_dom = new DOMDocument(); 
        $tmp_dom->appendChild($tmp_dom->importNode($child, true)); 
        $innerHTML.=trim($tmp_dom->saveHTML()); 
    } 
    return $innerHTML; 
} 

//get attribute value of a DOMNode
function getAttribute($node, $attributeName){
	$attributeCount = $node->attributes->length;
	for($i = 0; $i < $attributeCount; $i++){
		if($node->attributes->item($i)->name == $attributeName){
			return $node->attributes->item($i)->value;
		}
	}
}


?>