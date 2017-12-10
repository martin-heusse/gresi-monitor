<?php
require_once "monitor-constants.php";
require_once "common.php";

define('listUrl',baseUrl.'getMeters.php');
define('dataUrl1h',baseUrl.'getIrrad.php');
define('dataUrl10mn',baseUrl.'get10mn.php');


pageHeader("Monitoring");
date_default_timezone_set("UTC");
?>
<body>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.4.0/Chart.min.js"></script>
<script src="./monitor.js"> </script>

<div id="status" class="sc"><span id="progress"></span><span id="progressEnd"></span></div>
<input type=hidden id=listUrl value = "<?php print( listUrl ); ?>"/>
<input type=hidden id=dataUrl1h value = "<?php print( dataUrl1h ); ?>"/>
<input type=hidden id=dataUrl10mn value = "<?php print( dataUrl10mn ); ?>"/>
<div style="width:100%; height:50%;">
  <canvas id="globalChart"></canvas>
</div>
<div>
  <select id="zoomSelect" disabled></select>
  <div style="width:100%; height:50%;">
    <canvas id="zoomChart"></canvas>
  </div>
</div>
</body>
</html>
