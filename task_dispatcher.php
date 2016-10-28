<?PHP
use google\appengine\api\taskqueue\PushTask;

for($i = 0; $i < 4; $i++){
	echo date("Y-m-d H:i:s").' start task '.$i."<br/>\n";
	syslog(LOG_DEBUG, date("Y-m-d H:i:s")." start task ".$i);
	$task = new PushTask('/memory_eater.php', []);
	$task->add();
	sleep(60);	
}

?>