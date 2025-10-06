<?php

namespace App\Http\Controllers;

use App\Exports\TitularesExport;
use Illuminate\Http\Request;
use \App\Models\Titular;
use \App\Models\Dependencia;
use App\Http\Utils\ReportePDF;
use Excel;


class TitularController extends Controller
{
    public function list()
    {
        $titulares = Titular::where("titulares.status", 1)
            ->join("dependencia", "dependencia.idDependencia", "=", "titulares.idDependencia")->orderBy("idTitular", "DESC")->get();
        $dependencias = Dependencia::where("status", 1)->get();
        return view("super.titulares")->with("titulares", $titulares)->with("dependencias", $dependencias);
    }

    public function save(Request $req)
    {
        try {
            if ($req->idTitular == "") {
                $existe = Titular::where("status", 1)->where("idDependencia", $req->idDependencia)->get();
                if (count($existe) > 0) {
                    return response()->json([
                        'success' => 'error',
                        'message' => 'Ya está registrado un titular vigente para la dependencia seleccionada, Elimine el Titular asignado y continúe con el registro!',
                    ], 200);
                } else {
                    //nuevo titular
                    $titular = new Titular();
                    $titular->idDependencia = $req->idDependencia;
                    $titular->nombre = $req->nombre;
                    $titular->cargo = $req->cargo;
                    $titular->save();
                    $message = "Titular almacenado satisfactoriamente!";
                }
            } else {
                $existe = Titular::where("status", 1)->where("idDependencia", $req->idDependencia)->where("idTitular", "!=", $req->idTitular)->get();
                if (count($existe) > 0) {
                    return response()->json([
                        'success' => 'error',
                        'message' => 'Ya está registrado un titular vigente para la dependencia seleccionada, Elimine el Titular asignado y continúe con el registro!',
                    ], 200);
                } else {
                    //actualizacion del titular
                    $titular = titular::where("status", 1)->where("idTitular", $req->idTitular)->update(
                        [
                            "idDependencia" => $req->idDependencia,
                            "nombre" => $req->nombre,
                            "cargo" => $req->cargo
                        ]
                    );
                    $message = "Titular actualizado satisfactoriamente!";
                }
            }
            return response()->json([
                'success' => 'ok',
                'message' => $message,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => 'error',
                'message' => 'Ocurrió un error al procesar el Titular, Intente más tarde!',
            ], 500);
        }
    }

    public function delete(Request $req)
    {
        try {
            $baja = Titular::where("idTitular", $req->idTitular)->update([
                "status" => false
            ]);
            return response()->json([
                'success' => 'ok',
                'message' => 'Titular dado de baja Satisfactoriamente!',
            ], 200);
        } catch (Exception $ex) {
            return response()->json([
                'success' => 'error',
                'message' => 'Ocurrió un error al dar de baja al titular!',
            ], 200);
        }
    }

    public function downloadtitulares()
    {
        ReportePDF::setHeaderCallback(function ($pdf) {
            $image_file = public_path("images/siibien_colores.png");
            $pdf->Image($image_file, 230, 5, 50, '', 'PNG', '', 'T', false, 100, '', false, false, 0, false, false, false);
            $pdf->SetFont('helvetica', 'B', 12);
            //$pdf->SetFont('montserratsemib');
            $pdf->SetX(0);
            $pdf->SetY(10);
            $pdf->SetFontSize(16);
            $pdf->Cell(0, 20, 'Sistema de Seguimiento Integral a los Indicadores de Bienestar (SIIBien)', 0, false, 'L', 0, '', 0, false, 'M', 'M');
            $pdf->SetX(0);
            $pdf->SetY(18);
            $pdf->SetFontSize(11);
            $pdf->Cell(10, 15, 'Listado de Titulares', 0, false, 'L', 0, '', 0, false, 'M', 'M');
            $pdf->SetDrawColor(104, 27, 46);
            //$pdf->Line(15, 23, 200, 23);
            $pdf->SetLineStyle(array('width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(104, 27, 46)));
            $pdf->Line(15, 15, 230, 15);
        });

        ReportePDF::setFooterCallback(function ($pdf) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->SetX(0);
            $pdf->SetY(-15);
            $pdf->SetFontSize(8);
            $pdf->Cell(10, 15, 'Fecha de Impresión: ' . date("Y-m-d H:i:s"), 0, false, 'L', 0, '', 0, false, 'M', 'M');
            $pdf->SetX(0);
            $pdf->SetY(-15);
            $pdf->Cell(270, 15, 'Página: ' . $pdf->getAliasNumPage() . "/" . $pdf->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'M', 'M');
        });


        // ReportePDF::SetHeaderData("images/header_line.png", 25, "Reporte de Indicadores Estratégicos", "NINGUNO");
        ReportePDF::SetTitle('Instancia Técnica de Evaluación - SIIBien Listado de Titulares');
        ReportePDF::SetMargins(10, 23, 10);
        //ReportePDF::SetHeaderMargin(25);
        ReportePDF::AddPage('L', 'P');

        //Enlace
        $titulares = Titular::where("titulares.status",1)
        ->join("dependencia","dependencia.idDependencia","=","titulares.idDependencia")->orderBy("idTitular","DESC")->get();

        $html = \View::make("super.downloadtitulares")->with("titulares", $titulares);

        ReportePDF::writeHTML($html, true, false, true, false, '');

        ReportePDF::Output(public_path('listado_titulares.pdf'), 'I');
    }

    public function downloadtitularesxls()
    {
        return Excel::download(new TitularesExport, 'titulares' . date('YmdHis') . '.xlsx');
    }

    public function downloadtitularescsv()
    {
        return Excel::download(new TitularesExport, 'titulares' . date('YmdHis') . '.csv', \Maatwebsite\Excel\Excel::CSV);
    }
}
