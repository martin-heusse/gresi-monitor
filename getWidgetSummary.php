<?php
require_once "constants.php";
require_once "ids.php"; // Contains the identifier + Password to connect to RTone web API
require_once "common.php"; 

$db = connect_to_db();


// $_GET should contain serial, start, end 
// ex: http://localhost/~heusse/Monitor/get10mn.php?serial=216670215&start=1512814200&end=1512823800


$serialselect="";
if (isset($_GET['serial'])){
  $theserial=$_GET['serial'];
  $serialselect=" and serial=$theserial";
  $sum=['msg'=>"de cette station"];
}
else{
  $sum=['msg'=>"de Gr&eacute;si21"];
}

$dateYr=date("Y");
$dateMonth=date("m");
$thisMonth=strtotime("$dateYr-$dateMonth-01T00:00:00");

$thisYear=strtotime("$dateYr-01-01"."T00:00:00");

foreach(array('month'=>$thisMonth,'year'=>$thisYear,'total'=>0) as $what=>$startts){
  $qr="select '$what',sum(prod)/1000 from ".tp."readings where ts >  $startts".$serialselect;
  $select_messages = $db->prepare($qr);
  $select_messages->setFetchMode(PDO::FETCH_KEY_PAIR);
  $select_messages->execute($reqArgs);
  $sum =$sum+$select_messages->fetchAll();
}
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *',false);
echo json_encode($sum);


?>