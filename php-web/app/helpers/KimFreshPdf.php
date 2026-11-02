<?php
// app/helpers/KimFreshPdf.php — FPDF subclass for "Products" inventory export
// Extends lib/fpdf/fpdf.php

class KimFreshPdf extends FPDF
{
    public $title = 'KimFresh Document';

    public function Header()
    {
        $this->SetFont('Arial', 'B', 15);
        $this->SetTextColor(195, 66, 0);
        $this->Cell(0, 10, $this->title, 0, 1, 'C');
        $this->SetFont('Arial', 'I', 10);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 5, 'Generated on: ' . date('F j, Y, g:i A'), 0, 1, 'C');
        $this->Ln(10);
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(128);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . ' - KimFresh IoT System', 0, 0, 'C');
    }
}
