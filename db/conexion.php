<?php 
	class DbConnect {
		private $host = 'mysql.engenius.com.co';
		private $dbName = 'central_saas';
		private $user = 'infovisitas';
		private $pass = 'desarrollo2020';

		
		function updateData($fields, $conditionStatament){
			$tableConn = "PMSList";
			$sql = "UPDATE ".$tableConn. " SET " . $fields . " WHERE ". $conditionStatament;   
			return $sql;
		}

		function insertData($fields, $values){
			$tableConn = "Data_Cartagena";
			$sql = "INSERT INTO ".$tableConn . $fields . " VALUES ".$values;   
			return $sql;
		}

		function connect($typeQuery, $fields, $values){

			$complementTXT = "";
        	$tableConn = "";
        	$query2 = "";
        	echo "1. Inicio de connect";
			if((int)$typeQuery == 1){
				echo "Case 1: Update";
				$query2 = $this->updateData($fields,$values);
				$complementTXT = "-UPDATE"; 

			}
			else if((int)$typeQuery == 2){
				echo "Case 1: Update";
				$query2 = $this->insertData($fields,$values);
				$complementTXT = "-INSERT";
			}
			echo "----------------QUERY------------------";
			
			echo $complementTXT;
			echo $query2;
			$conn = new PDO('mysql:host=' . $this->host . '; dbname=' . $this->dbName, $this->user, $this->pass);
			$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			if($conn){
				echo "Conecten with mysql";
				
			}
			$stmt = $conn->prepare($query2);
			$stmt->execute();
			


		}

		


	}
 ?>