<?php

require_once "constants.php";
require_once "ids.php"; // Contains the identifier + Password to connect to RTone web API
require_once "common.php"; 

$db = connect_to_db();


function check_table($db, $table_name) {
    $db_check = $db->prepare("DESCRIBE ".tp.$table_name);
    if ($db_check->execute()) {
        return true;
    } else {
        echo "Table \"".tp.$table_name."\" has not been created.<br>";
        return false;
    }
}

pageHeader("Admin");
header_form(basename(__FILE__));
echo "<input type=\"submit\" name=\"create_base\" value=\"Créer base\"/> <-- A priori, à n'utiliser qu'une fois !";
echo "</form>";

if (isset($_POST['create_base'])){
    foreach(array(
    "create table IF NOT EXISTS ".tp."meters ( serial integer unsigned primary key, name varchar(256) not null, localization varchar(512), fisrtts integer unsigned, lastts integer unsigned, peak_power float not null, timeoffset integer default 0)",
     "create table IF NOT EXISTS ".tp."readings ( serial integer unsigned, ts integer unsigned not null, prod float not null, constraint ".tp."ref_serial foreign key (serial) references ".tp."meters(serial), primary key(serial,ts))",
     "create table IF NOT EXISTS ".tp."irrad ( serial integer unsigned, ts integer unsigned not null, prod float not null, irrad float not null, constraint ".tp."ref_serial1 foreign key (serial) references ".tp."meters(serial), primary key(serial,ts))",
     "create table IF NOT EXISTS ".tp."disabled ( serial integer unsigned )",
     "alter table ".tp."disabled add constraint ".tp."ref_serial2 foreign key (serial) references ".tp."meters(serial), add column replacedby integer unsigned, add constraint ".tp."ref_serial3 foreign key (replacedby) references ".tp."meters(serial)",
     "create table IF NOT EXISTS ".tp."ticmeters ( deveui bigint unsigned, name varchar(256) not null, fisrtts integer unsigned, lastts integer unsigned, peak_power float not null, primary key(deveui))",
     "create table IF NOT EXISTS ".tp."ticreadings ( deveui bigint unsigned, ts integer unsigned not null, eait integer not null, east integer not null, constraint ".tp."ref_eui foreign key (deveui) references ".tp."ticmeters(deveui), primary key(deveui,ts))",

        ) as $qr){
        echo $qr."<br>";
        $update_messages = $db->prepare($qr);
        $update_messages->execute();
    }

    # Check if tables have been correctly created
    echo "<br><br>";
    $status = true;
    $status = (check_table($db, "meters") && $status);
    $status = (check_table($db, "readings") && $status);
    $status = (check_table($db, "irrad") && $status);
    $status = (check_table($db, "disabled") && $status);
    $status = (check_table($db, "ticmeters") && $status);
    $status = (check_table($db, "ticreadings") && $status);

    if ($status === true) {
        echo "<h2>Tables successfully created !</h2>";
    }
}

pageFoot();

?>
