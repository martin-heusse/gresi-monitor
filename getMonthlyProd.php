<?php
require_once "constants.php";
require_once "ids.php"; // Contains the identifier + Password to connect to RTone web API
require_once "common.php"; 

$db = connect_to_db();


// $_GET should contain serial, start, end 
// ex: http://localhost/~heusse/Monitor/get10mn.php?serial=216670215&start=1512814200&end=1512823800

$endDate=$_GET['end'];
$dateYr=date("Y",$endDate);
$dateMonth=date("m",$endDate);
$startts= strtotime("$dateYr-$dateMonth-01T00:00:00");


if($dateMonth<12){
    $dateMonth++;
}
else{
    $dateMonth="1";
    $dateYr++;
}
$endts= strtotime("$dateYr-$dateMonth-01T00:00:00");

$reqArgs=array($_GET['serial'],$startts,$endts);

$qr="select serial,sum(prod) from monitorreadings where serial=? and ts between ? and  ? ";
// Oddly, summing on prod from XXirrad table does not give the good results
$select_messages = $db->prepare($qr);
$select_messages->setFetchMode(PDO::FETCH_KEY_PAIR);
$select_messages->execute($reqArgs);
$readings =$select_messages->fetchAll();
header('Content-Type: application/json');
echo json_encode($readings);


?>