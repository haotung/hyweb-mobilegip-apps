<?PHP
include_once("parser.lib.php");
header('Content-type: application/json; charset=utf-8');
//header('Content-type: text/html; charset=utf-8');


/*
url: 'http://citybus.taichung.gov.tw/APIs/GetRouteAndProvider.aspx',
        data: { 'RouteName': 'AllGet','Lang': 'En' },
        type: "POST",
*/

$url = 'http://citybus.taichung.gov.tw/APIs/GetRouteAndProvider.aspx';
$params = 'RouteName=AllGet&Lang=En';
$ch = curl_init($url);

curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);

curl_close($ch);

//echo $result;

$routeLines = explode("#", $result);
$jsonData = array("result" => array());
$preLineNum = "";
$preLineDesc = "";
for($i = 0; $i < count($routeLines); $i++){
	$item = explode('@', $routeLines[$i]);
	$lineNum = $item[1];
	$lineDesc = $item[2];
	//$shuttlePos = strpos($lineNum, 'Shuttle');
	if(!isPureNumberStr($lineNum)){
		continue;
	}
	if(($lineNum == $preLineNum) && ($lineDesc == $preLineDesc)){
		continue;	
	}
	$terminals = explode(' - ', $lineDesc);
	$jsonData["result"][] = array(
		"lineNum" => $lineNum,
		"lineDesc" => $lineDesc,
		"startStop" => $terminals[0],
		"endStop" => $terminals[1]
	);
	$preLineNum = $lineNum;
	$preLineDesc = $lineDesc;
}

echo json_encode($jsonData);






die();

$serviceURL = "/eweb/default.aspx";
$params = "";
$referer = "";
$host = "citybus.taichung.gov.tw";

$resultLines = file("http://".$host.$serviceURL);
$result = "";
for($i = 0; $i < count($resultLines); $i++){
	$result .= $resultLines[$i];	
}

$jsonData = array("result" => array());

$extractStart = strpos($result, '<select id="routesearch2"');
$extractEnd = strpos($result, '</select>', $extractStart) + 9;
$extractedHTML = iconv('big5', 'UTF-8', substr($result, $extractStart, $extractEnd - $extractStart + 1));
$extractedHTML = str_replace('<->', '－', $extractedHTML);
$extractedHTML = str_replace('&', '&amp;', $extractedHTML);
$extractedHTML = str_replace('、', ', ', $extractedHTML);
echo $extractedHTML;
$doc = new DOMDocument();
$doc->loadHTML('<?xml encoding="UTF-8">'.$extractedHTML);
$options = $doc->getElementsByTagName('option');
$optionCount = $options->length;


for($i = 0; $i < $optionCount; $i++){
	$optionText = $options->item($i)->childNodes->item(0)->nodeValue;
	$content = explode(' ', $optionText, 2);
	$lineNum = $content[0];
	$lineDesc = str_replace('&amp;', '&', $content[1]);
	$terminals = explode('－', $lineDesc);
	$jsonData["result"][] = array(
		"lineNum" => $lineNum,
		"lineDesc" => $lineDesc,
		"startStop" => $terminals[0],
		"endStop" => $terminals[1]
	);
}


echo json_encode($jsonData);


function isPureNumberStr($str){
	$num = (int)$str;
	$newStr = $num.'';
	if(strlen($newStr) == strlen($str)){
		return true;	
	}
	return false;
}

?>