<?PHP
include_once("parser.lib.php");
header('Content-type: application/json; charset=utf-8');
//header('Content-type: text/html; charset=utf-8');
error_reporting(0);

$getParamsLength = count($_GET);

$params = '';
foreach($_GET as $key => $value){
	if(strlen($params) > 0){
		$params .= '&';
	}
	//2013-12-28改版
	if(strpos($key, 'from') === 0){
		if(strlen($value) > 0){
			$params .= 'fromstation='.urlencode($value);
		}	
	}
	else if(strpos($key, 'to') === 0){
		if(strlen($value) > 0){
			$params .= 'tostation='.urlencode($value);
		}	
	}
	else if($key == 'Date'){
		$params .= 'searchdate='.urlencode($value);
	}
	else if($key == 'sTime'){
		$params .= 'fromtime='.urlencode(str_replace(':', '', $value));
	}
	else if($key == 'eTime'){
		$params .= 'totime='.urlencode(str_replace(':', '', $value));
	}
}
//2013-12-28改版


$serviceURL = "/twrail/mobile/e_TimeTableSearchResult.aspx";
if(strlen($params) == 0){
	$params = 'language=eng&searchdate=2014/01/10&trainclass=2&fromstation=1008&tostation=1319&fromtime=1300&totime=2359';
}
else{
	$params	= 'language=eng&trainclass=2&'.$params;
}
//http://twtraffic.tra.gov.tw/twrail/mobile/e_TimeTableSearchResult.aspx?language=eng&searchdate=2013/12/28&trainclass=2
//	&fromstation=1008&tostation=1319&fromtime=1300&totime=2359
$referer = "";
$host = "twtraffic.tra.gov.tw";


$result = getAndLoad2($host,$serviceURL,$params);



$jsonData = array("result" => array());

$jsonData["params"] = $params;
$jsonData["date"] = $_GET['Date'];
$extractStart = strpos($result, '<script>') + 8;
$extractEnd = strpos($result, '</script></form>');
$extractedScript = substr($result, $extractStart, $extractEnd - $extractStart);
$extractedScript = str_replace('TRSearchResult.push(\'', '', $extractedScript);
$extractedScript = str_replace('\')', '', $extractedScript);
$extractedValues = explode(";", $extractedScript);
//echo $extractedScript."<br/>\n";
$trainCount = floor(count($extractedValues) / 8);
for($i = 0; $i < $trainCount; $i++){
	$trainTypeId = $extractedValues[$i * 8 + 5];
	//echo $trainTypeId.",";
	$trainClass = 'Local Train';
	if($trainTypeId == '1100' || $trainTypeId == '1101' || $trainTypeId == '1102'){
		$trainClass = 'Tze-Chiang';
	}
	else if($trainTypeId == '1110'){
		$trainClass = 'Chu-Kuang';
	}
	$trainId = $extractedValues[$i * 8 + 1];
	$departureTime = $extractedValues[$i * 8 + 2];
	$arrivalTime = $extractedValues[$i * 8 + 3];
	$duration = calcTimeDuration($departureTime, $arrivalTime);
	//echo $extractedValues[$i * 8]." ".$extractedValues[$i * 8 + 1]." ".$extractedValues[$i * 8 + 5]." ".$trainClass."<br/>\n";
	$jsonData["result"][] = array(	"trainId" => $trainId,
											"trainClass" => $trainClass,
											"departureTime" => $departureTime,
											"arrivalTime" => $arrivalTime,
											"duration" => $duration
										);
}


echo json_encode($jsonData);
/*

TRSearchResult.push('Local Train');
TRSearchResult.push('2357');
TRSearchResult.push('16:48');
TRSearchResult.push('19:58');
TRSearchResult.push('03h 10m');
TRSearchResult.push('1131');
TRSearchResult.push('N');
TRSearchResult.push('');
*/



function getTrainClassFromHrefParams($hrefParams){
	$classStart = strpos($hrefParams, '&class=') + 7;
	$classEnd = strpos($hrefParams, '&date=', $classStart) - 1;
	return substr($hrefParams, $classStart, $classEnd - $classStart + 1);
	
}



?>