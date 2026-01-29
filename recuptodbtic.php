<?php
require_once "constants.php";
require_once "ids.php"; // Contains the identifier + Password to connect to RTone web API, + to connect to DB
require_once "common.php"; 

$initialNbWeeks = 1; // if 0, retrieve from first meter connection. Otherwise, just get last $initialNbWeeks weeks

$db = connect_to_db();
if(is_null($db)) exit;

date_default_timezone_set("UTC");

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
  $hex_eui=sprintf("%'016X",$dec_eui);
  if(!(preg_match('/^70B3D5/',$hex_eui) || preg_match('/^0018B2/',$hex_eui)))
    continue;
  $more_data=TRUE;
  $to_date=t_str_chg(date(DateTime::ISO8601));
  print($to_date);print("\n");
  $from_date=t_str_chg(date(DateTime::ISO8601,strtotime("2000-01-01")));
  print($from_date); print "\n";
  while($more_data){
    $httpreq=build_qr($hex_eui,$from_date,$to_date);
    print($httpreq."\n");
    $r=json_decode(file_get_contents_lo($httpreq));
    $p_data=array();
    foreach($r as $packet){
      $p_data[$packet->timestamp]=$packet->value->payload;
    }

    if(preg_match('/^70B3D5/',$hex_eui)){// NKE watteco
    // Verify the type of packet and only keeps the good ones
    $data_ok=preg_grep("/^110a00560000411b250/",$p_data);
      if(count($data_ok)==0) $more_data=FALSE;
      foreach($data_ok as $ts=>$p){//loop on packets
        $eait=1.0*hexdec(substr($p, -10,8));
        $east=1.0*hexdec(substr($p, -18,8));
        $t=strtotime($ts);
        if($t<strtotime($to_date)) {$to_date=$ts;}
        $insert_stmt->execute(array($dec_eui,$t,$eait,$east));
        // inserting will fail if data already exists.
        if($insert_stmt->rowCount()==0){$more_data = FALSE;}
      }
    }
    else if(preg_match('/^0018B2/',$hex_eui)){// Adeunis
      $data_ok=preg_grep("/^49/",$p_data);
      if(count($data_ok)==0) $more_data=FALSE;
      foreach($data_ok as $ts=>$p){//loop on packets
        $eait=1.0*hexdec(substr($p, -16,8));
        $east=1.0*hexdec(substr($p, -8,8));
        $t=strtotime($ts);
//      print_r($ts."  ".$eait."  ".$east." ".$t."\n");
        if($t<strtotime($to_date)) {$to_date=$ts;}
        $insert_stmt->execute(array($dec_eui,$t,$eait,$east));
        // inserting will fail if data already exists.
        if($insert_stmt->rowCount()==0){$more_data = FALSE;}
      }
    }
  }
//  update fisrtts, lastts
  $qr = "update ".tp."ticmeters set fisrtts=(select min(ts) from ".tp."ticreadings where deveui=$dec_eui) where deveui=$dec_eui and fisrtts is null";
  $select_messages = $db->prepare($qr);
  $select_messages->execute();
  $qr = "update ".tp."ticmeters set lastts=(select max(ts) from ".tp."ticreadings where deveui=$dec_eui) where deveui=$dec_eui";
  $select_messages = $db->prepare($qr);
  $select_messages->execute();
}

echo("PMEPMI\n");
########### Now PMEPMI sensors
$qr="select deveui from ".tp."ticpmepmimeters";
$select_messages = $db->prepare($qr);
$select_messages->setFetchMode(PDO::FETCH_ASSOC);
$select_messages->execute();
$res =$select_messages->fetchAll();

$qr_insert_pi="insert into ".tp."ticpmepmireadings values (? , ?, ?)"; //deveui as int, ts , mean_power
$insert_stmt_pi = $db->prepare($qr_insert_pi);
$qr_insert_index="insert into ".tp."ticpmepmiindex values (? ,  STR_TO_DATE(?, '%Y-%m-%e'), ?, ?, ?)"; //deveui as int, date , eait, east /// ON DUPLICATE KEY UPDATE eait=?
$insert_stmt_index = $db->prepare($qr_insert_index);

if($initialNbWeeks>0){
  $hist_limit=mktime(0, 0, 0, date("m"), date("d")-7*$initialNbWeeks,   date("Y"));
}
else{$hist_limit=mktime(0, 0, 0, 1,1,2000);}
print($hist_limit."\n");
foreach($res as $cur_res){
  $dec_eui=$cur_res['deveui'];
  $hex_eui=sprintf("%'016X",$dec_eui);
  $more_data=TRUE;
  $to_date=t_str_chg(date(DateTime::ISO8601));
  print("to_date:".$to_date);print("\n");
  $from_date=t_str_chg(date(DateTime::ISO8601,strtotime("2000-01-01")));
  print("from_date:".$from_date); print "\n";
  while($more_data && strtotime($to_date)>$hist_limit){
    print('ktime(strtotime($to_date)) : '.strtotime($to_date)."\n");
    $httpreq=build_qr($hex_eui,$from_date,$to_date);
    print($httpreq."\n");
    $r=json_decode(file_get_contents_lo($httpreq));
    $p_data=array();
    $nb_inserted_n=0;$nb_inserted_a=0;
    $more_data_n=TRUE;
    $more_data_a=TRUE;
    
    foreach($r as $packet){
      $p_data[$packet->timestamp]=$packet->value->payload;
    }
    if(preg_match('/^70B3D5/',$hex_eui)){// NKE watteco  
      // Verify the type of packet and only keeps the good ones
      $data_ok=preg_grep("/^110a005700004120070/",$p_data);
      // The TIC info is in local time
      date_default_timezone_set("Europe/Paris");
      foreach($data_ok as $ts=>$p){//loop on packets
        $str_day=hexdec(substr($p, 32,2));
        $str_month=hexdec(substr($p, 34,2));
        $str_year=hexdec(substr($p, 36,2));
        $str_h=hexdec(substr($p, 38,2));
        $str_mn=hexdec(substr($p, 40,2));
        $dat_date=strtotime("20$str_year-$str_month-$str_day $str_h:$str_mn:00");
        for($i=0;$i<6;$i++){
          $t=$dat_date-$i*10*60; //Go back in time 10mn for each data
          $pi = hexdec(substr($p, 44+$i*4,4));
          $insert_stmt_pi->execute(array($dec_eui,$t,$pi));
          if($insert_stmt_pi->rowCount()==0){$more_data_n = FALSE;}
          else {$nb_inserted_n++;}

        }
        // update $to_date for next query, before rounding it for index storage
        $t=strtotime($ts);
        if($t<strtotime($to_date)) {$to_date=$ts;}
  
        // Now retrieve index
        $str_date="20$str_year-$str_month-$str_day";
        $eait=1.0*hexdec(substr($p, -6,6));
        $east=1.0*hexdec(substr($p, -12,6));
        $ptcour=hexdec(substr($p, 30,2)); # 7 -> HCE, 14 -> HPE, 18 -> P
        $insert_stmt_index->execute(array($dec_eui,$str_date,$ptcour,$eait,$east));
      }
    }
    else if(preg_match('/^0018B2/',$hex_eui)){// Adeunis
      $data_ok=preg_grep("/^49/",$p_data);
      foreach($data_ok as $ts=>$p){//loop on packets
        $str_day=hexdec(substr($p, 10,2));
        $str_month=hexdec(substr($p, 12,2));
        $str_year=hexdec(substr($p, 14,2));
        $str_h=hexdec(substr($p, 16,2));
        $str_mn=hexdec(substr($p, 18,2));
        $dat_date=strtotime("20$str_year-$str_month-$str_day $str_h:$str_mn:00");
        for($i=0;$i<6;$i++){
          $t=$dat_date-$i*10*60; //Go back in time 10mn for each data
          $pi = hexdec(substr($p, 22+$i*8,8));
          $insert_stmt_pi->execute(array($dec_eui,$t,$pi));
          if($insert_stmt_pi->rowCount()==0){$more_data_a = FALSE;}
          else {$nb_inserted_a++;}
        }
        // update $to_date for next query, before rounding it for index storage
        $t=strtotime($ts);
        if($t<strtotime($to_date)) {$to_date=$ts;}
        // Now retrieve index
        $str_date="20$str_year-$str_month-$str_day";
        $eait=1.0*hexdec(substr($p, -8,8));
        $east=1.0*hexdec(substr($p, -16,8));
        $ptcourstr=hex2bin(substr($p, 4,6)); # 7 -> HCE, 14 -> HPE, 18 -> P
        switch(true){
          case preg_match("/HCE/",$ptcourstr):
            $ptcour=7; break;
          case preg_match("/HCH/",$ptcourstr):
            $ptcour=8; break;
          case preg_match("/HPE/",$ptcourstr):
            $ptcour=14; break;
          case preg_match("/HPH/",$ptcourstr):
            $ptcour=15; break;
          default:
            $ptcour=18;
        }
        $insert_stmt_index->execute(array($dec_eui,$str_date,$ptcour,$eait,$east));
      }
    }
    if(($nb_inserted_n>0 && $more_data_n) || ($nb_inserted_a>0 && $more_data_a)) $more_data=TRUE; else $more_data=FALSE;
  }
//  update fisrtts, lastts
  $qr = "update ".tp."ticpmepmimeters set fisrtts=(select min(ts) from ".tp."ticpmepmireadings where deveui=$dec_eui) where deveui=$dec_eui and fisrtts is null";
  $select_messages = $db->prepare($qr);
  $select_messages->execute();
  $qr = "update ".tp."ticpmepmimeters set lastts=(select max(ts) from ".tp."ticpmepmireadings where deveui=$dec_eui) where deveui=$dec_eui";
  $select_messages = $db->prepare($qr);
  $select_messages->execute();

}
?>