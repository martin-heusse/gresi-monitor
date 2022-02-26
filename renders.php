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

}

?>