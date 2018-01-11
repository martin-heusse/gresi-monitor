<?php
require_once "constants.php";
require_once "ids.php"; // Contains the identifier + Password to connect to RTone web API
require_once "common.php"; 

$db = connect_to_db();


// $_GET should contain serial, start, end 
// ex: http://localhost/~heusse/Monitor/get1h.php?serial=216670215&start=1512814200&end=1512823800

$reqArgs=array($_GET['serial'],$_GET['serial'],$_GET['start'],$_GET['end']);
// $reqArgs=array(216670215,216670215,1512814200,1512823800);

// $qr="select * from ".tp."irrad where serial=? and (ts between ? and ?)";
// Make sure that there is a data (and maybe 0) for any existing ts in the DB within the time span
$qr="select ? as serial, mru.ts, max(mru.prod) as prod ,mru.irrad from (select ts,0 as prod,irrad  from ".tp."irrad group by ts union select ts,prod,irrad from ".tp."irrad where serial=? ) as mru where  (ts between ? and ?) group by ts order by ts";

$select_messages = $db->prepare($qr);
$select_messages->setFetchMode(PDO::FETCH_ASSOC);
$select_messages->execute($reqArgs);
$readings =$select_messages->fetchAll();

header('Content-Type: application/json');
echo json_encode($readings);


?>