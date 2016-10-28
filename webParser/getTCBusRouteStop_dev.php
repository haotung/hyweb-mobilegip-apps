<?PHP
include_once("parser.lib.php");
header('Content-type: application/json; charset=utf-8');
//header('Content-type: text/html; charset=utf-8');

$Routename = $_GET['Routename'];
$Goback = $_GET['Goback'];
$lang = 'en';
if(isset($_GET['lang'])){
	if($_GET['lang'] == 'zh'){
		$lang = 'zh';
	}
}

$serviceURL = "/xmlbustms/".$lang."/BusStopTimeIFrame.aspx";
$params = "Routename=".$Routename."&Goback=".$Goback;
$referer = "";
$host = "citybus.taichung.gov.tw";

$resultLines = file("http://".$host.$serviceURL.'?'.$params);
$result = "";
for($i = 0; $i < count($resultLines); $i++){
	$result .= $resultLines[$i];	
}


$jsonData = array("result" => array());

$extractStart = strpos($result, 'var routeStops =') + 17;
$extractEnd = strpos($result, ';', $extractStart) - 2;
$extractedHTML = iconv('big5', 'UTF-8', substr($result, $extractStart, $extractEnd - $extractStart + 1));

echo $extractedHTML;


?>