<?PHP
include_once("parser.lib.php");
header('Content-type: application/json; charset=utf-8');
//header('Content-type: text/html; charset=utf-8');

$serviceURL = "/realTime.aspx";
$params = "";
$referer = "";
$host = "211.79.140.206";

$resultLines = file("http://".$host.$serviceURL);
$result = "";
for($i = 0; $i < count($resultLines); $i++){
	$result .= $resultLines[$i];	
}

$jsonData = array("result" => array(
				"arrival" => array(),
				"departure" => array()
			));

$extractStart = strpos($result, '<table');
$extractEnd = strpos($result, '</table>') + 7;
$extractedTable1 = substr($result, $extractStart, $extractEnd - $extractStart + 1);

$extractStart = strpos($result, '<table', $extractEnd);
$extractEnd = strpos($result, '</table>', $extractStart) + 7;
$extractedTable2 = substr($result, $extractStart, $extractEnd - $extractStart + 1);


$docArrv = new DOMDocument();
$docDept = new DOMDocument();
$docArrv->loadHTML('<?xml encoding="UTF-8">'.$extractedTable1);
$docDept->loadHTML('<?xml encoding="UTF-8">'.$extractedTable2);

$jsonData["result"]["arrival"] = getDataArray($docArrv);
$jsonData["result"]["departure"] = getDataArray($docDept);


function getDataArray($domDoc){
	$resultArr = array();
	$rows = $domDoc->getElementsByTagName('tr');
	$rowCount = $rows->length;
	for($i = 1; $i < $rowCount; $i++){
		$docRow = new DOMDocument();
		$docRow->loadHTML(getInnerHTML($rows->item($i)));	
		$columns = $docRow->getElementsByTagName('td');
		$columnCount = $columns->length;
		$tmpRow = array(
			"airline" => $columns->item(0)->textContent,
			"airNo" => $columns->item(1)->textContent,
			"from" => trim($columns->item(2)->textContent),
			"arrivalCity" => trim($columns->item(2)->textContent),
			"departureTime" => $columns->item(3)->textContent,
			"arrivalTime" => $columns->item(4)->textContent,
			"note" => strtoupper(mb_substr($columns->item(5)->textContent, 2, 30, 'UTF-8'))
		);
		$resultArr[] = $tmpRow;
	}
	return $resultArr;
}

echo json_encode($jsonData);


?>