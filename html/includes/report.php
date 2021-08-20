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
                font-size: 10px;
            }
            {awareness}
        </style>
        <h3>Scoreboard:</h3>
        <table>
            <tr style="color:white; background-color: red">
                <th style="width: 10%" align="center">Rank</th>
                <th style="width: 60%" align="left">Team name</th>
                <th style="width: 15%" align="right">Cash (€)</th>
                <th style="width: 15%" align="right" class="awareness">Awareness</th>
            </tr>
        ';

        $html = format($html, array("awareness" => (parseBool(getSetting(Setting::USE_AWARENESS)) ? "": ".awareness {display: none}")));

        $counter = 1;
        $rankings = getRankedTeams(true);
        foreach ($rankings as $ranking) {
            $html .= '<tr><td align="center">' . $counter . '</td><td><span style="white-space: nowrap">' . $ranking["full_name"] . '</span></td><td align="right">' . $ranking["cash"] . '</td><td align="right" class="awareness">' . $ranking["awareness"] . '</td></tr>';
            $counter += 1;
        }

        $html .= '
        </table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        $pdf->AddPage();
// // // // // // // // // // // // // // // // // // // // // // // // // // // // // // // // // // // // // // // // // // // // // // // // // // // // // // // // //
        $html = '
        <style>
            table, th, td {
                border: 1px solid black;
                border-collapse: collapse;
                font-size: 10px;
            }
            td {
                vertical-align: middle;
            }
        </style>
        <h3>Statistics:</h3>
        <table>
            <tr style="color:white; background-color: red">
                <th style="width: 30%" align="left">Task</th>
                <th style="width: 30%" align="left">Contract</th>
                <th style="width: 12%" align="right">Cash&nbsp;value (€)</th>
                <th style="width: 12%" align="right">Teams&nbsp;solved</th>
                <th style="width: 15%" align="right">Avg.&nbsp;solve&nbsp;time</th>
            </tr>
        ';

        $average = array();
        $rows = fetchAll("SELECT solved.task_id,tasks.contract_id,AVG(TIMESTAMPDIFF(SECOND,accepted.ts,solved.ts)) AS average_time FROM solved JOIN tasks ON solved.task_id=tasks.task_id JOIN accepted ON tasks.contract_id=accepted.contract_id AND accepted.team_id=solved.team_id GROUP BY task_id");
        foreach ($rows as $row) {
            $average[$row["task_id"]] = $row["average_time"];
        }

        $rows = fetchAll("SELECT tasks.task_id,GROUP_CONCAT(DISTINCT teams.login_name ORDER BY teams.login_name ASC SEPARATOR ', ') AS solved_by,COUNT(teams.login_name) AS solved_count,tasks.title AS task_title,cash AS task_cash,tasks.contract_id,contracts.title AS contract_title FROM tasks LEFT JOIN solved ON tasks.task_id=solved.task_id LEFT JOIN teams ON solved.team_id=teams.team_id LEFT JOIN contracts ON tasks.contract_id=contracts.contract_id GROUP BY tasks.task_id ORDER BY solved_count DESC, task_cash ASC");
        foreach ($rows as $row) {
            $html .= "<tr><td>" . $row["task_title"] . "</td><td style='valign: middle; vertical-align:middle'>" . $row["contract_title"] . '</td><td align="right">' . $row["task_cash"] . '</td><td align="right">' . $row["solved_count"] . '</td><td align="right">' . (isset($average[$row["task_id"]]) ? secondsToTime($average[$row["task_id"]]) : '-') . "</td></tr>";
        }


//         $counter = 1;
//         $rankings = getRankedTeams(true);
//         foreach ($rankings as $ranking) {
//             $html .= '<tr><td align="center">' . $counter . '</td><td><span style="white-space: nowrap">' . $ranking["full_name"] . '</span></td><td align="right">' . $ranking["cash"] . '</td><td align="right" class="awareness">' . $ranking["awareness"] . '</td></tr>';
//             $counter += 1;
//         }

        $html .= '
        </table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        header("Content-Type: application/octet-stream");
        $pdf->Output("report.pdf", 'I');
    }
?>
