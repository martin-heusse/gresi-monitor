<?php
require_once "constants.php";
require_once "ids.php"; // Contains the identifier + Password to connect to RTone web API
require_once "common.php";
require_once "helpers.php";

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

if(strcmp($_GET['family'], "rbee")==0) {

  $retreadings["rbee_".$serial] = get_rbee_prod(
    $db,
    $serial,
    new DateTime('@'.$startts),
    new DateTime('@'.$endts)
  );
}
elseif (strcmp($_GET['family'], "tic")==0) {
  $retreadings["tic_".$serial] = get_tic_prod(
    $db,
    $serial,
    new DateTime('@'.$startts),
    new DateTime('@'.$endts)
  );
}
elseif (strcmp($_GET['family'],"ticpmepmi") == 0) {
  $retreadings["ticpmepmi_".$serial] = get_tic_pmepmi_prod(
    $db,
    $serial,
    new DateTime('@'.$startts),
    new DateTime('@'.$endts)
  );
}
echo json_encode($retreadings);

?>