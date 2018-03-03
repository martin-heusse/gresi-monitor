<?php
require_once "constants.php";
require_once "ids.php"; // Contains the identifier + Password to connect to RTone web API
require_once "common.php"; 

$db = connect_to_db();

$meters = get_meter_list($db);
header('Content-Type: application/json');
echo json_encode($meters);

?>