<?php
require_once "constants.php";
require_once "ids.php"; // Contains the identifier + Password to connect to RTone web API
require_once "common.php"; 

$db = connect_to_db();


// $_GET should contain serial, start, end 
// ex: http://localhost/~heusse/Monitor/get10mn.php?serial=216670215&start=1512814200&end=1512823800

$reqArgs=array($_GET['serial'],$_GET['start'],$_GET['end'],$_GET['serial'],$_GET['start'],$_GET['end'],$_GET['serial']);

// Make sure that there is a data (and maybe 0) for any existing ts in the DB within the time span

$qr="SELECT serial, ts, prod
FROM ".tp."readings
WHERE serial=?
AND ts BETWEEN ? and ?
UNION
SELECT ? as serial, ts, -1
FROM ".tp."readings
WHERE ts BETWEEN ? AND ?
AND (ts) NOT IN ( SELECT ts FROM ".tp."readings WHERE serial=?)
ORDER BY ts";

$select_messages = $db->prepare($qr);
$select_messages->setFetchMode(PDO::FETCH_ASSOC);
$select_messages->execute($reqArgs);
$readings =$select_messages->fetchAll();
header('Content-Type: application/json');
echo json_encode($readings);


?>