<?php

require_once "constants.php";
require_once "ids.php"; // Contains the identifier + Password to connect to RTone web API
require_once "common.php"; 

$MAX_YEARS=10;

function getMeterProd($db,$m,$from,$to){
  if(strcmp($m['family'],"rbee")==0){
    $qr="select  sum(prod)/1000 as tot from ".tp."readings r where r.ts between $from and $to and r.serial=".$m["serial"];
    $select_messages = $db->prepare($qr);
    $select_messages->setFetchMode(PDO::FETCH_ASSOC);
    $select_messages->execute();
    $prod=$select_messages->fetchAll()[0];
    $total=$prod["tot"];
    $qr="select  sum(prod)/1000 as tot from ".tp."readings r,".tp."disabled d where r.ts between $from and $to and  (r.serial=d.replacedby and d.serial=".$m["serial"].")";
    $select_messages = $db->prepare($qr);
    $select_messages->setFetchMode(PDO::FETCH_ASSOC);
    $select_messages->execute();
    $prod=$select_messages->fetchAll()[0];
    $total+=$prod["tot"];
  }
  else if(strcmp($m['family'],"tic")==0){
    $reqArgs=array($m['serial'],$from);
    $qr="select eait from ".tp."ticreadings where deveui=? and ts>? order by ts limit 1";
    $select_messages = $db->prepare($qr);
    $select_messages->setFetchMode(PDO::FETCH_ASSOC);
    $select_messages->execute($reqArgs);
    $readings =$select_messages->fetchAll();
    $eait1=$readings[0]['eait'];

    $reqArgs=array($m['serial'],$to);
    $qr="select eait from ".tp."ticreadings where deveui=? and ts<? order by ts desc limit 1";
    $select_messages = $db->prepare($qr);
    $select_messages->setFetchMode(PDO::FETCH_ASSOC);
    $select_messages->execute($reqArgs);
    $readings =$select_messages->fetchAll();
    $eait2=$readings[0]['eait'];

    if(is_null($eait1)) $total=0;
    else $total=($eait2-$eait1)/1000;
    if ($total<0) $total=NULL;
  }
  return($total);
}

function tableHead(){
  echo "<DIV class='sc'><TABLE CLASS='prod'>";
  echo "<TR><TD>Compteur</TD><TD>Date</TD>";
}
function tableFoot(){
  echo "</TABLE>";
  echo "</DIV>";
}

$db = connect_to_db();

pageHeader(nameAppli()." — Productions annuelles");
?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="./tot.js"> </script>
<?php

$meters = get_meter_list_orig($db);

// Par demi-année
echo "<h2>Totaux par demi-année</h2>";

// Année courante
$yn=date("Y");
// Première année ? sera $ys
$ys=$yn;
foreach($meters as $m){
  $y=date("Y",$m["fisrtts"]);
  if($y<$ys){$ys=$y;};
}
$nbYears=$yn-$ys+1;


tableHead();
$tab='t2';

for ($i=0;$i<$nbYears;$i++){
  echo "<TD>".($ys+$i)."a</TD>";
  echo "<TD>".($ys+$i)."b</TD>";
}
echo "</TR>";


foreach($meters as $m){
//ex :  [family] => rbee [serial] => 211412449 [name] => 211412449 [fisrtts] => 1604151600 [lastts] => 1634083200 [peak_power] => 9 [timeoffset] => 0
  echo "<TR>";
  echo "<TD>".$m["name"]."</TD>";
  $y0=date("y",$m["fisrtts"]);
  $m0=date("m",$m["fisrtts"]);
  $d0=date("d",$m["fisrtts"]);
  echo "<TD> $d0/$m0/$y0 </TD>";
  $y0=$ys;
  for ($i=0;$i<$nbYears;$i++){
    $from=strtotime("$y0-01-01");
    $to=strtotime("$y0-$m0-$d0");
    $curprd=getMeterProd($db,$m,$from,$to);
    $j=2*$i;
    echo "<TD class=prod$j$tab align='right'> ".round($curprd,1)."    </TD>";
    $from=$to;
    $y1=$y0+1;
    $to=strtotime("$y1-01-01");
    $curprd=getMeterProd($db,$m,$from,$to);
    $j=2*$i+1;
    echo "<TD class=prod$j$tab align='right'> ".round($curprd,1)."    </TD>";
    $y0=$y1;
  }
  echo "</TR>\n";
}

tableFoot();

echo "<h2>Totaux par année civile</h2>";
tableHead();
$tab='t3';

for ($i=0;$i<$nbYears;$i++){
  echo "<TD>".($ys+$i)."</TD>";
}
echo "</TR>";
foreach($meters as $m){
  echo "<TR>";
  echo "<TD>".$m["name"]."</TD>";
  $y0=date("Y",$m["fisrtts"]);
  $m0=date("m",$m["fisrtts"]);
  $d0=date("d",$m["fisrtts"]);
  $from=$m["fisrtts"];
  echo "<TD> $d0/$m0/$y0 </TD>";

  for ($i=0;$i<$nbYears;$i++){
    $y1=$ys+$i;
    $y2=$y1+1;
    $from=strtotime("$y1-01-01");$to=strtotime("$y2-01-01");
    $curprd=getMeterProd($db,$m,$from,$to);
    echo "<TD class=prod$i$tab align='right'> ".round($curprd,1)."    </TD>";
  }
  echo "</TR>\n";
}
echo "<TR><B><TD></TD><TD></TD>";;
for ($i=0;$i<$nbYears;$i++){
  echo"<TD id=$i$tab class=totProd align='right'></TD>";
}
echo "</TR>";
tableFoot();



echo "<h2>Totaux par année d'exploitation</h2>";

$grandTotal=0;

tableHead();

for ($i=0;$i<$MAX_YEARS;$i++){
  echo "<TD>". ($i + 1) ."</TD>";
}
echo "</TR>";

$tab='t1';
foreach($meters as $m){
//ex : serial] => 210000217 [name] => name [localization] => [fisrtts] => 1512994200 [lastts] => 1518526200 [peak_power] => 8.1 [timeoffset] => 1200 
  echo "<TR>";
  echo "<TD>".$m["name"]."</TD>";
  $y0=date("Y",$m["fisrtts"]);
  $m0=date("m",$m["fisrtts"]);
  $d0=date("d",$m["fisrtts"]);
  $from=$m["fisrtts"];
  echo "<TD> $d0/$m0/$y0 </TD>";
  $y1=$y0+1;
  for ($i=0;$i<$MAX_YEARS;$i++){
    $to=strtotime("$y1-$m0-$d0");
    $curprd=getMeterProd($db,$m,$from,$to);
    echo "<TD class=prod$i$tab align='right'> ".round($curprd,1)."    </TD>";
    $grandTotal+=$curprd;
    $from=$to;$y1=$y1+1;
  }
  echo "</TR>\n";
}

tableFoot();
echo "<BR/><DIV class='sc'><B>Grand Total: ".number_format(round($grandTotal), 2, ',', ' ')." kWh</B></DIV>";

pageFoot();


?>