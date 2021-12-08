<?php
require_once "constants.php";
require_once "ids.php"; // Contains the identifier + Password to connect to RTone web API
require_once "common.php"; 

$db = connect_to_db();


// $_GET should contain serial, start, end 
//http://localhost/~heusse/Monitor/getMonthlyProd.php?family=ticpmepmi&serial=8121069742770395288&end=1638903599
// $_GET['end']=1638903599;
// $_GET['family']="ticpmepmi";
// $_GET['serial']=8121069742770395288;

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

header('Content-Type: application/json');

if(strcmp($_GET['family'],"rbee")==0){

  $reqArgs=array($serial,$startts,$endts);

  $qr="select COALESCE(sum(prod),0) as s from ".tp."readings where serial=? and ts between ? and  ? ";
  // Oddly, summing on prod from XXirrad table does not give the good results
  $select_messages = $db->prepare($qr);
  $select_messages->setFetchMode(PDO::FETCH_ASSOC);
  $select_messages->execute($reqArgs);
  $readings =$select_messages->fetchAll();
  $retreadings["rbee_".$serial]=$readings[0]['s'];
}
elseif (strcmp($_GET['family'],"tic")==0){
  $reqArgs=array($serial,$startts);
  $qr="select eait from ".tp."ticreadings where deveui=? and ts>? order by ts limit 1";
  $select_messages = $db->prepare($qr);
  $select_messages->setFetchMode(PDO::FETCH_ASSOC);
  $select_messages->execute($reqArgs);
  $readings =$select_messages->fetchAll();
  $eait1=$readings[0]['eait'];

  $reqArgs=array($serial,$endts);
  $qr="select eait from ".tp."ticreadings where deveui=? and ts<? order by ts desc limit 1";
  $select_messages = $db->prepare($qr);
  $select_messages->setFetchMode(PDO::FETCH_ASSOC);
  $select_messages->execute($reqArgs);
  $readings =$select_messages->fetchAll();
  $eait2=$readings[0]['eait'];

  if(is_null($eait1)) $retreadings["tic_".$serial]=0;
  else $retreadings["tic_".$serial]=$eait2-$eait1;
}
elseif (strcmp($_GET['family'],"ticpmepmi")==0){
  $reqArgs=array($serial,$startts);
//   $qr="select ptcour,max(eait) from ".tp."ticpmepmiindex where deveui=? and UNIX_TIMESTAMP(date)>? order by date limit 1";
//   echo $startts;
//   $select_messages = $db->prepare($qr);
//   $select_messages->setFetchMode(PDO::FETCH_ASSOC);
//   $select_messages->execute($reqArgs);
//   $readings =$select_messages->fetchAll();
//   $eait1=$readings[0]['eait'];
//   
//   $reqArgs=array($serial,$endts);
//   $qr="select 1000*eait from ".tp."ticpmepmiindex where deveui=? and date<FROM_UNIXTIME(?) order by date desc limit 1";
//   $select_messages = $db->prepare($qr);
//   $select_messages->setFetchMode(PDO::FETCH_ASSOC);
//   $select_messages->execute($reqArgs);
//   $readings =$select_messages->fetchAll();
//   $eait2=$readings[0]['eait'];
// 
//   if(is_null($eait1)) $retreadings["ticpmepmi_".$serial]=0;
//   else $retreadings["ticpmepmi_".$serial]=$eait2-$eait1;
  $retreadings["ticpmepmi_".$serial]=0;
}
echo json_encode($retreadings);

?>