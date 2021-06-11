<?php
    define("_PDF_HEADER_LOGO", "../../../../../../" . dirname(__FILE__) . "/../resources/logo.jpg");
    define("_PDF_HEADER_LOGO_WIDTH", "20");
    define("_PDF_HEADER_LOGO_HEIGHT", "20");
    define("_PDF_HEADER_TITLE", "ECSC 2021");
    define("_PDF_HEADER_STRING", "Status Report");

    require_once('tcpdf/tcpdf.php');

    class MYPDF extends TCPDF {
        public function ColoredTable($header, $data) {
            // Colors, line width and bold font
            $this->SetFillColor(255, 0, 0);
            $this->SetTextColor(255);
            $this->SetDrawColor(128, 0, 0);
            $this->SetLineWidth(0.3);
            $this->SetFont('', 'B');

            $w = array(80, 80);
            $num_headers = count($header);
            for($i = 0; $i < $num_headers; ++$i) {
                $this->Cell($w[$i], 7, $header[$i], 1, 0, 'C', 1);
            }
            $this->Ln();

            $this->SetFillColor(224, 235, 255);
            $this->SetTextColor(0);
            $this->SetFont('');

            $fill = 0;
            foreach($data as $row) {
                $this->Cell($w[0], 6, $row[0], 'LR', 0, 'L', $fill);
                $this->Cell($w[1], 6, $row[1], 'LR', 0, 'L', $fill);
//                 $this->Cell($w[2], 6, number_format($row[2]), 'LR', 0, 'R', $fill);
//                 $this->Cell($w[3], 6, number_format($row[3]), 'LR', 0, 'R', $fill);
                $this->Ln();
                $fill=!$fill;
            }
            $this->Cell(array_sum($w), 0, '', 'T');
        }
    }

    function generateReport($header, $content) {
        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

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

        $lines = explode("\n", $content);

        $data = array();
        foreach($lines as $line) {
            $data[] = explode(';', chop($line));
        }
        $pdf->ColoredTable($header, $data);

        header("Content-Type: application/octet-stream");
        $pdf->Output("report.pdf", 'I');
    }
?>
