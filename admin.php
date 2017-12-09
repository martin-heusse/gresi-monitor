<?php

require_once "constants.php";
require_once "ids.php"; // Contains the identifier + Password to connect to RTone web API
require_once "common.php"; 

$db = connect_to_db();

pageHeader("Admin");
header_form(basename(__FILE__));
echo "<input type=\"submit\" name=\"create_base\" value=\"Créer base\"/> <-- A priori, à n'utiliser qu'une fois !";
echo "</form>";

if (isset($_POST['create_base'])){
    foreach(array(
    "create table IF NOT EXISTS ".tp."meters ( serial integer unsigned primary key, name varchar(256) not null, localization varchar(512), fisrtts integer unsigned, lastts integer unsigned, peak_power float )",
     "create table IF NOT EXISTS ".tp."readings ( serial integer unsigned, ts integer unsigned not null, prod10 float not null, constraint ".tp."ref_serial foreign key (serial) references ".tp."meters(serial), primary key(serial,ts))",
     "create table IF NOT EXISTS ".tp."irrad ( serial integer unsigned, ts integer unsigned not null, prod1h float not null, irrad float not null, constraint ".tp."ref_serial1 foreign key (serial) references ".tp."meters(serial), primary key(serial,ts))"
     ) as $qr){
      echo $qr."<br>";
      $update_messages = $db->prepare($qr);
      $update_messages->execute();
      }
    }
    echo "<h2>base créée ?!??</h2>";

pageFoot();

?>