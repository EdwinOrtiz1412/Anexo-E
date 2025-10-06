<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Dependencia;
use Exception;
use Excel;
use App\Exports\DependenciasExport;
use App\Http\Utils\ReportePDF;

class DependenciaController extends Controller
{

    public function list()
    {
        //$dependencias = Dependencia::leftJoin("titulares","titulares.idDependencia","=","dependencia.idDependencia")->get();
        $dependencias = Dependencia::where("status",1)->get();
        return view("super.dependencias")->with("dependencias", $dependencias);
    }

    public function save(Request $req)
    {
        try {
            if ($req->idDependencia == "") {
                $existe = Dependencia::where("status",1)->where("numeroUR", $req->numeroUR)->get();
                if (count($existe) > 0) {
                    return response()->json([
                        'success' => 'error',
                        'message' => 'el numero de UR ya existe!',
                    ], 200);
                } else {
                    //nueva dependencia
                    $dependencia = new Dependencia();
                    $dependencia->numeroUR = $req->numeroUR;
                    $dependencia->dependenciaNombre = $req->dependenciaNombre;
                    $dependencia->dependenciaSiglas = $req->dependenciaSiglas;
                    $dependencia->save();
                    $message = "Dependencia creada satisfactoriamente!";
                }
            } else {
                $existe = Dependencia::where("status",1)->where("numeroUR", $req->numeroUR)->where("idDependencia", "!=", $req->idDependencia)->get();
                if (count($existe) > 0) {
                    return response()->json([
                        'success' => 'error',
                        'message' => 'el numero de UR ya existe!',
                    ], 200);
                } else {
                    //actualizacion de la dependencia
                    $dependencia = Dependencia::where("status",1)->where("idDependencia", $req->idDependencia)->update(
                        [
                            "numeroUR" => $req->numeroUR,
                            "dependenciaNombre" => $req->dependenciaNombre,
                            "dependenciaSiglas" => $req->dependenciaSiglas
                        ]
                    );
                    $message = "Dependencia actualizada satisfactoriamente!";
                }
            }
            return response()->json([
                'success' => 'ok',
                'message' => $message,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => 'error',
                'message' => 'Ocurrió un error al procesar la Dependencia, Intente más tarde!',
            ], 500);
        }
    }

    public function delete(Request $req){
        try{
            $baja = Dependencia::where("idDependencia",$req->idDependencia)->update([
                "status"=>false
            ]);
            return response()->json([
                'success' => 'ok',
                'message' => 'Dependencia dada de baja Satisfactoriamente!',
            ], 200);
        }catch(Exception $ex){
            return response()->json([
                'success' => 'error',
                'message' => 'Ocurrió un error al dar de baja a la dependencia!',
            ], 200);
        }
    }

    public function downloaddependencias()
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
            $pdf->Cell(10, 15, 'Listado de Dependencias', 0, false, 'L', 0, '', 0, false, 'M', 'M');
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
            $pdf->Cell(10, 15, 'Fecha de Impresión: '.date("Y-m-d H:i:s"), 0, false, 'L', 0, '', 0, false, 'M', 'M');
            $pdf->SetX(0);
            $pdf->SetY(-15);
            $pdf->Cell(270, 15, 'Página: '.$pdf->getAliasNumPage()."/".$pdf->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'M', 'M');
        });


        // ReportePDF::SetHeaderData("images/header_line.png", 25, "Reporte de Indicadores Estratégicos", "NINGUNO");
        ReportePDF::SetTitle('Instancia Técnica de Evaluación - SIIBien Listado de Dependencias');
        ReportePDF::SetMargins(10, 23, 10);
        //ReportePDF::SetHeaderMargin(25);
        ReportePDF::AddPage('L','P');

        //Enlace
        $dependencias =  Dependencia::where("status",1)->get();

        $html = \View::make("super.downloaddependencias")->with("dependencias", $dependencias);

        ReportePDF::writeHTML($html, true, false, true, false, '');

        ReportePDF::Output(public_path('listado_dependencias.pdf'), 'I');

    }

    public function downloaddependenciasxls(){
        return Excel::download(new DependenciasExport, 'dependencias'.date('YmdHis').'.xlsx');
    }

    public function downloaddependenciascsv(){
        return Excel::download(new DependenciasExport, 'dependencias'.date('YmdHis').'.csv',\Maatwebsite\Excel\Excel::CSV);
    }
}
