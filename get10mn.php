<?php
require_once "constants.php";
require_once "ids.php"; // Contains the identifier + Password to connect to RTone web API
require_once "common.php"; 

ini_set("zlib.output_compression", "On");
ini_set("zlib.output_compression_level", "-1");

$db = connect_to_db();

date_default_timezone_set("UTC");

// $_GET should contain serial, start, end 
// ex: http://localhost/~heusse/Monitor/get10mn.php?family=rbee&serial=216670215&start=1512814200&end=1512823800
//output: [{"serial":"218854693","ts":"1606331400","prod":"0"},{"serial":"218854693","ts":"1606332000","prod":"0"}]

$start=$_GET['start'];
$end=$_GET['end'];


if(strcmp($_GET['family'],"rbee")==0){
  $reqArgs=array($_GET['serial']);

  $qr="SELECT serial, timeoffset from ".tp."meters WHERE serial=?";
  $select_messages = $db->prepare($qr);
  $select_messages->setFetchMode(PDO::FETCH_KEY_PAIR);
  $select_messages->execute($reqArgs);
  $offset =($select_messages->fetchAll())[$_GET['serial']];

  $start = $start-$offset;
  $end = $end-$offset;

  // Round start/end to tens minutes in order to match
  // DB when generating missing ts
  $start = 600 * ceil($start / 600);

  header('Content-Type: application/json');
  echo json_encode(get_readings_rbee($start, $end, 600));
}
elseif (strcmp($_GET['family'],"tic")==0){
    echo json_encode(get_readings_tic($start, $end, 600));
}
elseif (strcmp($_GET['family'],"ticpmepmi")==0){
  $reqArgs=array($_GET['serial']);

  $start = $start-$offset;
  $end = $end-$offset;

  // Round start/end to tens minutes in order to match
  // DB when generating missing ts
  $start = 600 * ceil($start / 600);

  header('Content-Type: application/json');
  echo json_encode(get_readings_ticpmepmi($start, $end, 600));
}
?>