<?php
require_once "constants.php";
require_once "ids.php"; // Contains the identifier + Password to connect to RTone web API
require_once "common.php"; 

$db = connect_to_db();

$qr="select * from ".tp."meters order by name";
$select_messages = $db->prepare($qr);
$select_messages->setFetchMode(PDO::FETCH_ASSOC);
$select_messages->execute();
$meters =$select_messages->fetchAll();
header('Content-Type: application/json');
echo json_encode($meters);

?>