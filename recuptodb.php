<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Demo</title>
  <STYLE type="text/css">
  DIV.sc {font-family: Sans-Serif;}
 </STYLE>
</head>

<?php
require_once "constants.php";
require_once "ids.php"; // Contains the identifier + Password to connect to RTone web API
require_once "common.php"; 

$db = connect_to_db();

// Build API key from login, password, date
date_default_timezone_set("UTC");
$reqdate=date_to_str(time());
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
echo build_qr_list($hash,$reqdate);

// fixme remove this !!
$meterList->list = array_slice($meterList->list,count($meterList)-4);

foreach ($meterList->list as $serialNum){
  get_dev_info($hash,$reqdate,$serialNum);
  pace();
}

function get_dev_info($hash,$reqdate,$sn=null){
  $meterInfo=json_decode(file_get_contents(build_qr_info($hash,$reqdate,$sn)));
  pace();  
  if ($meterInfo->lastIndexDate){ // NULL if never retrieved
    echo strtotime($meterInfo->lastIndexDate) ; echo "\n";// convert to unix timestamp
    $endts=time();
    $startts=$endts-3600*12;
    var_dump(file_get_contents(build_qr_10mn($hash,$reqdate,$sn,$startts,$endts)));
    pace();
  }
}

function get_meter_lastts($serial,$_db){
  $qr = "SELECT lastts FROM ".tp."meters WHERE serial='$serial'";
  $select_messages = $_db->prepare($query);
  $select_messages->setFetchMode(PDO::FETCH_ASSOC);
  $select_messages->execute();
  $res =$select_messages->fetchAll();
  return $res[0];
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



?>

