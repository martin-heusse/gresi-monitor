<?php
require_once "constants.php";
require_once "ids.php"; // Contains the identifier + Password to connect to RTone web API
require_once "common.php";

ini_set("zlib.output_compression", "On");
ini_set("zlib.output_compression_level", "-1");

$db = connect_to_db();

// $_GET should contain serial, start, end 
// ex: http://localhost/~heusse/Monitor/get1h.php?serial=216670215&start=1512814200&end=1512823800

// Round start/end to hours in order to match
// DB when generating missing ts
$start = $_GET['start'];
$start = 3600 * ceil($start / 3600);
$end = $_GET['end'];

// Get meter family
$family = $_GET['family'];

// Create a temporary table with the period timestamps
$qr = "CREATE TEMPORARY TABLE all_ts (
    ts integer unsigned NOT NULL,
    PRIMARY KEY (ts)
);";
$prepare_variables = $db->prepare($qr);
$prepare_variables->setFetchMode(PDO::FETCH_ASSOC);
$prepare_variables->execute();
// Fill it
$qr = "INSERT INTO all_ts (ts) VALUES (" . implode("), (", range($start, $end, 3600)) . ");";
$prepare_variables = $db->prepare($qr);
$prepare_variables->setFetchMode(PDO::FETCH_ASSOC);
$prepare_variables->execute();

if ($family == 'rbee') {
    header('Content-Type: application/json');
    echo json_encode(get_readings_rbee($start, $end, 3600));
} else if ($family == 'tic') {
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

    header('Content-Type: application/json');

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
    for ($t = $rounded_start; $t < $last_ts; $t += 3600) {
        // Gather measures within this time interval
        $p_sum = 0;
        $nb = 0;
        $i = 0;
        for (; $i < count($pow); $i++) {
            if ($pow[$i]['ts'] >= $t - 3600 && $pow[$i]['ts'] < $t) {
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
        $cur_prod = array('ts' => $t, 'prod' => $this_prod * 3600);

        array_push($prod, $cur_prod);
    }

    echo json_encode($prod);
} else if ($family == 'ticpmepmi') {
    header('Content-Type: application/json');
    echo json_encode(get_readings_ticpmepmi($start, $end, 3600));
}

?>