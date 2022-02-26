<?php

class time_period {
    var $date_start;
    var $date_end;

    function __construct() {
        $this->date_start = (new DateTime())->setTime(0, 0, 0);
        $this->date_end = (new DateTime())->setTime(0, 0, 0);
    }
}

function get_rbee_prod($db , $serial, $date_start, $date_end) {
    $sql = "SELECT COALESCE(SUM(prod),0) AS sum FROM ".tp."readings
            WHERE serial=:serial AND ts BETWEEN :ts_start AND :ts_end
    ";
    // Oddly, summing on prod from XXirrad table does not give the good results
    $query = $db->prepare($sql);
    $query->bindValue('serial', $serial, PDO::PARAM_INT);
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
    $query->bindValue('serial', $serial, PDO::PARAM_INT);
    $query->bindValue('ts_start', $date_start->getTimestamp(), PDO::PARAM_INT);
    $query->bindValue('ts_end', $date_end->getTimestamp(), PDO::PARAM_INT);
    $query->execute();
    $prod = $query->fetch(PDO::FETCH_ASSOC)["prod"];

    return is_null($prod)? 0 : $prod;
}

?>