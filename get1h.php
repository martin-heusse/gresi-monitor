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
$end=$_GET['end'];

// Create a temporary table with the period timestamps
$qr="CREATE TEMPORARY TABLE all_ts (
    ts integer unsigned NOT NULL,
    PRIMARY KEY (ts)
);";
$prepare_variables = $db->prepare($qr);
$prepare_variables->setFetchMode(PDO::FETCH_ASSOC);
$prepare_variables->execute();
// Fill it
$qr="INSERT INTO all_ts (ts) VALUES (".implode("), (", range($start, $end, 3600)).");";
$prepare_variables = $db->prepare($qr);
$prepare_variables->setFetchMode(PDO::FETCH_ASSOC);
$prepare_variables->execute();

// Create the final query to grab data
$qr="-- Add -1 (null) for all missing values over the period
SELECT 'rbee' AS family, @serial AS serial, ts AS ts, -1 AS prod, 0 as irrad
    FROM all_ts
    WHERE all_ts.ts NOT IN ( SELECT ts FROM ".tp."irrad WHERE serial=@serial AND (ts BETWEEN @ts_start AND @ts_end))
UNION
-- Select prod values for a device over the period
SELECT 'rbee' as family, serial, ts as ts, prod, irrad
    FROM ".tp."irrad as tr
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
$readings = $select_messages->fetchAll();
header('Content-Type: application/json');
echo json_encode($readings);

?>