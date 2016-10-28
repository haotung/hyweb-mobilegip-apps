<?PHP
class MyAdodb{

	var $conn;
	
	function MyAdodb($_conn){
		$this->conn = $_conn;
	}
	
	
	function Execute($query){
		if($rs = mysqli_query($this->conn, $query)){
			return new MyAdodbRecordSet($rs);
		}
		return null;
	}

	function ExecuteMultipleQueries($query){
		if(mysqli_multi_query($this->conn, $query)){
			
			
			$i = 0; 
		    while ($this->conn->more_results()){ 
		        $i++; 
		        $this->conn->next_result();
		    } 
		}
		else{
			if($this->conn->errno){
				//echo $this->conn->error; 	
			}	
		}
		return null;
	}

	function Insert_ID(){
		return mysqli_insert_id($this->conn);	
	}

	function Close(){
		mysqli_close($this->conn);	
	}
	
}

class MyAdodbRecordSet{
	
	var $rs;
	
	function MyAdodbRecordSet($_rs){
		$this->rs = $_rs;	
		
	}	
	
	function FetchRow(){
		return mysqli_fetch_assoc($this->rs);
	}
	
}



?>