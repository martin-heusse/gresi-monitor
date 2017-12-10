<?php
require_once "constants.php";
require_once "ids.php"; // Contains the identifier + Password to connect to RTone web API, + to connect to DB
require_once "common.php"; 

$db = connect_to_db();

// Build API key from login, password, date
date_default_timezone_set("UTC");
$theTime=time();
$reqdate=date_to_str($theTime);
$string = rbusername.rbpass.$reqdate;

$hash = sha1($string, true);
$hash = base64_encode($hash);
$hash=preg_replace('/\n/', '', $hash);
$hash=preg_replace('/=/', '', $hash);
$hash=preg_replace('/\+/', '-', $hash);
$hash=preg_replace('/\//', '_', $hash);

// echo $string . "  ". $hash."\n" ;

//$reqdate=urlencode($reqdate);


$meterList = json_decode(file_get_contents(build_qr_list($hash,$reqdate))); // returns an object with a single property called "list"
pace();

// fixme remove this !!
$meterList->list = array_slice($meterList->list,count($meterList)-5);

foreach ($meterList->list as $serial){
  // First get all meters that belong to us
  $meterInfo=get_dev_info($hash,$reqdate,$serial);
  var_dump($meterInfo);
  // Is it activated?
  if ($meterInfo->lastIndexDate){ // NULL if never retrieved
    $lastIndexDate = strtotime($meterInfo->lastIndexDate) ; echo "\n";// convert to unix timestamp
    $tsInDB = get_meter_lastts($serial,$db); echo "tsInDB : ". $tsInDB . " lastIndexDate:" . $lastIndexDate . "\n";
    if($tsInDB == 0){
      echo "First retrieval -- ts in db: $tsInDB\n " ;
      $startts = strtotime($meterInfo->firstConnectionDate)-24*3600; // hoping the first data retrieval occured in first 24h
    }
    else{
      $startts = $tsInDB+1 ; // Just after last timestamp in DB
    }
    $lastTime=strtotime($meterInfo->lastIndexDate);
    $endts=$lastTime>$startts+7*24*3600?$startts+7*24*3600:$lastTime; // should not fetch more than 1 week at a time
    echo "endts : $endts lastTime $lastTime\n";
    while($startts<$lastTime){ // $lastTime
      // loop to retrieve one week at a time
      echo "retrieving from ".$startts." to ". $endts. "=" . ($endts-$startts)/24/3600 . "j\n";
      retrieve_and_insert($hash,$reqdate, $serial,$startts,$endts,$db);
      $startts = $endts+1; // let's not retrieve twice the same data
      $endts=$lastTime>$startts+7*24*3600?$startts+7*24*3600:$lastTime;
    }
    // Fix radiation table, which may be false around the last retrieval during the day
    fix_irrad($hash,$reqdate, $serial,$theTime-38*3600,$theTime,$db);
    
    // work done
    set_meter_lastts($serial,$db,$lastTime);
    echo "$serial done \n\n";
    if($tsInDB==0){
      update_db_init_meter($serial,$meterInfo,$db);
    }
  }
}

function get_dev_info($hash,$reqdate,$sn=null){
  $meterInfo=json_decode(file_get_contents(build_qr_info($hash,$reqdate,$sn)));
  pace();
  return $meterInfo;
}

function get_meter_lastts($serial,$_db){
  $qr = "SELECT serial,lastts FROM ".tp."meters WHERE serial='$serial'";
  $select_messages = $_db->prepare($qr);
  $select_messages->setFetchMode(PDO::FETCH_KEY_PAIR);
  $select_messages->execute();
  $res =$select_messages->fetchAll();
  if(!count($res)){
    $qr = "insert into ".tp."meters values ($serial , '', '', 0, 0, 0)";
    echo "$qr\n";
    $insert_stmt = $_db->prepare($qr);
    $insert_stmt->execute();
    return 0;
  }
  var_dump($res[$serial]);
  return $res[$serial];
}
function set_meter_lastts($serial,$_db,$ts){
  $qr = "UPDATE ".tp."meters set lastts=? WHERE serial=$serial";
  $update_messages = $_db->prepare($qr);
  $update_messages->execute(array($ts));
}


function base_args($hash,$reqdate){
  $args['mps']=$hash;
  $args['login']=rbusername;
  $args['requestDate']=$reqdate ;
  return $args;
}

function build_qr_list($hash,$reqdate){
  //Build query to retrieve counter serial list
  return url_rb_List.http_build_query(base_args($hash,$reqdate));
}

function build_qr_info($hash,$reqdate,$serial){
  $args=base_args($hash,$reqdate);
  $args['serialNumber']=$serial; 
  return url_rb_Info.http_build_query($args);
}

function build_qr_1h($hash,$reqdate,$serial,$startts,$endts){
  $args=base_args($hash,$reqdate);
  $args['serialNumber']=$serial; 
  $args['startDate']=date_to_str($startts);
  $args['endDate']= date_to_str($endts);
  $args['step']="h" ; // 1h steps
  return url_rb_ProdRad.http_build_query($args);
}

function build_qr_10mn($hash,$reqdate,$serial,$startts,$endts){
  $args=base_args($hash,$reqdate);
  $args['serialNumber']=$serial; 
  $args['startDate']=date_to_str($startts);
  $args['endDate']= date_to_str($endts);
  $args['step']="tenmin" ; // ten minutes steps
  return url_rb_Prod.http_build_query($args);
}

function update_db_init_meter($serial,$meterInfo,$db){
  $qr = "UPDATE ".tp."meters set peak_power=?, name=? WHERE serial=$serial";
  $update_messages = $db->prepare($qr);
  $update_messages->execute(array($meterInfo->peakPower,$serial));
 
  $qr="select serial,min(ts) from monitorreadings where serial=$serial";
  $select_messages = $db->prepare($qr);
  $select_messages->setFetchMode(PDO::FETCH_KEY_PAIR);
  $select_messages->execute();
  $res =$select_messages->fetchAll();
  if($res[$serial]>0){
    $qr = "UPDATE ".tp."meters set fisrtts=? WHERE serial=$serial";
    $update_messages = $db->prepare($qr);
    $update_messages->execute(array($res[$serial]));
  }
}

function retrieve_and_insert($hash,$reqdate, $serial,$startts,$endts,$db){
  // First retrieve + insert for 10mn steps
  $r=json_decode(file_get_contents(build_qr_10mn($hash,$reqdate, $serial,$startts,$endts)));
  pace();
  $qr = "insert into ".tp."readings values ($serial , ?, ?)";//serial , ts , prod10 
  $insert_stmt = $db->prepare($qr);

  foreach($r->records as $x => $y ){
    // $y has two components : measureDate and measure
    if($y->measure>=0){
      $insert_stmt->execute(array(strtotime($y->measureDate),$y->measure));
    }
  }
  
  // Then for 1h
  
  $r=json_decode(file_get_contents(build_qr_1h($hash,$reqdate, $serial,$startts,$endts)));
  pace();
  $qr = "insert into ".tp."irrad values ($serial , ?, ?, ?)";//serial , ts , prod1h, irrad
  $insert_stmt = $db->prepare($qr);
  foreach($r->records as $x ){
    // $x has 3 components : measureDate, measure and radiation
    if($x->measure>=0){
      $insert_stmt->execute(array(strtotime($x->measureDate),$x->measure,$x->radiation));
    }
  }
}

function fix_irrad($hash,$reqdate, $serial,$startts,$endts,$db){
  $r=json_decode(file_get_contents(build_qr_1h($hash,$reqdate, $serial,$startts,$endts)));
  pace();
  $qr = "update ".tp."irrad set prod=?, irrad=? where serial=$serial and ts=? and irrad=0.0";//serial , ts , prod1h, irrad
  $insert_stmt = $db->prepare($qr);
  foreach($r->records as $x ){
    // $x has 3 components : measureDate, measure and radiation
    if($x->measure>=0){
      $insert_stmt->execute(array($x->measure,$x->radiation,strtotime($x->measureDate)));
    }
  }
}
?>

