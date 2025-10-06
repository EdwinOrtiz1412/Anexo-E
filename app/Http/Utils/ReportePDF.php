<?php
namespace App\Http\Utils;
use PDF;

class ReportePDF extends PDF{

    public function Header(){
        $this->SetFont('helvetica','B',20);
        $this->Cell(0,15,'<< TCPDF Ejemplo>>',0,false,'C',0,'',false,'M','M');
    }

    public function Footer(){
        $this->SetY(-15);
        $this->setFont('helvetica','I',8);
        $this->Cell(0,10,'Pagina '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(),'C',0,'',0,false,'T','M');
    }
}