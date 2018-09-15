<?php
require_once "constants.php";
require_once "ids.php"; // Contains the identifier + Password to connect to RTone web API
require_once "common.php"; 

$db = connect_to_db();


// $_GET should contain serial, start, end 
// ex: http://localhost/~heusse/Monitor/get10mn.php?serial=216670215&start=1512814200&end=1512823800

$endDate=$_GET['end'];
$dateYr=date("Y");
$dateMonth=date("m");
$thisMonth= strtotime("$dateYr-$dateMonth-01T00:00:00");

$dateYr=$dateYr-1;
$dateDay=date("d");
$thisYear=strtotime("$dateYr-$dateMonth-$dateDay"."T00:00:00");

$sum=[];
foreach(array('month'=>$thisMonth,'year'=>$thisYear,'total'=>0) as $what=>$startts){
  $qr="select '$what',sum(prod)/1000 from ".tp."readings where ts >  $startts";
  $select_messages = $db->prepare($qr);
  $select_messages->setFetchMode(PDO::FETCH_KEY_PAIR);
  $select_messages->execute($reqArgs);
  $sum =$sum+$select_messages->fetchAll();
}
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *',false);
echo json_encode($sum);


?>