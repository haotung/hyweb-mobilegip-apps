<?PHP
include_once('config.inc.php');
header("Content-Type:text/html; charset=utf-8");
error_reporting(E_ERROR);
set_time_limit(120);
ini_set('memory_limit', '512M');
$apiRoot = 'https://api.appannie.com/v1.2/';
$apiKey = 'ab1a46530aebfb52f79a53d2ff80d276f0aab8d1';	//haotung
$apiKey = '551d9f99240605ffd9ca0db62e382fab7c4837b4';	//haotung.lin@hyweb.com.tw
$forceUpdate = false;
if(isset($_GET['forceUpdate']) && strcmp($_GET['forceUpdate'],'true') === 0){
	$forceUpdate = true;
}
$allData = array();
$allProductIds = array();
$contextOption = array( 
    'http' => array(
        'method' => 'GET',
        'header' => 'Authorization: Bearer '.$apiKey."\r\n" .
                    'Content-Type: application/json' . "\r\n"
    )
);


function main(){
	global $allData;
	global $db;
	global $forceUpdate;
	if($forceUpdate || (!loadCacheFromDB())){
		$allData['accounts'] = getAccounts();
		for($i = 0; $i < count($allData['accounts']); $i++){
			$allData['accounts'][$i]['products'] = getProducts($allData['accounts'][$i]);
		}
		$query = "Insert into app_annie_cache "
				."Set all_data = '".json_encode($allData, JSON_UNESCAPED_UNICODE)."', "
				."last_update = '".date("Y-m-d H:i:s")."' ";
		$db->Execute($query);
	}
	
	drawAppsTable();
}

function loadCacheFromDB(){
	global $allData;
	global $db;
	$anHourAgo = date('Y-m-d H:i:s', strtotime('-1 hour'));
	$query = "Select all_data, last_update "
			."From app_annie_cache "
			."Where last_update >= '".$anHourAgo."' "
			."Order by last_update desc ";
	$rs = $db->Execute($query);
	if($row = $rs->FetchRow()){
		$allData = json_decode($row['all_data'], true);
		$allData['cacheTime'] = $row['last_update'];
		return true;
	}
	return false;
}


function drawAppsTable(){
	global $allData;
	global $allProductIds;
	$accountCount = count($allData['accounts']);
	if(array_key_exists('cacheTime', $allData)){
		echo '<span class="cacheTime">來自'.$allData['cacheTime']."的快取</span><br/>\n";	
	}
	for($a = 0; $a < $accountCount; $a++){
		$account = $allData['accounts'][$a];
		$displayAccountName = readableMarket($account['market']).' - '.urldecode($account['publisher_name']);
		echo '<h4><a name="acc'.$account['account_id'].'" displayAccountName="'.$displayAccountName.'" >'.$displayAccountName."</a></h4>\n";
		
		echo '<table class="productTable">'."\n";
		$products = $account['products'];
		$productCount = count($products);
		for($p = 0; $p < $productCount; $p++){
			$product = $products[$p];
			echo '<tr>'."\n";
			echo '<td><img src="'.$product['icon'].'" width="64"/></td>'."\n";
			echo '<td width="500" valign="top" align="left"><span class="productName">'.$product['product_name']."</span><br/>";
			echo '<span class="statistics_date">'.$product['first_sales_date'].' ~ '.$product['last_sales_date']."</span>"."<br/>";
			echo '<span class="download" id="dl'.$product['product_id'].'">'.number_format($product['downloads']).'次</span>';
			echo '</td>'."\n";
			echo '</tr>'."\n";	
			$allProductIds[] = $product['product_id'];
		}
		echo "</table>\n";
		
	}
	
}

function readableMarket($market){
	if(strcmp($market, 'ios') === 0){
		return 'iOS';	
	}
	else if(strcmp($market, 'google-play') === 0){
		return 'Android';	
	}
	else{
		return $market;	
	}
}

function getAccounts(){
	global $apiRoot;
	global $contextOption;
	$url = $apiRoot.'accounts';
	$context = @stream_context_create($contextOption);
	$execResult = @file_get_contents($url, false, $context);
	$infoObj = json_decode($execResult, true);
	if(!array_key_exists('accounts', $infoObj)){
		die('向App Annie取得資料時發生錯誤，請重新整理。');	
	}
	return $infoObj['accounts'];
}

function getProducts($account){
	global $apiRoot;
	global $contextOption;
	
	$url = $apiRoot.'accounts/'.$account['account_id'].'/products';
	$context = @stream_context_create($contextOption);
	$execResult = @file_get_contents($url, false, $context);
	$infoObj = json_decode($execResult, true);
	
	$products = $infoObj['products'];
	$productCount = count($products);
	
	
	$url = $apiRoot.'accounts/'.$account['account_id'].'/sales?break_down=product';
	$context = @stream_context_create($contextOption);
	$execResult = @file_get_contents($url, false, $context);
	$salesObj = json_decode($execResult, true);
	$salesList = $salesObj['sales_list'];
	$salesCount = count($salesList);
	for($i = 0; $i < $salesCount; $i++){
		$sale = $salesList[$i];
		$productId = $sale['product_id'];
		$downloads = $sale['units']['product']['downloads'];
		for($p = 0; $p < $productCount; $p++){
			if(strcmp($products[$p]['product_id'], $productId) === 0){
				$products[$p]['downloads'] = $downloads;
				break;
			}
		}
	}
	
	
	return $products;
}

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, user-scalable=no, width=device-width">
<style>
	
.productTable td{
	border:none;
	border-bottom:solid 1pt #C0C0C0;	
}

.statistics_date{
	font-size:10pt;
	color:#666666;	
}

.download{
	font-size:11pt;
	color:#009000;	
}

.accLinkAndroid{
	display:inline-block;
	font-size:10pt;
	border:solid 1px #209038;
	background-color: #ECF9F8;
	padding:2px;
	cursor:pointer;
	margin:2px;
	margin-left:0px;
	color:#209038;
	border-radius: 4px;
}

.accLinkiOS{
	display:inline-block;
	font-size:10pt;
	border:solid 1px #5088F8;
	background-color: #E8F2FF;
	padding:2px;
	cursor:pointer;
	margin:2px;
	margin-left:0px;
	color:#5088F8;
	border-radius: 4px;
}

.cacheTime{
	font-size:9pt;
	color:#C00000;	
}
	
</style>
<script src="http://code.jquery.com/jquery-1.10.2.js"></script>
<script>
	
$(document).ready(function(){
	var linkLabels = '';
	$('a[name]').each(function(){
		var accName = $(this).text();
		linkLabels += '<a href="#' + $(this).attr('name') + '"><span class="' + ((accName.indexOf('iOS') == 0)?"accLinkiOS":"accLinkAndroid") + '">' + accName + '</span></a> ';
		
	});
	
	$('#quickLink').html(linkLabels);
});
</script>
</head>
<body>
<h3>MobileGIP App下載次數列表</h3>
<div id="quickLink"></div>
<?PHP
main();
?>

</body>
</html>