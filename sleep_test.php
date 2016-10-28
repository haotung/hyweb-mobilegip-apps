<?PHP
include_once('config.inc.php');

sleep(3);
echo 'after sleep 3 seconds';
syslog(LOG_DEBUG, "my id = ".$_POST['id']);




?>