<?php
//     TODO: do real report

    require_once('tcpdf/tcpdf.php');

    function generateReport() {
        // create new PDF document
        $pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Nicola Asuni');
        $pdf->SetTitle('TCPDF Example 011');
        $pdf->SetSubject('TCPDF Tutorial');
        $pdf->SetKeywords('TCPDF, PDF, example, test, guide');

        // set default header data
        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 011', PDF_HEADER_STRING);

        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

        // set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set font
        $pdf->SetFont('helvetica', '', 12);

        // add a page
        $pdf->AddPage();

        // column titles
        $header = array('Country', 'Capital', 'Area (sq km)', 'Pop. (thousands)');

        // data loading
        $data = $pdf->LoadData();

        // print colored table
        $pdf->ColoredTable($header, $data);

        // ---------------------------------------------------------

        // close and output PDF document
        $pdf->Output('/tmp/example_011.pdf', 'I');
    }

    class MYPDF extends TCPDF {
        // Load table data from file
        public function LoadData() {
            // Read file lines
            $content = <<<END
Austria;Vienna;83859;8075
Belgium;Brussels;30518;10192
Denmark;Copenhagen;43094;5295
Finland;Helsinki;304529;5147
France;Paris;543965;58728
Germany;Berlin;357022;82057
Greece;Athens;131625;10511
Ireland;Dublin;70723;3694
Italy;Roma;301316;57563
Luxembourg;Luxembourg;2586;424
Netherlands;Amsterdam;41526;15654
Portugal;Lisbon;91906;9957
Spain;Madrid;504790;39348
Sweden;Stockholm;410934;8839
United Kingdom;London;243820;58862
END;
            $lines = explode("\n", $content);

            $data = array();
            foreach($lines as $line) {
                $data[] = explode(';', chop($line));
            }
            return $data;
        }

        // Colored table
        public function ColoredTable($header,$data) {
            // Colors, line width and bold font
            $this->SetFillColor(255, 0, 0);
            $this->SetTextColor(255);
            $this->SetDrawColor(128, 0, 0);
            $this->SetLineWidth(0.3);
            $this->SetFont('', 'B');
            // Header
            $w = array(40, 35, 40, 45);
            $num_headers = count($header);
            for($i = 0; $i < $num_headers; ++$i) {
                $this->Cell($w[$i], 7, $header[$i], 1, 0, 'C', 1);
            }
            $this->Ln();
            // Color and font restoration
            $this->SetFillColor(224, 235, 255);
            $this->SetTextColor(0);
            $this->SetFont('');
            // Data
            $fill = 0;
            foreach($data as $row) {
                $this->Cell($w[0], 6, $row[0], 'LR', 0, 'L', $fill);
                $this->Cell($w[1], 6, $row[1], 'LR', 0, 'L', $fill);
                $this->Cell($w[2], 6, number_format($row[2]), 'LR', 0, 'R', $fill);
                $this->Cell($w[3], 6, number_format($row[3]), 'LR', 0, 'R', $fill);
                $this->Ln();
                $fill=!$fill;
            }
            $this->Cell(array_sum($w), 0, '', 'T');
        }
    }
