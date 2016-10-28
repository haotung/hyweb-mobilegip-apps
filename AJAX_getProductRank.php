<?PHP
header("Content-Type:application/json; charset=utf-8");
$apiRoot = 'https://api.appannie.com/v1.2/';
$apiKey = 'ab1a46530aebfb52f79a53d2ff80d276f0aab8d1';
$contextOption = array( 
    'http' => array(
        'method' => 'GET',
        'header' => 'Authorization: Bearer '.$apiKey."\r\n" .
                    'Content-Type: application/json' . "\r\n"
    )
);

$vertical = $_GET['vertical'];
$market = $_GET['market'];
$asset = $_GET['asset'];
$productId = $_GET['productId'];
$startDate = $_GET['startDate'];
$endDate = $_GET['endDate'];

$url = $apiRoot.$vertical.'/'.$market.'/'.$asset.'/'.$productId.'/ranks?start_date='.$startDate.'&end_date='.$endDate;
$context = @stream_context_create($contextOption);
$execResult = @file_get_contents($url, false, $context);
$infoObj = json_decode($execResult, true);



$data['productId'] = $infoObj;
echo json_encode($data);


?>