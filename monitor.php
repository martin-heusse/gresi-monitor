<?php
require_once "monitor-constants.php";
require_once "common.php";

define('listUrl',baseUrl.'getMeters.php');
define('dataUrl1h',baseUrl.'get1h.php');
define('dataUrl10mn',baseUrl.'get10mn.php');
define('dataUrlMonth',baseUrl.'getMonthlyProd.php');


pageHeader("Monitoring");
date_default_timezone_set("Europe/Paris");
?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.4.0/Chart.min.js"></script>
<script src="./monitor.js"> </script>


<?php

function shiftday(&$ed,$shiftval){
    if(strlen($ed)>0){
        $edstr=strtotime($ed)+3600*24*$shiftval;
        $ed=date("Y-m-d",$edstr);
    }
    else{
        $ed=date("Y-m-d",time()+3600*24*$shiftval);
    }
}

if (isset($_POST['enddate'])){
    $enddate=$_POST['enddate'];
}
else $enddate="";

if (isset($_POST['prevday']))
    shiftday($enddate,-1);
if (isset($_POST['nextday']))
    shiftday($enddate,+1);
if (isset($_POST['prevweek']))
    shiftday($enddate,-7);
if (isset($_POST['nextweek']))
    shiftday($enddate,+7);

?>

<input type=hidden id=listUrl value = "<?php print( listUrl ); ?>"/>
<input type=hidden id=dataUrl1h value = "<?php print( dataUrl1h ); ?>"/>
<input type=hidden id=dataUrl10mn value = "<?php print( dataUrl10mn ); ?>"/>
<input type=hidden id=dataUrlMonth value = "<?php print( dataUrlMonth ); ?>"/>

<div class="sc" style="width:80%; padding:6px;">
<?php header_form(basename(__FILE__));?>
  Afficher la semaine se terminant le:
  <input type="date" id="enddate" name="enddate" value="<?php echo $enddate;?>"/>
  <input type="submit"/>
  <input type="submit" name="prevweek" value="<<"/>
  <input type="submit" name="prevday" value="<"/>
  <input type="submit" name="nextday" value=">"/>
  <input type="submit" name="nextweek" value=">>"/>
</form>
</div>

<div id="status" class="sc ib" style="display:none;"><span id="progress"></span><span id="progressEnd"></span></div>
<div id="globalChartDiv" class="chartClass">
  <canvas id="globalChart" ></canvas>
</div>


<select id="zoomSelect" disabled></select>  <input type="checkbox" id="irradBox"><label for="irrad"> Satellite</label>
<div class="chartClass">
  <canvas id="zoomChart"></canvas>
</div>

<div id="MonthlyProd" style="margin-top:1%;" class="sc" >
</div>
</body>
</html>
