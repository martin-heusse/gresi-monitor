<?php
require_once "constants.php";
require_once "ids.php"; // Contains the identifier + Password to connect to RTone web API
require_once "common.php"; 

$db = connect_to_db();


// $_GET should contain serial, start, end 
// ex: http://localhost/~heusse/Monitor/get10mn.php?serial=216670215&start=1512814200&end=1512823800


$reqArgs=array($_GET['serial']);

$qr="SELECT serial, timeoffset from ".tp."meters WHERE serial=?";
$select_messages = $db->prepare($qr);
$select_messages->setFetchMode(PDO::FETCH_KEY_PAIR);
$select_messages->execute($reqArgs);
$offset =($select_messages->fetchAll())[$_GET['serial']];

$start=$_GET['start']-$offset;
$end=$_GET['end']-$offset;


$reqArgs=array($_GET['serial'],$start,$end,$_GET['serial'],$start,$end,$_GET['serial']);

// Make sure that there is a data (and maybe 0) for any existing ts in the DB within the time span

$qr="SELECT serial, ts+$offset as ts, prod
FROM ".tp."readings as tr
WHERE serial=?
AND tr.ts BETWEEN ? and ?
UNION
SELECT ? as serial, ts+$offset as ts, -1
FROM ".tp."readings as tr
WHERE tr.ts BETWEEN ? AND ?
AND (tr.ts) NOT IN ( SELECT ts FROM ".tp."readings WHERE serial=?)
ORDER BY ts";

$select_messages = $db->prepare($qr);
$select_messages->setFetchMode(PDO::FETCH_ASSOC);
$select_messages->execute($reqArgs);
$readings =$select_messages->fetchAll();
header('Content-Type: application/json');
echo json_encode($readings);


?>