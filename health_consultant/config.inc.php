<?PHP


$DB_CONN_STR = '/cloudsql/hyweb-mobilegip-notification:push-notification';
$DB_HOST = '173.194.225.54:3306';
$DB_NAME = 'online_health_consultant';
$DB_USER = 'root';
$DB_PASSWORD = '';

/*
$DB_HOST = 'localhost';
$DB_PORT = 8889;
$DB_NAME = 'online_health_consultant';
$DB_USER = 'root';
$DB_PASSWORD = 'root';
*/




global $conn;


date_default_timezone_set('Asia/Taipei');
putenv('TZ=Asia/Taipei'); 
?>