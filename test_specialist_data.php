<?php
require_once('vendor/autoload.php');

$pdf = new TCPDF();
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 10, 'TCPDF is working!', 0, 1);
$pdf->Output('test.pdf', 'I');