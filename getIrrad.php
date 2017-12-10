<?php
require_once "constants.php";
require_once "ids.php"; // Contains the identifier + Password to connect to RTone web API
require_once "common.php"; 

$db = connect_to_db();


// $_GET should contain serial, start, end 
// ex: http://localhost/~heusse/Monitor/getIrrad.php?serial=216670215&start=1512814200&end=1512823800

$reqArgs=array($_GET['serial'],$_GET['start'],$_GET['end']);

$qr="select * from ".tp."irrad where serial=? and (ts between ? and ?)";
$select_messages = $db->prepare($qr);
$select_messages->setFetchMode(PDO::FETCH_ASSOC);
$select_messages->execute($reqArgs);
$readings =$select_messages->fetchAll();

header('Content-Type: application/json');
echo json_encode($readings);


?>