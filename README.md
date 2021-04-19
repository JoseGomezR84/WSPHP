# WSPHP
Documentacion WebSocket php

Web socket desarrollado con el lenguaje de progrmacion php y la libreria ratchet.

Consulte mas acerca de la libreria en el siguiente link http://socketo.me/

Clase donde codificamos los metodos que tendra el websocket 

Se encuentra dentro de la carpeta src 

Archivo Chat.php

Metodos predefinidos del webSocket 

Metodo onOpen en este recibimos todas las conexiones y enviamos el resultado de la conexión.

public function onOpen(ConnectionInterface $conn) {
         // Store the new connection to send messages to later
         $this->clients->attach($conn);
         echo "New connection! ({$conn->remoteAddress})\n";
         $ip = $conn->remoteAddress;
         $port = $conn->remotePort;
         $port = str_replace("tcp://".$ip.":", "", $port);
         foreach ($this->clients as $client) {
            echo $client->remoteAddress;
            if ($conn === $client) {
                // The sender is not the receiver, send to each client connected
                $conn->send("INIT\tOK\r\nPUERTO\t ".$port." \r\nIP\t" . $client->remoteAddress . "\r\n");

            }

        }
         $fecha = getdate();
         if (file_exists("../logs/".$fecha['year']."-".$fecha['mday']."-".$fecha['mon']."_conexion.txt")){
            $archivo = fopen("../logs/".$fecha['year']."-".$fecha['mday']."-".$fecha['mon']."_conexion.txt", "a");
            fwrite($archivo,PHP_EOL ."****************\nCliente conectado :".$conn->remoteAddress." Hora de conexion : ".$fecha['year']."-".$fecha['mon']."-".$fecha['mday']."-".$fecha['hours'].":".$fecha['minutes'].":".$fecha['seconds']."\n****************");
            fclose($archivo);
            }
            else{
            $archivo = fopen("../logs/".$fecha['year']."-".$fecha['mday']."-".$fecha['mon']."_conexion.txt", "w");
            fwrite($archivo,PHP_EOL ."****************\nCliente conectado :".$conn->remoteAddress." Hora de conexion : ".$fecha['year']."-".$fecha['mon']."-".$fecha['mday']."-".$fecha['hours'].":".$fecha['minutes'].":".$fecha['seconds']."\n****************");
            fclose($archivo);
               }
    }

Metodo onMessage en este recibimos todas los mensajes de las conexiones se evalua y se trabaja con los datos recibidos .

public function onMessage(ConnectionInterface $from, $msg) {

        try{
            $indexWRL_ID = strpos($msg, "WRL_ID\t") ;

            if($indexWRL_ID !== false ){
                $claveSinWrl = substr($msg,$indexWRL_ID);
                $initValor  = strpos($claveSinWrl, "\t") ;
                $corteValor = strpos($claveSinWrl, "\n") ;
                $strFinal   = substr($claveSinWrl,$initValor,$corteValor);
                $nombreESP  = $strFinal;
                echo  "Hola, My WRL es: ".$nombreESP;
            }
            if(strlen($msg)>0){
                $fecha = getdate();
                if (file_exists("../logs/".$fecha['year']."-".$fecha['mon']."-".$fecha['mday']."_data.txt")){
                    $archivo = fopen("../logs/".$fecha['year']."-".$fecha['mon']."-".$fecha['mday']."_data.txt", "a");
                    fwrite($archivo,PHP_EOL . $fecha['year']."-".$fecha['mon']."-".$fecha['mday']."-".$fecha['hours'].":".$fecha['minutes'].":".$fecha['seconds']."; datos recibidos" . $msg);
                    fclose($archivo);
                }
                else{
                    $archivo = fopen("../logs/".$fecha['year']."-".$fecha['mon']."-".$fecha['mday']."_data.txt", "w");
                    fwrite($archivo,PHP_EOL . $fecha['year']."-".$fecha['mon']."-".$fecha['mday']."-".$fecha['hours'].":".$fecha['minutes'].":".$fecha['seconds']."; datos recibidos" . $msg);
                    fclose($archivo);
                }
            }

            echo "MSG: ". $msg;

            if(strpos($msg, "KW\tINFO") !== false){

                $auxResponse = ESP_MSG($msg);
                $responseWS = "";

                if(strpos($auxResponse, "SIN_BOF") !== false){
                    echo "--------------------------------------Banderas sin desbordamiento -------- ";
                }
                else{
                    $responseWS  = "PID\tWS\r\nKW\tRSP\r\nPAYLOAD\tCLR-". (string)$auxResponse . "\r\nCHCKSM\t75";
                    foreach ($this->clients as $client) {
                        $client->send($responseWS);
                    }
                }
            }
            else if(strpos($msg, "CONNECTED") !== false){
                $lista = [];
                $msgUpdate = $msg . "\r\nIP\t" . $from->remoteAddress . "\r\nPORT\t 8080";
                $lista = dataUpdate("\t", "\r\n", $msgUpdate, "WRL_ID");
                $idEsp = $lista[0];
                //$conDB = new \DbConnect;
                //$conDB-> connect(1,$lista[1],$lista[2]);
                //echo $this->idEsps;
                array_push($this->idEsps, $lista[0]);
                $idEsp = null;
            }
            else if(strpos($msg, "SU_SERVER") !== false){
                $listaSu = [];
                $listaSu = datosDiccionario("\t", "\r\n", $msg);
                $serverSu=$listaSu[0];
                $fecha = getdate();
                $hora = (int)$fecha['hours']-7;
                foreach ($this->clients as $client) {
                    $client->send($msg."FechaTx: ". $fecha['year']."-".$fecha['mon']."-".$fecha['mday']." ".$hora.":".$fecha['minutes'].":".$fecha['seconds']);
                }
            }
        }
        catch( Exception $e){
            echo $e->getMessage();
            $fecha = getdate();
            if (file_exists("../logs/".$fecha['year']."-".$fecha['mday']."-".$fecha['mon']."_exception.txt")){
               $archivo = fopen("../logs/".$fecha['year']."-".$fecha['mday']."-".$fecha['mon']."_exception.txt", "a");
               fwrite($archivo,PHP_EOL ."\n*******\n".$fecha['year']."-".$fecha['mon']."-".$fecha['mday']."-".$fecha['hours'].":".$fecha['minutes'].":".$fecha['seconds']."; ESP_MSG(msg_receive) Instance: ". $e->getMessage() ." \n*******\n");
               fclose($archivo);
               }
               else{
               $archivo = fopen("../logs/".$fecha['year']."-".$fecha['mday']."-".$fecha['mon']."_exception.txt", "w");
               fwrite($archivo,PHP_EOL ."\n*******\n".$fecha['year']."-".$fecha['mon']."-".$fecha['mday']."-".$fecha['hours'].":".$fecha['minutes'].":".$fecha['seconds']."; ESP_MSG(msg_receive) Instance: ". $e->getMessage() ." \n*******\n");
               fclose($archivo);
                  }
        }

    }



Metodo onClose en este vemos cuando se remueve una conexion y dentro del servidor podemos ver la desconexión.

    public function onClose(ConnectionInterface $conn) {
         // The connection is closed, remove it, as we can no longer send it messages
         $this->clients->detach($conn);

         echo "Connection {$conn->resourceId} has disconnected\n";
         $fecha = getdate();
         if (file_exists("../logs/".$fecha['year']."-".$fecha['mday']."-".$fecha['mon']."_conexion.txt")){
            $archivo = fopen("../logs/".$fecha['year']."-".$fecha['mday']."-".$fecha['mon']."_conexion.txt", "a");
            fwrite($archivo,PHP_EOL ."****************\nCliente desconectado :".$conn->remoteAddress." Hora de desconexion : ".$fecha['year']."-".$fecha['mon']."-".$fecha['mday']."-".$fecha['hours'].":".$fecha['minutes'].":".$fecha['seconds']."\n****************");
            fclose($archivo);
            }
            else{
            $archivo = fopen("../logs/".$fecha['year']."-".$fecha['mday']."-".$fecha['mon']."_conexion.txt", "w");
            fwrite($archivo,PHP_EOL ."****************\nCliente desconectado :".$conn->remoteAddress." Hora de desconexion : ".$fecha['year']."-".$fecha['mon']."-".$fecha['mday']."-".$fecha['hours'].":".$fecha['minutes'].":".$fecha['seconds']."\n****************");
            fclose($archivo);
               }
    }

Metodo onError en este vemos los errores que pueda generar una conexion 
	
     public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }

Otros metodos importantes dentro de la creacion del servidor que se encargan de manipular los datos que se reciben y las funciones de guardar en la base de datos 

//Metodo #1
function dataUpdate($sep1, $sep2, $msg, $filterField){
    $fields2='';
    $conditionString = '';
    $listaDatos = [];
    $KVSeparados = [];

    $KVSeparados = explode($sep2, $msg);

       foreach ( $KVSeparados as $palabra){

            $TEMPO = explode($sep1, $palabra);
            $key = $TEMPO[0];
            $value = $TEMPO[1];
            if(strpos($value, "\r") !== false){
                $value = str_replace('\t','',$value);
            }
            if($key == $filterField){
                $conditionString = "`".$filterField."`='".$value."'";
                //echo  $conditionString;
            }
            else{
                $datos[$key] = $value;
                //echo $datos[$key]."<br>";
                $fields2 = $fields2. "`" . $key . "`='" . $value . "',";
               //echo $fields2;
            }
        }
        $campos ='';
        $campos = substr($fields2, 0, -1);
        $listaDatos = [$datos, $campos, $conditionString];

        return $listaDatos;

}

//Metodo #2
function datosDiccionario($sep1, $sep2, $msg){
    $fields = '(';
    $values = '(' ; # String con los valores del msg
    $listaDatos = [];  # Lista donde se almacena Datos[Dicc], fields, values.


    $KVSeparados = [];
    $KVSeparados = explode($sep2, $msg);
    foreach ( $KVSeparados as $palabra){

        $TEMPO = explode($sep1, $palabra);
        $key = $TEMPO[0];
        $value = $TEMPO[1];
        if(strpos($value, "\r")){
            $value = str_replace("\r", "", $value);
        }
        $datos[$key]= $value;
        $fields = $fields . '`'. $key .'`,';
        $values = $values ."'". $value . "',";
    $fields= $fields . '`T`)';
    $fecha = getdate();
    $values = $values . "'". time() . "')";
    $listaDatos = [$datos, $fields, $values];
    return $listaDatos;

    }


}

//Metodo #3
function ESP_MSG($msg_receive){
    try{

        $C_BOFSR   = 0b100000;
        $C_BOFSD   = 0b10000;
        $C_BOFC1   = 0b1000;
        $C_BOFC2   = 0b100;
        $C_BOFC3   = 0b10;
        $C_BOFG    = 0b1;


        $F_BOFSR   = 0;
        $F_BOFSD   = 0;
        $F_BOFC1   = 0;
        $F_BOFC2   = 0;
        $F_BOFC3   = 0;
        $F_BOFG    = 0;

        $initBOF = -1;
        $endBOF  = -1;
        $midBOF  = -1;
        $strBOF  = "";
        $strBOF2 = "";
        $BOF_int = 0;
        $BOF_RESPONSE = 40;


        $initBOF = strpos($msg_receive, "BOF\t") ;
        if($initBOF !== false ){

            $strBOF = substr($msg_receive,$initBOF);
            $midBOF  = strpos($strBOF, "\t") ;
            $endBOF = strpos($strBOF, "\n") ;
            $strFinal   = substr($strBOF,$midBOF,$endBOF);

            $BOF_int = (int)$strFinal;

        if($BOF_int > 0){
             #print("|||||||||||||||||| BOF Logic !!!!!!!!!!!!!!!!!,,,,",(initBOF,strBOF, endBOF, strBOF, BOF_int))
	            # Deteccion de banderas
	            $F_BOFSR   = $C_BOFSR & $BOF_int;
	            $F_BOFSD   = $C_BOFSD & $BOF_int;
	            $F_BOFC1   = $C_BOFC1 & $BOF_int;
	            $F_BOFC2   = $C_BOFC2 & $BOF_int;
	            $F_BOFC3   = $C_BOFC3 & $BOF_int;
	            $F_BOFG    = $C_BOFG  & $BOF_int;
	            #
	            #print("|||||||||||||||||| Status BOF !!!!!!!!!!!!!!!!!,,,,",bin(F_BOFSR|F_BOFSD|F_BOFC1|F_BOFC2|F_BOFC3|F_BOFG))
	            $BOF_RESPONSE = $BOF_int;
	            if($F_BOFSR){
	                $BOF_RESPONSE = (0b11111) & $BOF_RESPONSE;
	                #print("-----------------Clear F_BOFSR---------",bin(BOF_RESPONSE))
                }
	            if($F_BOFSD){
	                $BOF_RESPONSE = (0b101111)  & $BOF_RESPONSE;
	                #print("-----------------Clear F_BOFSD---------",bin(BOF_RESPONSE))
                }
	            if($F_BOFC1){
	                $BOF_RESPONSE = (0b110111)  & $BOF_RESPONSE;
	                #print("-----------------Clear F_BOFC1---------",bin(BOF_RESPONSE))
                }
	            if($F_BOFC2){
	                $BOF_RESPONSE = (0b111011)  & $BOF_RESPONSE;
	                #print("-----------------Clear F_BOFC2---------",bin(BOF_RESPONSE))
                }
	            if($F_BOFC3){
	                $BOF_RESPONSE = (0b111101)  & $BOF_RESPONSE;
	                #print("-----------------Clear F_BOFC3---------",bin(BOF_RESPONSE))
                }
	            if($F_BOFG){
	                $BOF_RESPONSE = (0b111110)  & $BOF_RESPONSE;
	                #print("-----------------Clear F_BOFG---------",bin(BOF_RESPONSE))
                }
	            #print("|||||||||||||||||| Response BOF !!!!!!!!!!!!!!!!!,,,,",bin(BOF_RESPONSE))
            }

            elseif ($BOF_int == 0){
                $BOF_RESPONSE = "SIN_BOF";
            }
        }
        $conDB = new \DbConnect;
        $stmDic = datosDiccionario("\t", "\n", $msg_receive);
        $conDB-> connect(2,$stmDic[1],$stmDic[2]);
        return (string)$BOF_RESPONSE;
    }
    catch(Exception $e){
        echo $e->getMessage();
        $fecha = getdate();
         if (file_exists("../logs/".$fecha['year']."-".$fecha['mday']."-".$fecha['mon']."_exception.txt")){
            $archivo = fopen("../logs/".$fecha['year']."-".$fecha['mday']."-".$fecha['mon']."_exception.txt", "a");
            fwrite($archivo,PHP_EOL ."\n*******\n".$fecha['year']."-".$fecha['mon']."-".$fecha['mday']."-".$fecha['hours'].":".$fecha['minutes'].":".$fecha['seconds']."; ESP_MSG(msg_receive) Instance: ". $e->getMessage() ." \n*******\n");
            fclose($archivo);
            }
            else{
            $archivo = fopen("../logs/".$fecha['year']."-".$fecha['mday']."-".$fecha['mon']."_exception.txt", "w");
            fwrite($archivo,PHP_EOL ."\n*******\n".$fecha['year']."-".$fecha['mon']."-".$fecha['mday']."-".$fecha['hours'].":".$fecha['minutes'].":".$fecha['seconds']."; ESP_MSG(msg_receive) Instance: ". $e->getMessage() ." \n*******\n");
            fclose($archivo);
               }
    }

}

Este es el codigo que debe tener la clase con la que se crea el servidor WebSocket 

A continuacion la clase que conecta con la base de datos y nos permite manipularla conexion.php esta se encuentra dentro de la carpeta db
 

Datos de conexion:

		private $host = '********';
		private $dbName = '*********';
		private $user = '***********';
		private $pass = '*************';

Funcion que actualiza en la bd

	function updateData($fields, $conditionStatament){
			$tableConn = "PMSList";
			$sql = "UPDATE ".$tableConn. " SET " . $fields . " WHERE ". $conditionStatament;   
			return $sql;
		}

Funcion que inserta el la bd 

	function insertData($fields, $values){
			$tableConn = "Data_Cartagena";
			$sql = "INSERT INTO ".$tableConn . $fields . " VALUES ".$values;   
			return $sql;
		}

Fucion donde llamamos alguna de las funciones anteriores dependiendo de un parametro que se recibe

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


por ultimo tenemos la clase que permite correr nuestro servidor web socket se encuentra dentro de la carpeta bin 
	
	<?php
	use Ratchet\Server\IoServer;
	use Ratchet\Http\HttpServer;
	use Ratchet\WebSocket\WsServer;
	use MyApp\Chat;

	    require dirname(__DIR__) . '/vendor/autoload.php';

	    $server = IoServer::factory(
        	new HttpServer(
            	new WsServer(
                	new Chat()
            		)
        	),

		// puero en el que queremos que corra nuestro servidor 
        	8081
    		);

    	    $server->run();


Instalar nuestro servidor WebSocket en un servidor php en la nube 

1)Una vez tengamos nuestro servidor montado con php procedemos a instalar composer con los siguientes comandos  
	
	php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
	php -r "if (hash_file('sha384', 'composer-setup.php') === '756890a4488ce9024fc62c56153228907f1545c228516cbf63f885e036d37e9a59d27d63f46af1d4d07ee0f76181c7d3') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
	php composer-setup.php
	php -r "unlink('composer-setup.php');"

2)Instalamos la libreria ratchet de php con el siguiente comando
 
	php ~/composer.phar require cboden/ratchet

3) Copiamos y pegamos las carpetas bin, db, logs, src con sus respectivos archivos.

4)Dentro de la carpeta vendor/cboden/ratchet/server el archivo IoServer.php en la funcion  handleConnect agregamos esta variable que nos permitira obtener el puerto de una nueva conexion 

	$conn->decor->remotePort = $uri;

5)ya teniendo montados todos los archivos agregamos al archivo composer.json el siguiente codigo

	"autoload": {
        "psr-4": {
            "MyApp\\": "src"
        }
    },

el archivo completo deberia verse asi: 

5) Ejecutamos dentro de nuestro servidor el comando composer update 

6) Por ultimo dentro de la consola de comandos de nuestro servidor entramos a la carpeta bin y ejecutamos el servidor con el comando 

	php server.php

7)Nuestro servidor estara corriendo dentro de la consola dode podremos ver todos los movimientos de conexiones que se hacen detro de este mismo.

8)Si tiene problemas trate de crear las carpetas y los archivos manualmente y copie y pegue el codigo 
 







