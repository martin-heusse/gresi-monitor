<?php

require_once "constants.php";
require_once "ids.php"; // Contains the identifier + Password to connect to RTone web API
require_once "common.php"; 

$MAX_YEARS=10;

function getMonthProd($db,$m,$from,$to){
  $qr="select  sum(prod)/1000 as tot from ".tp."readings where ts between $from and $to and serial=".$m["serial"];
  $select_messages = $db->prepare($qr);
  $select_messages->setFetchMode(PDO::FETCH_ASSOC);
  $select_messages->execute();
  $prod=$select_messages->fetchAll()[0];
  return($prod["tot"]);
}

$db = connect_to_db();

pageHeader(nameAppli()." — Productions annuelles");
?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="./tot.js"> </script>
<?php
echo "<DIV class='sc'><TABLE CLASS='prod'>";

$meters = get_meter_list($db);

$grandTotal=0;

echo "<TR><TD>Compteur</TD><TD>Date</TD>";

for ($i=0;$i<$MAX_YEARS;$i++){
  echo "<TD>". ($i + 1) ."</TD>";
}
echo "</TR>";


foreach($meters as $m){
//ex : serial] => 210000217 [name] => name [localization] => [fisrtts] => 1512994200 [lastts] => 1518526200 [peak_power] => 8.1 [timeoffset] => 1200 
  echo "<TR>";
  echo "<TD>".$m["name"]."</TD>";
  $y0=date("Y",$m["fisrtts"]);
  $m0=date("m",$m["fisrtts"]);
  $d0=date("d",$m["fisrtts"]);
  $from=$m["fisrtts"];
  $y1=$y0+1;
  echo "<TD> $d0/$m0/$y0 </TD>";
  for ($i=0;$i<$MAX_YEARS;$i++){
    $to=strtotime("$y1-$m0-$d0");
    $curprd=getMonthProd($db,$m,$from,$to);
    echo "<TD class=prod$i align='right'> ".round($curprd,1)."    </TD>";
    $grandTotal+=$curprd;
    $from=$to;$y1=$y1+1;
  }
  echo "</TR>\n";
}
echo "<TR><B><TD></TD><TD></TD>";;
for ($i=0;$i<$MAX_YEARS;$i++){
  echo"<TD id=$i class=totProd align='right'></TD>";
}
echo "</B></TR>\n";

echo "</TABLE>";
echo "<BR/><B>Grand Total: ".round($grandTotal)." kWh</B>";
echo "</DIV>";

// Maintenant, par demi-année
echo "<h2>Totaux par demi-année</h2>";
echo "<DIV class='sc'><TABLE CLASS='prod'>";

echo "<TR><TD>Compteur</TD><TD>Date</TD>";

for ($i=0;$i<$MAX_YEARS*2;$i++){
  echo "<TD>". round(($i + 1)/2,0) .(($i + 1)%2?"a":"b")."</TD>";
}
echo "</TR>";


foreach($meters as $m){
//ex : serial] => 210000217 [name] => name [localization] => [fisrtts] => 1512994200 [lastts] => 1518526200 [peak_power] => 8.1 [timeoffset] => 1200 
  echo "<TR>";
  echo "<TD>".$m["name"]."</TD>";
  $y0=date("Y",$m["fisrtts"]);
  $m0=date("m",$m["fisrtts"]);
  $d0=date("d",$m["fisrtts"]);
  $from=$m["fisrtts"];
  $y1=$y0+1;
  echo "<TD> $d0/$m0/$y0 </TD>";
  for ($i=0;$i<$MAX_YEARS;$i++){
    $to=strtotime("$y1-01-01");
    $curprd=getMonthProd($db,$m,$from,$to);
    echo "<TD align='right'> ".round($curprd,1)."    </TD>";
    $from1=$to;
    $to=strtotime("$y1-$m0-$d0");
    $curprd=getMonthProd($db,$m,$from1,$to);
    echo "<TD align='right'> ".round($curprd,1)."    </TD>";
    $from=$to;$y1=$y1+1;
  }
  echo "</TR>\n";
}

echo "</TABLE>";
echo "</DIV>";


pageFoot();
?>