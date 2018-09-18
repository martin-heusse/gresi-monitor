<?php
require_once "constants.php";
require_once "ids.php"; // Contains the identifier + Password to connect to RTone web API, + to connect to DB
require_once "common.php"; 

$db = connect_to_db();


# Parameters for alarm
$maxNbHoursSinceUpdate=12;
$nb_hours_prod=18;
$thresh_null_readings=9; #1 unit = 10mn, 9 -> 1h30'

# Needs the define of mailalertsubject mailadmin

date_default_timezone_set("UTC");

$meters = get_meter_list($db);

$now=time(); # Current unix timestamp


# First check if a meter is way behind
$outdated_meters=[];
foreach($meters as $m){
  $deltat=($now - $m["lastts"])/3600;
  if($deltat>$maxNbHoursSinceUpdate){
    array_push($outdated_meters,$m["name"]." (".number_format($deltat,2,",",".")."h)");
  }
}

$update_pb_str="";
if(count($outdated_meters)){$update_pb_str = implode(', ',$outdated_meters )." n'ont (n'a) pas été mis à jour depuis au moins ".$maxNbHoursSinceUpdate." heures.\n";}


#See if a meter did not return a little bit too many 0s
#First find the fist and last timestamps with non-null production in our time window (In fact > 1, as there are sometime glitches with 1 unit at random times)
$timeWinStart=$now-$nb_hours_prod*3600;

$qr="select min(envelop.time) as tsi, max(envelop.time) as tsf from (select  (r.ts+m.timeoffset) as time, max(r.prod/m.peak_power) as maxprod from ".tp."readings r, ".tp."meters m  where r.ts>$timeWinStart and r.serial=m.serial group by time) as envelop where envelop.maxprod>1";
$select_messages = $db->prepare($qr);
$select_messages->setFetchMode(PDO::FETCH_ASSOC);
$select_messages->execute();
$daylight=($select_messages->fetchAll())[0];
$tsi=$daylight["tsi"]; $tsf=$daylight["tsf"];

if($tsi!=NULL && $tsf!=NULL){
  #Find how many zero production ts we have during that time
  $qr="select serial,count(distinct ts) as nbval  from ".tp."readings where prod=0 and ts>? and ts<? and serial=?";
  $select_messages->setFetchMode(PDO::FETCH_KEY_PAIR);
  $select_messages = $db->prepare($qr);
  $zero_prod_meters=[];
  foreach($meters as $m){
    # time offset in the _other_ direction !!
    $select_messages->execute(array($tsi-$m["timeoffset"],$tsf-$m["timeoffset"],$m["serial"]));
    $nb_zero=($select_messages->fetchAll())[0][1];
    if($nb_zero>$thresh_null_readings){
      array_push($zero_prod_meters,$m["name"]." : ".number_format($nb_zero/6,2,",",".")." heures.");
    }
  }
  if(count($zero_prod_meters)){
    $update_pb_str="Alerte production nulle : ".implode(', ',$zero_prod_meters )."\n\n". $update_pb_str;
  }
}
if(strlen($update_pb_str)){
  mail(mailadmin,mailalertsubject,$update_pb_str,prepare_header());
}

?>