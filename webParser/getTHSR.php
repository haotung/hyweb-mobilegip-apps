<?PHP
include_once("parser.lib.php");
header('Content-Type: application/json; charset=utf-8');
//header( 'Content-type: text/html; charset=utf-8' );


if(array_key_exists('from', $_GET)){
	$from = $_GET['from'];
	$to = $_GET['to'];
	$sDate = $_GET['sDate'];
	$TimeTable = $_GET['TimeTable'];
	$FromOrDest = $_GET['FromOrDest'];
}
else{ 
	$from = "1";
	$to = "5";
	$sDate = "2015/06/30";
	$TimeTable = "18:00";
	$FromOrDest = "From";

}
$stationCode = Array();
$stationCode[1] = '977abb69-413a-4ccf-a109-0272c24fd490';
$stationCode[2] = 'e6e26e66-7dc1-458f-b2f3-71ce65fdc95f';
$stationCode[3] = 'fbd828d8-b1da-4b06-a3bd-680cdca4d2cd';
$stationCode[4] = 'a7a04c89-900b-4798-95a3-c01c455622f4';
$stationCode[5] = '3301e395-46b8-47aa-aa37-139e15708779';
$stationCode[6] = '60831846-f0e4-47f6-9b5b-46323ebdcef7';
$stationCode[7] = '9c5ac6ca-ec89-48f8-aab0-41b738cb1814';
$stationCode[8] = 'f2519629-5973-4d08-913b-479cce78a356';



//$serviceURL = "/thsrcPDA/e_search_results.asp";
$serviceURL = "/tw/TimeTable/SearchResult";
$params = "from=".$from."&to=".$to."&sDate=".urlencode($sDate)."&TimeTable=".urlencode($TimeTable)."&FromOrDest=".$FromOrDest;
$referer = "http://www.thsrc.com.tw/thsrcPDA/e_index.asp";
$referer = "http://www.thsrc.com.tw/tw/TimeTable/SearchResult";
$host = "www.thsrc.com.tw";


$postData = "StartStation=".$stationCode[intval($from)]
		.	"&EndStation=".$stationCode[intval($to)]
		.	"&SearchDate=".urlencode($sDate)
		.	"&SearchTime=".urlencode($TimeTable)
		.	"&SearchWay=".(($FromOrDest == "From")?'DepartureInMandarin':'ArrivalInMandarin')
		.	"&RestTime="
		.	"&EarlyOrLater=";


//ob_flush();
//flush();


$result = postAndLoad2($host, $serviceURL, $postData);
//ob_flush();
//flush();

//echo $result;


$jsonData = array("date" => $sDate, "result" => array());





$extractStart = strpos($result, '<section class="result_table"');
$extractEnd = strpos($result, '</section><!--result_table-->', $extractStart + 10) + 5;
$extractedHTML = substr($result, $extractStart, $extractEnd - $extractStart + 5);

//echo $extractedHTML;



//$extractStart = strpos($extractedHTML, '<table');
//$extractEnd = strpos($extractedHTML, '</table>', $extractStart + 10) + 8;
//$extractedHTML = substr($extractedHTML, $extractStart, $extractEnd - $extractStart + 1);
$doc = new DOMDocument();

error_reporting(0);

$doc->loadHTML($extractedHTML);
$tables = $doc->getElementsByTagName('table');
$tableCount = $tables->length;
//echo $rowCount;
for($i = 0; $i < $tableCount; $i++){
	$trainId = "";
	$departureTime = "";
	$arrivalTime = "";
	$tableClass = $tables->item($i)->getAttribute('class');
	if($tableClass == 'touch_table'){
		$tdNodes = $tables->item($i)->childNodes->item(0)->childNodes;
	
		foreach($tdNodes as $key => $node){
			//echo $node->nodeName."<br/>\n";
			if(strtolower($node->nodeName == "td")){
				$tdChildNodes = $node->childNodes;
				if($key == 0){	//include train ID
					foreach($tdChildNodes as $contentNode){
						if(strtolower($contentNode->nodeName) == "a"){
							$trainId = $contentNode->textContent;
						}
					}
				}
				else if($key == 2){
					$departureTime = $node->textContent;
				}
				else if($key == 4){
					$arrivalTime = $node->textContent;
				}
			}
		}
		$duration = calcTimeDuration($departureTime, $arrivalTime);
		$jsonData["result"][] = array(	"trainId" => $trainId,
										"departureTime" => $departureTime,
										"arrivalTime" => $arrivalTime,
										"duration" => $duration
									);	
		
	}
	
}



echo json_encode($jsonData);

//echo $extractedHTML;


/*


*/

function postAndLoad2($host, $path, $data){
	//create cURL connection
	//$curl_connection = curl_init('http://www.thsrc.com.tw/tw/TimeTable/SearchResult');
	$curl_connection = curl_init('http://'.$host.$path);
	//set options
	curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
	curl_setopt($curl_connection, CURLOPT_USERAGENT, 
	  "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
	curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
	//curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
	//curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $data);
	$result = curl_exec($curl_connection);
	//show information regarding the request
	//print_r(curl_getinfo($curl_connection));
	//echo curl_errno($curl_connection) . '-' . curl_error($curl_connection);
	 
	//close the connection
	curl_close($curl_connection);
 	return $result;
	
}

?>