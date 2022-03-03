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

  header('Content-Type: application/json');


  // Build an array of power
  $pow=array();
  for($i=1;$i<count($readings);$i++){
    if($readings[$i]['ts']-$readings[$i-1]['ts']==0) continue;// Ya never know
    $p=($readings[$i]['eait']-$readings[$i-1]['eait'])/($readings[$i]['ts']-$readings[$i-1]['ts']);
    $t=($readings[$i]['ts']+$readings[$i-1]['ts'])/2;
    $cur_pow=array('ts'=>$t,'pow'=>$p);
    array_push($pow,$cur_pow);
  }
//   echo json_encode($pow);
  
  if(count($pow)<2) exit ;
  $last_ts=$readings[count($readings)-1]['ts'];
//   print((($last_ts-$rounded_start)/600)."\n");
  $prev_prod=$pow[0]['pow'];
  $prev_t=$rounded_start;
  for ($t=$rounded_start;$t<$last_ts ;$t+=60*10){
    // Gather measures within this time interval
    $p_sum=0 ;$nb=0; $i=0;
    for($i=0;$i<count($pow);$i++){
//       echo $pow[$i]['ts']-$rounded_start; echo "  "; echo $t-$rounded_start; echo "<BR>";
      if($pow[$i]['ts']>=$t-60*10 && $pow[$i]['ts']<$t){
        $p_sum+=$pow[$i]['pow'];$nb++;
      }
      if($pow[$i]['ts']>=$t) break;
    }
    if($nb>0){$prev_prod=$p_sum/$nb;$prev_t=$t;$this_prod=$prev_prod;}
    else{
// extrapolate!
//       if($i<count($pow)){
//         $next_prod=$pow[$i]['pow'];
//         $next_ts=$pow[$i]['ts'];
//         $this_prod=$prev_prod+($next_prod-$prev_prod)/($next_ts-$prev_t)*($t-$prev_t-5*60);
//       }
// Put NULL (coded by -1)
      $this_prod=-1;
    }
    $cur_prod=array('ts'=>$t,'prod'=>$this_prod * 60*10);

    array_push($prod,$cur_prod);
  }
  
  echo json_encode($prod);

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