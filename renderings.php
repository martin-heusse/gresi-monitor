<?php

function render_csv($filename, $header, $data) {
    // Create CSV output
    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=prod.csv");
    $output_csv = fopen("php://output", 'w');
    fputcsv($output_csv, $header);

    // Fill it
    foreach ($data as $row) {
        fputcsv($output_csv, $row);
    }
}

function render_36kwc_invoicing_data($data) {
    pageHeader(nameAppli()." — Données facturations 36 kWc+");
    echo "<h2>Production annuelle avec séparation 6 mois/date anniversaire:</h2>";
    echo '<form action="" method="post"><button name="csv" value="true">Download as CSV</button></form><br>';
    echo "<DIV class='sc'><TABLE CLASS='prod'>";
    echo "<TR>";
    foreach (array(
                    "Compteur",
                    "Date d'installation",
                    (date("Y")-1)."-debut (kWh)",
                    (date("Y")-1)."-semestre (kWh)",
                    (date("Y")-1)."-fin (kWh)",
                    date("Y")."-debut (kWh)",
                    date("Y")."-semestre (kWh)",
                    date("Y")."-fin (kWh)"
                ) as $item) {
        echo '<TH style="padding: 10px;">'.$item.'</TH>';
    }
    echo "</TR>";

    foreach ($data as $row) {
        echo "<TR>";
        foreach ($row as $item) {
            echo "<TD>".$item."</TD>";
        }
        echo "</TR>";
    } 

    echo "</TABLE></DIV>";
    pageFoot();
}

?>