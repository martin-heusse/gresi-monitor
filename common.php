<?php
function pageHeader($pageDesc){
    echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

	<head>
		<meta http-equiv="content-type" content="text/html;charset=utf-8" />
		<meta http-equiv="Content-Language" content="fr" />
		<meta http-equiv="refresh" content="600">
    <link rel="SHORTCUT ICON" HREF="./favicon.ico">
		<title>'
	. $pageDesc .'</title>

      <STYLE type="text/css">
      DIV.sc {font-family: Sans-Serif;}
      DIV.ib {display: inline;}
      DIV.plot{ margin-left: auto;
                margin-right: auto;}
      DIV.chartClass{border-style: solid;
                width:95vw;
                height:40vh;
                margin-bottom:6px}
      TABLE.dm{border-collapse: collapse;}
      TABLE.prod{border: 2px solid black; border-collapse: collapse;}
      TD{border: 1px solid black; padding-left: 5px; padding-right: 3px; padding-top: 3px; padding-bottom: 2px}
      TD.totProd{font-weight: bold;}
      H1{font-family: Sans-Serif; text-align:center; font-size: 1.5em; margin: .75em 0}
     </STYLE>


	</head>

	<body>
	<H1>'.$pageDesc.'</H1>';
}

function nameAppli(){
  return "TablOWatt";
}

function pageFoot(){
	echo "</body></html>\r";
}

function header_form($filename){
    echo "<form method=\"post\" action=\"$filename\" id=\"fid\">";
}


function connect_to_db(){
    $db_url="mysql:host=".db_host.";dbname=".db_name.";charset=utf8";
    try{return new PDO($db_url,db_username,db_pwd);}
    catch (PDOException $err){echo "<p><b>Echec de connexion a la BD !!!</b></p>";}
}

function prepare_header(){
    return "MIME-Version: 1.0\r\n".
    'Content-Type: text/plain; charset=utf-8'. "\r\n".
    'X-Mailer: PHP/' . phpversion(). "\r\n";
}


function pace(){
  time_nanosleep(0, 700000000);
}

function date_to_str($time){ // Reciprocal of builtin strtotime()
  return date("Y-m-d\TH:i:s",$time);
}

function get_meter_list($db, $min_peak_power=0)
{
    $qr = "select 'rbee' as family, serial, rm.name, fisrtts ,lastts,timeoffset, peak_power, longitude as 'LONG', latitude as LAT, tilt as betta, azimuth as gamma from " . tp . "meters rm 
           WHERE peak_power > :peak_power join " . tp . "metersdata m on rm.name=m.name 
    union select 'tic' as family, deveui as serial, tm.name, fisrtts ,lastts, 0 as timeoffset, peak_power, longitude as 'LONG', latitude as LAT, tilt as betta, azimuth as gamma from " . tp . "ticmeters tm 
          WHERE peak_power > :peak_power join " . tp . "metersdata m on tm.name=m.name 
    union select 'ticpmepmi' as family, deveui as serial, tpm.name, fisrtts ,lastts, 0 as timeoffset, peak_power, longitude as 'LONG', latitude as LAT, tilt as betta, azimuth as gamma from " . tp . "ticpmepmimeters tpm 
          WHERE peak_power > :peak_power join " . tp . "metersdata m on tpm.name=m.name";
    $query = $db->prepare($qr);
    $query->bindValue('peak_power', $min_peak_power, PDO::PARAM_INT);
    $query->execute();
  
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

function get_meter_list_orig($db)
{
  $qr="select 'rbee' as family, serial, name, fisrtts ,lastts,timeoffset from ".tp."meters where serial not in (select replacedby from ".tp."disabled where replacedby is not null) union select 'tic' as family, deveui as serial, name, fisrtts ,lastts, 0 as timeoffset from ".tp."ticmeters where fisrtts>0 union select 'ticpmepmi' as family, deveui as serial, name, fisrtts ,lastts, 0 as timeoffset from ".tp."ticpmepmimeters where fisrtts>0 order by name";
  $select_messages = $db->prepare($qr);
  $select_messages->setFetchMode(PDO::FETCH_ASSOC);
  $select_messages->execute();
  $meters =  $select_messages->fetchAll();
  $qr = "select name, longitude, latitude, peak_power, azimuth, tilt from ".tp."metersdata";
  $select_messages = $db->prepare($qr);
  $select_messages->setFetchMode(PDO::FETCH_ASSOC);
  $select_messages->execute();
  $data = $select_messages->fetchAll();
  $result = [];
  // Concatenate meters with data in one array using name as join key
  foreach ($meters as $meter) {
    foreach ($data as $d) {
      if ($meter['name'] == $d['name']) {
        $res = [];
        $res['family'] = $meter['family'];
        $res['serial'] = $meter['serial'];
        $res['name'] = $meter['name']; // == $data['name']
        $res['fisrtts'] = $meter['fisrtts'];
        $res['lastts'] = $meter['lastts'];
        $res['peak_power'] = $d['peak_power'];
        $res['timeoffset'] = $meter['timeoffset'];
//        $res['LONG'] = $d['longitude']; useless
//        $res['LAT'] = $d['latitude'];
//        $res['betta'] = $d['azimuth'];
//        $res['gamma'] = $d['tilt'];
        $result[] = $res;
      }
    }
  }

  return $result;
}
function get_meter_list_check($db)
{
  $qr="select * from ".tp."meters where serial not in (select serial from  ".tp."disabled) order by name";
  $select_messages = $db->prepare($qr);
  $select_messages->setFetchMode(PDO::FETCH_ASSOC);
  $select_messages->execute();
  return $select_messages->fetchAll();
}

function get_readings_rbee($start, $end, $second)
{
    $db = connect_to_db();

    // Create a temporary table with the period timestamps
    $qr = "CREATE TEMPORARY TABLE all_ts (
        ts integer unsigned NOT NULL,
        PRIMARY KEY (ts)
    );";
    $prepare_variables = $db->prepare($qr);
    $prepare_variables->setFetchMode(PDO::FETCH_ASSOC);
    $prepare_variables->execute();
    // Fill it
    $qr = "INSERT INTO all_ts (ts) VALUES (" . implode("), (", range($start, $end, $second)) . ");";
    $prepare_variables = $db->prepare($qr);
    $prepare_variables->setFetchMode(PDO::FETCH_ASSOC);
    $prepare_variables->execute();

    // Create the final query to grab data
    $table = $second == 600 ? "readings" : "irrad";
    $qr = "-- Add -1 (null) for all missing values over the period
    SELECT ts AS ts, -1 AS prod
        FROM all_ts
        WHERE all_ts.ts NOT IN ( SELECT ts FROM " . tp . $table . " WHERE serial=@serial AND (ts BETWEEN @ts_start AND @ts_end))
    UNION
    -- Select prod values for a device over the period
    SELECT ts+0 as ts, prod
        FROM " . tp . $table . " as tr
        WHERE serial=@serial AND (tr.ts BETWEEN @ts_start and @ts_end)
    ORDER BY ts;";

    // Set variables used in the query
    $prepare_variables = $db->prepare("SET @ts_start = ?;");
    $prepare_variables->setFetchMode(PDO::FETCH_ASSOC);
    $prepare_variables->execute(array($start));
    $prepare_variables = $db->prepare("SET @ts_end = ?;");
    $prepare_variables->setFetchMode(PDO::FETCH_ASSOC);
    $prepare_variables->execute(array($end));
    $prepare_variables = $db->prepare("SET @serial = ?;");
    $prepare_variables->setFetchMode(PDO::FETCH_ASSOC);
    $prepare_variables->execute(array($_GET['serial']));

    // Trigger the query
    $select_messages = $db->prepare($qr);
    $select_messages->setFetchMode(PDO::FETCH_ASSOC);
    $select_messages->execute($reqArgs);

    // Send the content
    return $select_messages->fetchAll();
}

function get_readings_tic($start, $end, $second)
{
    $db = connect_to_db();

    $start = $start + $second;
    $qr = "SELECT deveui as serial, ts, eait
        FROM " . tp . "ticreadings
        WHERE deveui=?
        AND ts BETWEEN ? and ? order by ts";
    $reqArgs = array($_GET['serial'], $start, $end);
    $select_messages = $db->prepare($qr);
    $select_messages->setFetchMode(PDO::FETCH_ASSOC);
    $select_messages->execute($reqArgs);
    $readings = $select_messages->fetchAll();

    $prod = array();
    $rounded_start = $start;

    if (count($readings) < 2) exit;

    // Build an array of power
    $pow = array();
    for ($i = 1; $i < count($readings); $i++) {
        if ($readings[$i]['ts'] - $readings[$i - 1]['ts'] == 0) continue;// Ya never know
        $p = ($readings[$i]['eait'] - $readings[$i - 1]['eait']) / ($readings[$i]['ts'] - $readings[$i - 1]['ts']);
        $t = ($readings[$i]['ts'] + $readings[$i - 1]['ts']) / 2;
        $cur_pow = array('ts' => $t, 'pow' => $p);
        array_push($pow, $cur_pow);
    }

    if (count($pow) < 2) exit;
    $last_ts = $readings[count($readings) - 1]['ts'];
    $prev_prod = $pow[0]['pow'];
    $prev_t = $rounded_start;
    for ($t = $rounded_start; $t < $last_ts; $t += $second) {
        // Gather measures within this time interval
        $p_sum = 0;
        $nb = 0;
        $i = 0;
        for (; $i < count($pow); $i++) {
            if ($pow[$i]['ts'] >= $t - $second && $pow[$i]['ts'] < $t) {
                $p_sum += $pow[$i]['pow'];
                $nb++;
            }
            if ($pow[$i]['ts'] >= $t) break;
        }
        if ($nb > 0) {
            $prev_prod = $p_sum / $nb;
            $prev_t = $t;
            $this_prod = $prev_prod;
        } else {
            $this_prod = -1;
        }
        $cur_prod = array('ts' => $t, 'prod' => $this_prod * $second);

        array_push($prod, $cur_prod);
    }

    return $prod;
}

function get_readings_ticpmepmi($start, $end, $second)
{
    $db = connect_to_db();

    $qr = "CREATE TEMPORARY TABLE all_ts (
        ts integer unsigned NOT NULL,
        PRIMARY KEY (ts)
    );";
    $prepare_variables = $db->prepare($qr);
    $prepare_variables->setFetchMode(PDO::FETCH_ASSOC);
    $prepare_variables->execute();
    // Fill it
    $qr = "INSERT INTO all_ts (ts) VALUES (" . implode("), (", range($start, $end, $second)) . ");";
    $prepare_variables = $db->prepare($qr);
    $prepare_variables->setFetchMode(PDO::FETCH_ASSOC);
    $prepare_variables->execute();

    $qr = "-- Add -1 (null) for all missing values over the period
    SELECT ts AS ts, -1 AS prod
        FROM all_ts
        WHERE all_ts.ts NOT IN ( SELECT ts FROM " . tp . "ticpmepmireadings WHERE deveui=@serial AND (ts BETWEEN @ts_start AND @ts_end))
    UNION
    -- Select prod values for a device over the period
    SELECT ts+0 as ts, 1000*pi/6 as prod
        FROM " . tp . "ticpmepmireadings as tr
        WHERE deveui=@serial AND (tr.ts BETWEEN @ts_start and @ts_end)
    ORDER BY ts;";

    // Set variables used in the query
    $prepare_variables = $db->prepare("SET @ts_start = ?;");
    $prepare_variables->setFetchMode(PDO::FETCH_ASSOC);
    $prepare_variables->execute(array($start));
    $prepare_variables = $db->prepare("SET @ts_end = ?;");
    $prepare_variables->setFetchMode(PDO::FETCH_ASSOC);
    $prepare_variables->execute(array($end));
    $prepare_variables = $db->prepare("SET @serial = ?;");
    $prepare_variables->setFetchMode(PDO::FETCH_ASSOC);
    $prepare_variables->execute(array($_GET['serial']));

    // Trigger the query
    $select_messages = $db->prepare($qr);
    $select_messages->setFetchMode(PDO::FETCH_ASSOC);
    $select_messages->execute($reqArgs);
    $readings = $select_messages->fetchAll();

    $result = [];
    $sum = 0;
    $nb = 0;
    foreach ($readings as $reading) {
        $sum += $reading['prod'];
        $nb++;

        if ((int)$reading['ts'] % $second == 0) {
            if ($nb == $second / 600) {
                $result[] = [
                    'ts' => $reading['ts'],
                    'prod' => $sum,
                ];
            } else {
                $result[] = [
                    'ts' => $reading['ts'],
                    'prod' => -1,
                ];
            }

            $sum = 0;
            $nb = 0;
        }
    }

    return $result;
}

?>