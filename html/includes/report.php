<?php
    define("_PDF_HEADER_LOGO", "../../../../../../" . dirname(__FILE__) . "/../resources/logo.jpg");
    define("_PDF_HEADER_LOGO_WIDTH", "20");
    define("_PDF_HEADER_LOGO_HEIGHT", "20");
    define("_PDF_HEADER_TITLE", "ECSC 2021");
    define("_PDF_HEADER_STRING", "Status Report (" . date('Y-m-d H:i:s') . ")");

    require_once("common.php");
    require_once('tcpdf/tcpdf.php');

    function generateReport() {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $pdf->SetTitle(_PDF_HEADER_TITLE);
        $pdf->SetSubject(_PDF_HEADER_STRING);

        $pdf->SetHeaderData(_PDF_HEADER_LOGO, _PDF_HEADER_LOGO_WIDTH, _PDF_HEADER_TITLE, _PDF_HEADER_STRING);

        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        $pdf->SetFont("helvetica", '', 12);

        $pdf->AddPage();

        $html = '
        <style>
            table, th, td {
                border: 1px solid black;
                border-collapse: collapse;
            }
        </style>
        <h3>Scoreboard:</h3>
        <table>
            <tr style="color:white; background-color: red">
                <th style="width: 10%" align="center">Rank</th>
                <th style="width: 60%" align="left">Team name</th>
                <th style="width: 15%" align="right">Cash (€)</th>
                <th style="width: 15%" align="right">Awareness</th>
            </tr>
        ';

        $counter = 1;
        $rankings = getRankedTeams(true);
        foreach ($rankings as $ranking) {
            $html .= '<tr><td align="center">' . $counter . '</td><td><span style="white-space: nowrap">' . $ranking["full_name"] . '</span></td><td align="right">' . $ranking["cash"] . '</td><td align="right">' . $ranking["awareness"] . '</td></tr>';
            $counter += 1;
        }

        $html .= '
        </table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        $pdf->AddPage();

        $pdf->writeHTML($html, true, false, true, false, '');

        header("Content-Type: application/octet-stream");
        $pdf->Output("report.pdf", 'I');
    }
?>
