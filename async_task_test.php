<?PHP
//use 'google/appengine/api/taskqueue/PushTask.php';
use google\appengine\api\taskqueue\PushTask;


echo "async_task_test.php<br/>\n";

for($i = 0; $i < 5; $i++){
	$task = new PushTask('/sleep_test.php', ['id' => $i]);
    $task_name = $task->add();
	echo "execute ".$task_name." at ".date("Y-m-d H:i:s")."<br/>\n";
}

?>