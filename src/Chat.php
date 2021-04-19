<?php
namespace MyApp;

use DbConnect;
use Exception;
use ipDireccion;
use Psr\Http\Message\RequestInterface;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
require "../db/conexion.php";

$idEsps = [];

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

class Chat implements MessageComponentInterface {
    protected $clients;
    protected $idEsps = [];
    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        echo "server iniciado";
    }
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

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }
}