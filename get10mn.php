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
}
elseif (strcmp($_GET['family'],"tic")==0){
  $start=$start+60*10;
  $qr="SELECT deveui as serial, ts, eait
  FROM ".tp."ticreadings
  WHERE deveui=?
  AND ts BETWEEN ? and ? order by ts";
  $reqArgs=array($_GET['serial'],$start,$end);
  $select_messages = $db->prepare($qr);
  $select_messages->setFetchMode(PDO::FETCH_ASSOC);
  $select_messages->execute($reqArgs);
  $readings =$select_messages->fetchAll();
  
  $prod=array();
  // find 10mn boundaries following initial ts
  $rounded_start=strtotime(date("Y-m-d\TH:0:0",$start+10*60));
  $mn=date("i",$start);
  $rounded_start = $rounded_start+60*($mn-$mn%10);
   
  if(count($readings)<2) exit ;
  
  $i=1;
  $prev=$readings[$i-1];
  $next=$readings[$i];
  $last_ts=$readings[count($readings)-1]['ts'];
//   print((($last_ts-$rounded_start)/600)."\n");
  for ($t=$rounded_start;$t<$last_ts ;$t+=60*10){
    if($t>$next['ts']&&$i<count($readings)-2) $prev=$readings[$i];
    while($t>$next['ts']) {
      if($i>count($readings)-2) break;
      $i+=1;$next=$readings[$i];
    }
    $pow=($next['eait']-$prev['eait'])/($next['ts']-$prev['ts']);
//     print((($t-$rounded_start)/600)." ".(($prev['ts']-$rounded_start)/600)." ".(($next['ts']-$rounded_start)/600)."\n" );
    $cur_prod=array('serial'=>$_GET['serial'],'ts'=>$t,'prod'=>$pow * 60*10);
    array_push($prod,$cur_prod);
  }
  
  header('Content-Type: application/json');
  echo json_encode($prod);

}
?>