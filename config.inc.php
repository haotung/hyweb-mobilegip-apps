<?PHP
header("Content-Type:text/html; charset=utf-8");
require_once('vendor/autoload.php');
require_once('my_adodb_class.php');
include_once('Constants.php');
include_once('datastore_config.php');
include_once('DatastoreTool.php');
use Google\Cloud\ServiceBuilder;
error_reporting(E_ERROR);

$FOR_DEVELOPMENT = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false);
$DB_CONN_STR = '/cloudsql/hyweb-mobilegip-notification:push-notification';
$DB_HOST = '173.194.248.238:3306';
$DB_NAME = 'mobilegip';
$DB_USER = 'root';
$DB_PASSWORD = '';


if($FOR_DEVELOPMENT){
	$DB_PASSWORD = 'tttt';
}
global $conn;


//echo $DB_CONN_STR."<br/>\n";
try{
	if($FOR_DEVELOPMENT){
		$conn = new mysqli($DB_HOST,
					$DB_USER,
					$DB_PASSWORD,
					$DB_NAME
				);
	}
	else{
		$conn = new mysqli(null,
					$DB_USER,
				 	$DB_PASSWORD, 
				 	$DB_NAME,
				 	null,
				 	$DB_CONN_STR
				 );
	}
	$db = new MyAdodb($conn);
	
}
catch(Exception $e){
	 echo "error @ connecting db:".$e;
	
}



include_once('./smarty/Smarty.class.php');

$smarty = new Smarty();
$smarty->compile_dir= "gs://hyweb-mobilegip-apps/smarty/compiled/"; 
$smarty->cache_dir="gs://hyweb-mobilegip-apps/smarty/cache/";

date_default_timezone_set('Asia/Taipei');
putenv('TZ=Asia/Taipei'); 



$datastoreTool = new DatastoreTool($datastore);

?>