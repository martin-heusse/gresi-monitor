<?php
require_once "constants.php";
require_once "ids.php"; // Contains the identifier + Password to connect to RTone web API
require_once "common.php"; 

$db = connect_to_db();


// $_GET should contain serial, start, end 
// ex: http://localhost/~heusse/Monitor/getMonthlyProd.php?serial=216670215&end=1512823800

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
$serial=$_GET['serial'];
$reqArgs=array($serial,$startts,$endts);

$qr="select COALESCE(sum(prod),0) as s from ".tp."readings where serial=? and ts between ? and  ? ";
// Oddly, summing on prod from XXirrad table does not give the good results
$select_messages = $db->prepare($qr);
$select_messages->setFetchMode(PDO::FETCH_ASSOC);
$select_messages->execute($reqArgs);
$readings =$select_messages->fetchAll();
$retreadings[$serial]=$readings[0]['s'];
header('Content-Type: application/json');
echo json_encode($retreadings);


?>