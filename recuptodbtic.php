<?php
require_once "constants.php";
require_once "ids.php"; // Contains the identifier + Password to connect to RTone web API, + to connect to DB
require_once "common.php"; 

$initialNbWeeks = 2; // if 0, retrieve from first meter connection. Otherwise, just get last $initialNbWeeks weeks

$db = connect_to_db();
if(is_null($db)) exit;

date_default_timezone_set("UTC");

function convBase($numberInput, $fromBaseInput, $toBaseInput)
{
    if ($fromBaseInput==$toBaseInput) return $numberInput;
    $fromBase = str_split($fromBaseInput,1);
    $toBase = str_split($toBaseInput,1);
    $number = str_split($numberInput,1);
    $fromLen=strlen($fromBaseInput);
    $toLen=strlen($toBaseInput);
    $numberLen=strlen($numberInput);
    $retval='';
    if ($toBaseInput == '0123456789')
    {
        $retval=0;
        for ($i = 1;$i <= $numberLen; $i++)
            $retval = bcadd($retval, bcmul(array_search($number[$i-1], $fromBase),bcpow($fromLen,$numberLen-$i)));
        return $retval;
    }
    if ($fromBaseInput != '0123456789')
        $base10=convBase($numberInput, $fromBaseInput, '0123456789');
    else
        $base10 = $numberInput;
    if ($base10<strlen($toBaseInput))
        return $toBase[$base10];
    while($base10 != '0')
    {
        $retval = $toBase[bcmod($base10,$toLen)].$retval;
        $base10 = bcdiv($base10,$toLen,0);
    }
    return $retval;
}

function build_qr($hex_eui,$from,$to){
  $args=array();
//   $args=base_args();
//   $args['serialNumber']=$serial; 
//   $args['startDate']=date_to_str($startts);
//   $args['endDate']= date_to_str($endts);
//   $args['step']="h" ; // 1h steps
  $args['timeRange']="$from,$to" ; // 1h steps
  return url_lo.$hex_eui."?".http_build_query($args);
}

function t_str_chg($instr){
  return preg_replace("/\+/",".",$instr)."Z";
}

function file_get_contents_lo($s){
  $arrContextOptions=array(
      "http"=>array(
                'method'=>"GET",
                'header'=>loapikey)
//       "ssl"=>array(
//           "verify_peer"=>false,
//           "verify_peer_name"=>false)
              );
  return file_get_contents($s, false, stream_context_create($arrContextOptions));
}

$qr="select deveui from ".tp."ticmeters";
$select_messages = $db->prepare($qr);
$select_messages->setFetchMode(PDO::FETCH_ASSOC);
$select_messages->execute();
$res =$select_messages->fetchAll();

$qr_insert="insert into ".tp."ticreadings values (? , ?, ?, ?)"; //deveui as int, ts , eait , east 
$insert_stmt = $db->prepare($qr_insert);
// loop on meters
foreach($res as $cur_res){
  $dec_eui=$cur_res['deveui'];
  $hex_eui=convbase($dec_eui,'0123456789','0123456789ABCDEF');
  $more_data=TRUE;
  $to_date=t_str_chg(date(DateTime::ISO8601));
  print($to_date);print("\n");
  $from_date=t_str_chg(date(DateTime::ISO8601,strtotime("2000-01-01")));
  print_r($from_date); print "\n";
  while($more_data){
    $httpreq=build_qr($hex_eui,$from_date,$to_date);
    print($httpreq."\n");
    $r=json_decode(file_get_contents_lo($httpreq));
    $p_data=array();
    foreach($r as $packet){
      $p_data[$packet->timestamp]=$packet->value->payload;
    }
    // Verify the type of packet and only keeps the good ones
    $data_ok=preg_grep("/^110a00560000411b250/",$p_data);
    if(count($data_ok)==0) $more_data=FALSE;

    foreach($data_ok as $ts=>$p){//loop on packets
      $eait=1.0*hexdec(substr($p, -10,8));
      $east=1.0*hexdec(substr($p, -18,8));
      $t=strtotime($ts);
      if($t<strtotime($to_date)) {$to_date=$ts;}
      // inserting will fail if data already exists.
      if(!$insert_stmt->execute(array($dec_eui,$t,$eait,$east))){$more_data=FALSE;}
    }
    print_r($ts);print("\n");
  }
//  update fisrtts, lastts
  $qr = "update ".tp."ticmeters set fisrtts=(select min(ts) from ".tp."ticreadings where deveui=$dec_eui) where deveui=$dec_eui";
  $select_messages = $db->prepare($qr);
  $select_messages->execute();
  $qr = "update ".tp."ticmeters set lastts=(select max(ts) from ".tp."ticreadings where deveui=$dec_eui) where deveui=$dec_eui";
  $select_messages = $db->prepare($qr);
  $select_messages->execute();

}
?>