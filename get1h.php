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

if ($family == 'rbee') {
    header('Content-Type: application/json');
    echo json_encode(get_readings_rbee($start, $end, 3600));
} else if ($family == 'tic') {
    echo json_encode(get_readings_tic($start, $end, 3600));
} else if ($family == 'ticpmepmi') {
    header('Content-Type: application/json');
    echo json_encode(get_readings_ticpmepmi($start, $end, 3600));
}

?>