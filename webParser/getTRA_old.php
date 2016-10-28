<?PHP
include_once("parser.lib.php");
header('Content-type: application/json; charset=utf-8');
//header('Content-type: text/html; charset=utf-8');
error_reporting(0);
//http://163.29.3.97/mobile_en/result.jsp?from2=1319&to2=1305&carclass=2&Date=2013%2F12%2F01&sTime=00%3A00&eTime=23%3A59&Dep=false

$getParamsLength = count($_GET);

$params = '';
foreach($_GET as $key => $value){
	if(strlen($params) > 0){
		$params .= '&';
	}
	$params .= $key.'='.urlencode($value);
}


$serviceURL = "/mobile_en/result.jsp";
if(strlen($params) == 0){
	$params = '?from1=1008&to2=1319&carclass=2&Date=2013%2F11%2F10&sTime=00%3A00&eTime=23%3A59&Dep=true';
}

$referer = "";
$host = "163.29.3.97";


$result = getAndLoad2($host,$serviceURL,$params);



$jsonData = array("result" => array());

$jsonData["params"] = $params;
$jsonData["date"] = $_GET['Date'];
$extractStart = strpos($result, '<body');
$extractEnd = strpos($result, '</body>', $extractStart) + 8;
$extractedHTML = substr($result, $extractStart, $extractEnd - $extractStart + 1);


$doc = new DOMDocument();
$doc->recover = true;
$doc->strictErrorChecking = false;
$doc->loadHTML($extractedHTML);
$tables = $doc->getElementsByTagName('table');
$tableCount = $tables->length;
if($tableCount >= 2){
	$mainTable = $tables->item(1);
	$trNodes = $mainTable->getElementsByTagName('tr');
	for($r = 0; $r < $trNodes->length; $r++){
		$trNode = $trNodes->item($r);
		$trClass = $trNode->getAttribute('class');
		
		if($trClass == "odd" || $trClass == "even"){			
			$trainId = "";
			$departureTime = "";
			$arrivalTime = "";
			$trainClass = "";
			
			$tdNodes = $trNode->getElementsByTagName('td');
			//trainID
			$trainIDLink = $tdNodes->item(1)->getElementsByTagName('a')->item(0);
			$trainIDHref = $trainIDLink->getAttribute('href');
			$trainId = $trainIDLink->textContent;
			$trainClass = str_replace('+', ' ', getTrainClassFromHrefParams($trainIDHref));
			//deptature
			$departureTime = $tdNodes->item(2)->getElementsByTagName('span')->item(0)->textContent;
			//arrive
			$arrivalTime = $tdNodes->item(3)->getElementsByTagName('span')->item(0)->textContent;
			
			$duration = calcTimeDuration($departureTime, $arrivalTime);
			$jsonData["result"][] = array(	"trainId" => $trainId,
											"trainClass" => $trainClass,
											"departureTime" => $departureTime,
											"arrivalTime" => $arrivalTime,
											"duration" => $duration
										);
		}
	}
}



echo json_encode($jsonData);


function getTrainClassFromHrefParams($hrefParams){
	$classStart = strpos($hrefParams, '&class=') + 7;
	$classEnd = strpos($hrefParams, '&date=', $classStart) - 1;
	return substr($hrefParams, $classStart, $classEnd - $classStart + 1);
	
}



?>