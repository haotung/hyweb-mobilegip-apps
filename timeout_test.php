<?PHP

echo "start<br/>\n";
for($i = 0; $i < 10; $i++){
	echo $i.":".date("Y-m-d H:i:s")."<br/>\n";
	sleep(15);
}
?>