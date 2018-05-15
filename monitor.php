<?php
require_once "monitor-constants.php";
require_once "common.php";

define('listUrl',baseUrl.'getMeters.php');
define('dataUrl1h',baseUrl.'get1h.php');
define('dataUrl10mn',baseUrl.'get10mn.php');
define('dataUrlMonth',baseUrl.'getMonthlyProd.php');


pageHeader(nameAppli());
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

if (isset($_POST['zoommeter'])){
    $zoommeter=$_POST['zoommeter'];
}
else $zoommeter="";

if (isset($_POST['irradbox'])){
    $irradbox=$_POST['irradbox'];
}
else $irradbox="";

if (isset($_POST['zoomenddate'])){
    $zoomenddate=$_POST['zoomenddate'];
}
else $zoomenddate="";
if (isset($_GET['meters'])){
    $meterstoconsider=$_GET['meters'];
}
else $meterstoconsider="";
?>
<input type=hidden id=postzoommeter value = "<?php print( $zoommeter ); ?>"/>
<input type=hidden id=postirradbox value = "<?php print( $irradbox ); ?>"/>
<input type=hidden id=postzoomenddate value = "<?php print( $zoomenddate ); ?>"/>
<input type=hidden id=meterstoconsider value = "<?php print( $meterstoconsider ); ?>"/>
<?php

if (isset($_POST['prevday']))
    shiftday($enddate,-1);
if (isset($_POST['nextday']))
    shiftday($enddate,+1);
if (isset($_POST['prevweek']))
    shiftday($enddate,-7);
if (isset($_POST['nextweek']))
    shiftday($enddate,+7);
$main1h=true;
if (isset($_POST['dataspan'])){
    if(0==strcmp($_POST['dataspan'],"10mn"))
        $main1h=false;
}
?>

<input type=hidden id=listUrl value = "<?php print( listUrl ); ?>"/>
<input type=hidden id=dataUrl1h value = "<?php print( dataUrl1h ); ?>"/>
<input type=hidden id=dataUrl10mn value = "<?php print( dataUrl10mn ); ?>"/>
<input type=hidden id=dataUrlMonth value = "<?php print( dataUrlMonth ); ?>"/>

<div class="sc" style="width:100%; padding:6px;">
<?php header_form($_SERVER['REQUEST_URI']);?>
  Afficher la semaine se terminant le : 
  <input type="date" onChange='this.form.submit();'  id="enddate" name="enddate" value="<?php echo $enddate;?>"/>
  <input type="submit"/>
  <input type="submit" name="prevweek" value="<<"/>
  <input type="submit" name="prevday" value="<"/>
  <input type="submit" name="nextday" value=">"/>
  <input type="submit" name="nextweek" value=">>"/>
  <input id="radio1h" type="radio" onChange='this.form.submit();' name="dataspan" value="1h" <?php echo $main1h?"checked='checked'":"";?>/><label for="radio1h"> 1h</label>
  <input id="radio10mn" type="radio"  onChange='this.form.submit();' name="dataspan" value="10mn" <?php echo (!$main1h) ?
           "checked='checked'":"";?>/><label for="radio10mn"> 10mn</label>
  <input type="button" id="hideAll" value="Cacher tout" style="float: right;" title="Cacher ou afficher toutes les courbes"/>
  <input type="button" id="GenerateURL" value="Filtrer" style="float: right;" title="Générer une page contenant uniquement les stations affichées" onclick="alert('Vous pourrez ajouter la page suivante à vos signets');"/>
</form>
</div>

<div id="status" class="sc ib" style="display:none;"><span id="progress"></span><span id="progressEnd"></span></div>
<div id="globalChartDiv" class="chartClass" style="height: 70vh">
  <canvas id="globalChart" ></canvas>
</div>


<select id="zoomSelect" name="zoommeter" disabled form="fid"></select>  <input type="checkbox" id="irradBox" name="irradbox" form="fid"><label for="irradBox"> Satellite</label>
<span style="margin-left:10%;">Période de référence :</span><input style="margin-left:1%;" type="date" id="zoomenddate" name="zoomenddate" form="fid"/>
<div class="chartClass">
  <canvas id="zoomChart"></canvas>
</div>

<div class="sc">
<span id="MonthlyProd" style="margin-top:2%;display: inline-block" > </span>
<span style="margin-top:2%;margin-left:10px";><a  href="./yearlyprod.php"> Productions annuelles</a></span>
</div>
</body>
</html>
