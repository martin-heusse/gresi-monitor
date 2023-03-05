<?php

class time_period {
    var $date_start;
    var $date_end;

    function __construct() {
        $this->date_start = (new DateTime())->setTime(0, 0, 0);
        $this->date_end = (new DateTime())->setTime(0, 0, 0);
    }
}

function get_rbee_prod($db, $serial, $date_start, $date_end) {
    $sql = "SELECT COALESCE(SUM(prod),0) AS sum FROM ".tp."readings
            WHERE serial=:serial AND ts BETWEEN :ts_start AND :ts_end
    ";
    // Oddly, summing on prod from XXirrad table does not give the good results
    $query = $db->prepare($sql);
    $query->bindValue('serial', $serial, PDO::PARAM_STR);
    $query->bindValue('ts_start', $date_start->getTimestamp(), PDO::PARAM_INT);
    $query->bindValue('ts_end', $date_end->getTimestamp(), PDO::PARAM_INT);
    $query->execute();
    return $query->fetch(PDO::FETCH_ASSOC)['sum'];
}

function get_tic_prod($db , $serial, $date_start, $date_end) {
    $sql = "SELECT MAX(eait)-MIN(eait) AS prod FROM ".tp."ticreadings
            WHERE deveui=:serial AND ts BETWEEN :ts_start AND :ts_end
    ";
    $query = $db->prepare($sql);
    $query->bindValue('serial', $serial, PDO::PARAM_STR);
    $query->bindValue('ts_start', $date_start->getTimestamp(), PDO::PARAM_INT);
    $query->bindValue('ts_end', $date_end->getTimestamp(), PDO::PARAM_INT);
    $query->execute();
    $prod = $query->fetch(PDO::FETCH_ASSOC)["prod"];

    return is_null($prod)? 0 : $prod;
}

function get_tic_pmepmi_prod($db , $serial, $date_start, $date_end) {
// # Debug code
//     $sql="SELECT MAX(date) AS date_start FROM ".tp."ticpmepmiindex
//             WHERE deveui=:serial AND
//             UNIX_TIMESTAMP(date) < :ts_end
//             GROUP BY ptcour";
//             
//     $query = $db->prepare($sql);
//     $query->bindValue('serial', $serial, PDO::PARAM_STR);
//     $query->bindValue('ts_end', $date_start->getTimestamp(), PDO::PARAM_INT);
//     $query->execute();
//     $readings = $query->fetchAll(PDO::FETCH_ASSOC);
//     error_log(print_r($readings, TRUE)); 
//     
//     $sql="SELECT MAX(date) AS date_end FROM ".tp."ticpmepmiindex
//             WHERE deveui=:serial AND
//             UNIX_TIMESTAMP(date) < :ts_end
//             GROUP BY ptcour";
//             
//     $query = $db->prepare($sql);
//     $query->bindValue('serial', $serial, PDO::PARAM_STR);
//     $query->bindValue('ts_end', $date_end->getTimestamp(), PDO::PARAM_INT);
//     $query->execute();
//     $readings = $query->fetchAll(PDO::FETCH_ASSOC);
//     error_log(print_r($readings, TRUE)); 
    
    $sql="SELECT MAX(eait) AS max_eait, MIN(eait) AS min_eait FROM ".tp."ticpmepmiindex
            WHERE deveui=:serial AND
            UNIX_TIMESTAMP(date) BETWEEN :ts_start AND :ts_end
            GROUP BY ptcour";
            
    $query = $db->prepare($sql);
    $query->bindValue('serial', $serial, PDO::PARAM_STR);
    $query->bindValue('ts_start', $date_start->getTimestamp(), PDO::PARAM_INT);
    $query->bindValue('ts_end', $date_end->getTimestamp(), PDO::PARAM_INT);
    $query->execute();
    $readings = $query->fetchAll(PDO::FETCH_ASSOC);
    $prod=0;
    foreach ($readings as $reading) {
      // error_log(print_r($reading, TRUE)); 
      $prod+=$reading['max_eait']-$reading['min_eait'];
    }
    
    $query->bindValue('ts_end', $date_end->getTimestamp(), PDO::PARAM_INT);
    $query->execute();
    $readings = $query->fetchAll(PDO::FETCH_ASSOC);


    $eait2=0;
    foreach ($readings as $reading) {
//       error_log(print_r($reading, TRUE)); 
      $eait2+=$reading['max_eait'];
    }

    return 1000 * ($prod);
}

?>
