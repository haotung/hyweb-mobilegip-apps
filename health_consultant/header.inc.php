<?PHP
if(!isset($contentType)){
	$contentType = 'application/json';
}
header("Content-Type:".$contentType."; charset=utf-8");


include_once('config.inc.php');
require_once('my_adodb_class.php');
error_reporting(E_ERROR);

global $conn;


$conn = new mysqli(null,
					$DB_USER,
				 	$DB_PASSWORD, 
				 	$DB_NAME,
				 	null,
				 	$DB_CONN_STR
				 );


//$conn = new mysqli($DB_HOST.':'.$DB_PORT, $DB_USER, $DB_PASSWORD, $DB_NAME);
$conn->set_charset("utf8");
$db = new MyAdodb($conn);


?>