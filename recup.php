<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Demo</title>
</head>

<?php
require_once "constants.php";

//$db = connect_to_db();

// Build API key from login, password, date
date_default_timezone_set("UTC");
$reqdate=date("Y-m-d\TH:i:s");
$string = rbusername.rbpass.$reqdate;

$hash = sha1($string, true);
$hash = base64_encode($hash);
$hash=preg_replace('/\n/', '', $hash);
$hash=preg_replace('/=/', '', $hash);
$hash=preg_replace('/\+/', '-', $hash);
$hash=preg_replace('/\//', '_', $hash);

// echo $string . "  ". $hash."\n" ;

//$reqdate=urlencode($reqdate);

//Build query to retrieve counter serial list
$args= null;
$args['mps']=$hash;
$args['login']=rbusername;
$args['requestDate']=$reqdate ;
$listUrl = url_rb_List.http_build_query($args);

//Build query to retrieve counter data
$args['startDate']=date("Y-m-d\TH:i:s",time()-3600*24*7); // Last week
$args['endDate']= $reqdate;
$args['step']="h" ; // t for ten minutes steps
$args['serialNumber']="" ; //was c1serial -- counter serial number, now dynamically retrieved

$dataUrl = url_rb_ProdRad.http_build_query($args);

// This is how to retrieve the JSON from a url in php:
// not used if the browser js engine does it...
//echo file_get_contents($url);
?>

<body>
<div>Yo !</div>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
  <script src="./monitoring.js"> </script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.4.0/Chart.min.js"></script>
  <input type=hidden id=listUrl value = "<?php print( $listUrl ); ?>"/>
  <input type=hidden id=dataUrl value = "<?php print( $dataUrl ); ?>"/>
  <canvas id="myChart" width="600" height="400"></canvas> 
</body>
</html>
