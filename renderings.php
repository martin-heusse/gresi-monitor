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
    echo "<DIV class='sc'><TABLE CLASS='prod'>";
    echo "<TR>";
    foreach (array(
                    "Compteur",
                    "Date d'installation",
                    "Année",
                    "Prod1 (kWh)",
                    "Prod2 (kWh)",
                    "Prod3 (kWh)",
                    "Total (kWh)"
                ) as $item) {
        echo "<TD>".$item."</TD>";
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