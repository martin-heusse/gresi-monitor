<?php
require_once "constants.php";
require_once "ids.php"; // Contains the identifier + Password to connect to RTone web API
require_once "common.php"; 

$db = connect_to_db();


// $_GET should contain serial, start, end 
// ex: http://localhost/~heusse/Monitor/get10mn.php?serial=216670215&start=1512814200&end=1512823800

$reqArgs=array($_GET['serial'],$_GET['serial'],$_GET['start'],$_GET['end']);

// Make sure that there is a data (and maybe 0) for any existing ts in the DB within the time span
$qr="select ? as serial, mru.ts, max(mru.prod) as prod from (select ts,0 as prod from ".tp."readings group by ts union select ts,prod from ".tp."readings where serial=? ) as mru where  (ts between ? and ?) group by ts order by ts";
$select_messages = $db->prepare($qr);
$select_messages->setFetchMode(PDO::FETCH_ASSOC);
$select_messages->execute($reqArgs);
$readings =$select_messages->fetchAll();
header('Content-Type: application/json');
echo json_encode($readings);


?>