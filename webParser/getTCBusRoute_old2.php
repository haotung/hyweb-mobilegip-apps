<?PHP
include_once("parser.lib.php");
header('Content-type: application/json; charset=utf-8');
//header('Content-type: text/html; charset=utf-8');

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
	$content = split(' ', $optionText, 2);
	$lineNum = $content[0];
	$lineDesc = str_replace('&amp;', '&', $content[1]);
	$terminals = split('－', $lineDesc);
	$jsonData["result"][] = array(
		"lineNum" => $lineNum,
		"lineDesc" => $lineDesc,
		"startStop" => $terminals[0],
		"endStop" => $terminals[1]
	);
}


echo json_encode($jsonData);


?>