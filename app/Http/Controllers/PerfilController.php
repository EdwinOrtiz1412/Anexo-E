<?php

namespace App\Http\Controllers;

use App\Models\EnlaceDependencia;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Utils\ReportePDF;

class PerfilController extends Controller
{
    public function edit(Request $request): View
    {
        $idEnlaceDependencia = $request->user()->idEnlaceDependencia;
        $infoEnlace = EnlaceDependencia::where("idEnlaceDependencia", $idEnlaceDependencia)->first();
        return view('perfil.edit', [
            'user' => $request->user(),
            'enlace' => $infoEnlace
        ]);
    }

    public function update(Request $request)
    {
        try {
            DB::beginTransaction();
            EnlaceDependencia::where("idEnlaceDependencia", $request->idEnlaceDependencia)->update([
                "titulo" => $request->titulo,
                "nombre" => $request->nombre,
                "apellidoP" => $request->apellidoP,
                "apellidoM" => $request->apellidoM,
                "cargo" => $request->cargo,
                //"tipoEnlace" => $request->tipoEnlace,
                "email" => $request->email,
                "telefono" => $request->telefono,
                "celular" => $request->celular,
                "teloficina" => $request->teloficina,
                "extension" => $request->extension
            ]);
            DB::commit();
            return response()->json([
                'success' => 'ok',
                'message' => 'Perfil Actualizado Satisfactoriamente!'
            ], 200);
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json([
                'success' => 'error',
                'message' => 'Ocurrió un error al Actualizar el perfil, intente más tarde!'
            ], 200);
        }
    }

    public function changepassword(Request $request)
    {
        try {
            DB::beginTransaction();
            $passcorrecta = User::where("id", Auth::id())->first();
            $passwordform = $request->passwordactual;

            if (Hash::check($passwordform, $passcorrecta->password)) {

                User::where("id", Auth::id())->update([
                    "password" => Hash::make($request->passwordconfirmada),
                    "enc" => base64_encode($request->passwordconfirmada)
                ]);
                DB::commit();
                return response()->json([
                    'success' => 'ok',
                    'message' => 'Contraseña actualizada correctamente!'
                ], 200);
            } else {
                return response()->json([
                    'success' => 'error',
                    'message' => 'La contraseña del usuario no es correcta!'
                ], 200);
            }
        } catch (Exception $ex) {
            DB::beginTransaction();
            return response()->json([
                'success' => 'error',
                'message' => 'No pudo actualizarse la contraseña correctamente, intente más tarde!' . $ex
            ], 200);
        }
    }

    public function responsiva(Request $request)
    {
        ReportePDF::setHeaderCallback(function ($pdf) {
            $image_file = public_path("images/siibien_colores.png");
            $pdf->Image($image_file, 150, 5, 50, '', 'PNG', '', 'T', false, 100, '', false, false, 0, false, false, false);
            $image_file = public_path("images/logo_finanzas.png");
            $pdf->Image($image_file, 10, 5, 60, '', 'PNG', '', 'T', false, 100, '', false, false, 0, false, false, false);
            //$pdf->SetFont($font_family = "timesb", $variant = "", $fontsize = 11);
            $pdf->SetFont('times', 'B', 14);

            $pdf->SetY(30);
            $pdf->SetX(0);
            $pdf->SetFontSize(14);
            $pdf->setTextColor(104, 27, 46);
            $pdf->Cell(210, 0, 'RESPONSIVA DE USUARIO', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        });


        ReportePDF::setFooterCallback(function ($pdf) {
            $pdf->SetFont('times', 'B', 14);
            $pdf->SetX(0);
            $pdf->SetY(-15);
            $pdf->SetFontSize(8);
            //$pdf->Cell(10, 15, 'Fecha de Impresión: '.date("Y-m-d H:i:s"), 0, false, 'L', 0, '', 0, false, 'M', 'M');
            $pdf->SetY(-15);
            $text1 = "Ciudad Administrativa, Edificio 3 “Andrés Henestrosa”, Primer Nivel, Carretera Internacional Oaxaca-Istmo";
            $text2 = "Km. 11.5, Tlalixtac de Cabrera, Oaxaca; C.P. 68270 Tel. Conmutador.  01(951) 50 150 00 Extensión 11252";
            $pdf->setTextColor(104, 27, 46);
            $pdf->Cell(180, 10, $text1, 0, false, 'R', 0, '', 0, false, 'M', 'M');
            $pdf->SetY(-10);
            $pdf->Cell(180, 10, $text2, 0, false, 'R', 0, '', 0, false, 'M', 'M');
        });

        // ReportePDF::SetHeaderData("images/header_line.png", 25, "Reporte de Indicadores Estratégicos", "NINGUNO");
        ReportePDF::SetTitle('Responsiva de cuenta - Instancia Técnica de Evaluación');
        ReportePDF::SetMargins(15, 30, 15);
        //ReportePDF::SetHeaderMargin(25);
        ReportePDF::AddPage();
        ReportePDF::SetFont('times', '', 10);

        if (!isset($request->idEnlaceDependencia)) {
            $enlace = EnlaceDependencia::where("enlacedependencia.idEnlaceDependencia", session("idEnlaceDependencia"))
                ->join("users", "users.idEnlaceDependencia", '=', "enlacedependencia.idEnlaceDependencia")
                ->leftjoin("dependencia", "enlacedependencia.idDependencia", '=', "dependencia.idDependencia")
                ->first();
        } else {
            $enlace = EnlaceDependencia::where("enlacedependencia.idEnlaceDependencia", $request->idEnlaceDependencia)
                ->join("users", "users.idEnlaceDependencia", '=', "enlacedependencia.idEnlaceDependencia")
                ->leftjoin("dependencia", "enlacedependencia.idDependencia", '=', "dependencia.idDependencia")
                ->first();
        }




        $html = \View::make("perfil.responsiva")->with("enlace", $enlace);

        ReportePDF::writeHTML($html, true, false, true, false, '');

        ReportePDF::Output(public_path('responsiva' . Auth::id() . '.pdf'), 'I');
    }
}
