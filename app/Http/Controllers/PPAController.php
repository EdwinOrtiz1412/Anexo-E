<?php

namespace App\Http\Controllers;


use Exception;
use App\Models\PPA;
use App\Models\EjePED;
use App\Models\TemaPED;
use App\Models\LineaPED;
use App\Models\PPAMedios;
use App\Exports\PPAsExport;
use App\Models\Dependencia;
use App\Models\ObjetivoPED;
use Illuminate\Http\Request;
use App\Models\EstrategiaPED;
use App\Http\Utils\ReportePDF;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\ProgramasPresupuestales;

class PPAController extends Controller
{
    //
    public function index()
    {
        $dependencias = Dependencia::all();
        $ejes = EjePED::all();
        $programas = ProgramasPresupuestales::all();
        return view('ppa.index', ['dependencias' => $dependencias, 'ejes' => $ejes, 'programas' => $programas]);
    }

    public function store(Request $request)
    {
        try {
            $alineacionped = $request->eje_ped . "|" . $request->tema_ped . "|" . $request->objetivo_ped . "|" . $request->estrategia_ped . "|" . $request->linea_ped;
            $programas = "";
            if ((isset($request->programa_))) {
                foreach ($request->programa_ as $programa) {
                    $programas .= $programa . "|";
                }
            }

            $poblacion_objetivo = $request->p_o . "|" . $request->p_o_m . "|" . $request->p_o_h;
            $poblacion_atendida = $request->p_a . "|" . $request->p_a_m . "|" . $request->p_a_h;
            $poblacion_atender = $request->p_pa . "|" . $request->p_pa_m . "|" . $request->p_pa_h;

            $regiones = "";
            foreach ($request->region as $region) {
                $regiones .= $region . "|";
            }

            //dd($alineacionped);

            DB::beginTransaction();
            if (isset($request->id)) {
                $ppa = PPA::find($request->id);
                $ppa->periodo = $request->periodo;
                $ppa->nombre = $request->nombre;
                $ppa->objetivo = $request->objetivo;
                $ppa->descripcion = $request->descripcion;
                $ppa->cobertura = $request->cobertura;
                $ppa->alineacion_ped = $alineacionped;
                $ppa->alineacion_pp = $programas;
                $ppa->fuente_financiamiento = $request->fuente_financiamiento;
                $ppa->monto_inversion = $request->monto_inversion;
                $ppa->monto_ejercido = $request->monto_ejercido;
                $ppa->descripcion_bs = $request->descripcion_bs;
                $ppa->entregas_bs = $request->entregas_bs;
                $ppa->um_bs = $request->um_bs;
                $ppa->tipo_beneficiario = $request->tipo_beneficiario;
                $ppa->descripcion_beneficiario = $request->descripcion_beneficiario;
                $ppa->poblacion_objetivo = $poblacion_objetivo;
                $ppa->poblacion_atendida = $poblacion_atendida;
                $ppa->poblacion_atender = $poblacion_atender;
                $ppa->regiones = $regiones;
                $ppa->municipios = $request->municipios;
                $ppa->impacto_social = $request->impacto_social;
                $ppa->impacto_economico = $request->impacto_economico;
                $ppa->impacto_ambiental = $request->impacto_ambiental;
                $ppa->fecha_evento = $request->fecha_evento;
                $ppa->observaciones = $request->observaciones_generales;
                $ppa->dependencia_id = $request->dependencia;
                if ($ppa->save()) {
                    //Almacenamos los medios de verificacion
                    $cont = -1;
                    if (isset($request->medio)) {
                        foreach ($request->medio as $medio) {
                            $cont++;
                            PPAMedios::where("id", $medio)->update([
                                "descripcion" => $request->descripcionmedio[$cont]
                            ]);
                        }
                    }
                }
                DB::commit();
                return response()->json([
                    'success' => 'ok',
                ]);
            } else {
                $ppa = new PPA();
                $ppa->periodo = $request->periodo;
                $ppa->nombre = $request->nombre;
                $ppa->objetivo = $request->objetivo;
                $ppa->descripcion = $request->descripcion;
                $ppa->cobertura = $request->cobertura;
                $ppa->alineacion_ped = $alineacionped;
                $ppa->alineacion_pp = $programas;
                $ppa->fuente_financiamiento = $request->fuente_financiamiento;
                $ppa->monto_inversion = $request->monto_inversion;
                $ppa->monto_ejercido = $request->monto_ejercido;
                $ppa->descripcion_bs = $request->descripcion_bs;
                $ppa->entregas_bs = $request->entregas_bs;
                $ppa->um_bs = $request->um_bs;
                $ppa->tipo_beneficiario = $request->tipo_beneficiario;
                $ppa->descripcion_beneficiario = $request->descripcion_beneficiario;
                $ppa->poblacion_objetivo = $poblacion_objetivo;
                $ppa->poblacion_atendida = $poblacion_atendida;
                $ppa->poblacion_atender = $poblacion_atender;
                $ppa->regiones = $regiones;
                $ppa->municipios = $request->municipios;
                $ppa->impacto_social = $request->impacto_social;
                $ppa->impacto_economico = $request->impacto_economico;
                $ppa->impacto_ambiental = $request->impacto_ambiental;
                $ppa->fecha_evento = $request->fecha_evento;
                $ppa->observaciones = $request->observaciones_generales;
                $ppa->dependencia_id = $request->dependencia;
                $ppa->user_id = auth()->user()->id;

                if ($ppa->save()) {
                    //Almacenamos los medios de verificacion
                    $cont = -1;
                    if (isset($request->mediooriginal)) {
                        foreach ($request->mediooriginal as $medio) {
                            $cont++;
                            $mediog = new PPAMedios();
                            $mediog->ppa_id = $ppa->id;
                            $mediog->original = $medio;
                            $mediog->real = $request->medioreal[$cont];
                            $mediog->descripcion = $request->descripcionmedio[$cont];
                            if ($mediog->save()) {
                                $carpeta = 'medios/ppa/' . $ppa->id;
                                if (!file_exists($carpeta)) {
                                    mkdir($carpeta, 0777, true);
                                }
                                rename(public_path('medios/ppa/') . $medio, public_path('medios/ppa/') . $ppa->id . "/" . $medio);
                            }
                        }
                    }
                }
                DB::commit();
            }

            return response()->json([
                'success' => 'ok',
            ]);
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json([
                'success' => 'error',
                'message' => "Error al intentar actualizar el PPA" . $ex
            ]);
        }
    }

    public function listado()
    {
        if (session("idDependencia") == 0)
            $ppas = PPA::where("status", 1)->get();
        else
            $ppas = PPA::where("dependencia_id", session("idDependencia"))
                ->where("status", 1)
                ->get();
        return view('ppa.list', ['ppas' => $ppas]);
    }

    public function edit($id)
    {
        $ppa = PPA::where("id", $id)->first();
        $medios = PPAMedios::where("ppa_id", $id)->get();
        $dependencias = Dependencia::all();
        $ejes = EjePED::all();
        $programas = ProgramasPresupuestales::all();
        return view('ppa.edit', ['ppa' => $ppa, 'dependencias' => $dependencias, 'ejes' => $ejes, 'programas' => $programas, 'medios' => $medios]);
    }

    public function download($id)
    {

        ReportePDF::setHeaderCallback(function ($pdf) {
            $image_file = public_path("images/siibien_colores.png");
            $pdf->Image($image_file, 150, 6, 50, '', 'PNG', '', 'T', false, 100, '', false, false, 0, false, false, false);
            $image_file = public_path("images/logo_gabinete.png");
            //$pdf->Image($image_file, 10, 5, 50, '', 'PNG', '', 'T', false, 100, '', false, false, 0, false, false, false);
            $pdf->SetFont('helvetica', 'B', 11);
            //$pdf->SetFont('montserratsemib');

            $pdf->SetY(10);
            $pdf->SetX(15);
            $pdf->SetFontSize(12);
            $pdf->Cell(0, 20, 'Informe de Avances y Resultados de la Transformación de Oaxaca', 0, false, 'L', 0, '', 0, false, 'M', 'M');
            $pdf->SetY(18);
            $pdf->SetX(15);
            $pdf->SetFontSize(11);
            $pdf->Cell(10, 15, 'Reporte de Seguimiento Trimestral (IARTO)', 0, false, 'L', 0, '', 0, false, 'M', 'M');
            $pdf->SetDrawColor(104, 27, 46);
            //$pdf->Line(15, 23, 200, 23);
            $pdf->SetLineStyle(array('width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(104, 27, 46)));
            $pdf->Line(15, 15, 120, 15);
        });


        ReportePDF::setFooterCallback(function ($pdf) {
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->SetX(0);
            $pdf->SetY(-15);
            $pdf->SetFontSize(8);
            $pdf->Cell(10, 15, 'Fecha de Impresión: ' . date("Y-m-d H:i:s"), 0, false, 'L', 0, '', 0, false, 'M', 'M');
            $pdf->SetY(-15);
            $pdf->Cell(200, 15, 'Página: ' . $pdf->getAliasNumPage() . "/" . $pdf->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'M', 'M');
        });

        // ReportePDF::SetHeaderData("images/header_line.png", 25, "Reporte de Indicadores Estratégicos", "NINGUNO");
        ReportePDF::SetTitle('Reporte IARTP - Gobierno del Estado');
        ReportePDF::SetMargins(10, 23, 10);
        //ReportePDF::SetHeaderMargin(25);
        ReportePDF::AddPage();
        ReportePDF::SetFontSize(10);


        //Información del Indicador
        $infoPPA = PPA::find($id);
        $dependencia = Dependencia::where("idDependencia", $infoPPA->dependencia_id)->first();

        $periodo_s = $infoPPA->periodo;
        switch ($periodo_s[0]) {
            case 1:
                $periodo = "Enero-Marzo ";
                break;
            case 2:
                $periodo = "Abril-Junio ";
                break;
            case 3:
                $periodo = "Julio-Septiembre ";
                break;
            case 4:
                $periodo = "Octubre-Diciembre ";
                break;
        }
        $periodo .= $periodo_s[1] . $periodo_s[2] . $periodo_s[3] . $periodo_s[4];
        $ejes_s = $infoPPA->alineacion_ped;
        $ejes_s = explode("|", $ejes_s);

        $ejeped = "";
        $temaped = "";
        $objetivoped = "";
        $estrategiaped = "";
        $lineaped = "";

        if ($ejes_s[0] != '')
            $ejeped = EjePED::where("idEjePED", $ejes_s[0])->first();

        if ($ejes_s[1] != '')
            $temaped = TemaPED::where("idTemaPED", $ejes_s[1])->first();

        if ($ejes_s[2] != '')
            $objetivoped = ObjetivoPED::where("idObjetivoPED", $ejes_s[2])->first();

        if ($ejes_s[3] != '')
            $estrategiaped = EstrategiaPED::where("idEstrategiaPED", $ejes_s[3])->first();

        if ($ejes_s[4] != '')
            $lineaped = LineaPED::where("idLAPED", $ejes_s[4])->first();

        $programas = $infoPPA->alineacion_pp;
        $programas = explode("|", $programas);

        $presupuestarios = [];
        if (count($programas) > 0) {
            foreach ($programas as $programa) {
                if ($programa != '') {
                    $prog = ProgramasPresupuestales::where("idPrograma", $programa)->first();
                    $presupuestarios[] = $prog->clavePrograma . " " . $prog->descripcionPrograma;
                }
            }
        }

        $regiones = explode("|", $infoPPA->regiones);
        array_pop($regiones);
        $regionesArray = [
            "caniada" => "Sierra de Flores Magón",
            "istmo" => "Istmo de Tehuantepec",
            "sierra_sur" => "Sierra Sur",
            "sierra_norte" => "Sierra de Juárez",
            "papaloapam" => "Papaloapan",
            "mixteca" => "Mixteca",
            "costa" => "Costa",
            "valles_centrales" => "Valles Centrales"
        ];

        $medios = PPAMedios::where("ppa_id", $infoPPA->id)->get();

        //Variables del Indicador

        //Titular
        $titular = DB::table("titulares")->where("idDependencia", $infoPPA->dependencia_id)->first();

        //Enlace
        $enlace = DB::table("enlacedependencia")->where("idEnlaceDependencia", auth()->user()->idEnlaceDependencia)->first();

        $html = \View::make("ppa.download")->with("ppa", $infoPPA)
            ->with("titular", $titular)
            ->with("enlace", $enlace)
            ->with('periodo', $periodo)
            ->with('ejeped', $ejeped)
            ->with('temaped', $temaped)
            ->with('estrategiaped', $estrategiaped)
            ->with('objetivoped', $objetivoped)
            ->with('programas', $presupuestarios)
            ->with('lineaped', $lineaped)
            ->with('regiones', $regiones)
            ->with('regiones_array', $regionesArray)
            ->with('medios', $medios)
            ->with('dependencia', $dependencia);
        //die($html);

        ReportePDF::writeHTML($html, true, false, true, false, '');

        ReportePDF::Output(public_path('ppa' . $id . '.pdf'), 'I');
        //return response()->download(public_path('indicador'.$indicador.'.pdf'));

    }

    public function medioupload(Request $req)
    {
        try {
            $medio = $req->file('file');
            //dd($medio->getClientOriginalName());
            $extension = $medio->extension();
            $random = time() . rand(1, 100);
            $nombreMedio =  $random . '.' . $medio->extension();
            if (isset($req->ppa_id)) {
                $carpeta = 'medios/ppa/' . $req->ppa_id;
                if (!file_exists($carpeta)) {
                    mkdir($carpeta, 0777, true);
                }
                $medio->move(public_path('medios/ppa/' . $req->ppa_id . "/"), $nombreMedio);
                DB::beginTransaction();
                $mediog = new PPAMedios();
                $mediog->ppa_id = $req->ppa_id;
                $mediog->original = $nombreMedio;
                $mediog->real = $medio->getClientOriginalName();
                $mediog->save();
                DB::commit();
                return response()->json([
                    'success' => 'ok',
                    'message' => 'Medio cargado Satisfactoriamente!',
                    'filename' => $nombreMedio,
                    'medio_id' => $mediog->id
                ]);
            } else {
                $medio->move(public_path('medios/ppa/'), $nombreMedio);
            }
            return response()->json([
                'success' => 'ok',
                'message' => 'Medio cargado Satisfactoriamente!',
                'random' => $random,
                'filename' => $nombreMedio,
                'extension' => $extension
            ]);
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json([
                'success' => 'error',
                'message' => 'Ocurrió un error al cargar el medio!' . $ex,
            ]);
        }
    }

    public function mediotempremove(Request $req)
    {
        if (isset($req->base)) {
            //Procedemos a eliminar el registro de la base y a eliminar el medio cargado
            $infoMedio = PPAMedios::find($req->medio_id);
            $file = public_path('medios/ppa/') . $req->ppa_id . "/" . $infoMedio->original;
            try {
                if (file_exists($file)) {
                    if (unlink($file)) {
                        $infoMedio->delete();
                    }
                }
                return response()->json([
                    'success' => 'ok',
                    'message' => 'Medio eliminado satisfactoriamente!',
                ]);
            } catch (Exception $ex) {
                return response()->json([
                    'success' => 'error',
                    'message' => 'Ocurrió un error al eliminar el medio!',
                ]);
            }
        } else {
            $file = public_path('medios/ppa/') . $req->medio . "." . $req->extension;
            try {
                if (file_exists($file)) {
                    unlink($file);
                }
                return response()->json([
                    'success' => 'ok',
                    'message' => 'Medio temporal eliminado satisfactoriamente!',
                ]);
            } catch (Exception $ex) {
                return response()->json([
                    'success' => 'error',
                    'message' => 'Ocurrió un error al eliminar el medio temporal!',
                ]);
            }
        }
    }

    public function adminppas()
    {
        if (session("idDependencia") == 0) {
            $ppas = PPA::select('*')
                ->join('dependencia', 'dependencia.idDependencia', '=', 'ppa.dependencia_id')
                ->where("ppa.status", 1)
                ->get();
        } else {
            $ppas = PPA::where("dependencia_id", session("idDependencia"))
                ->where("ppa.status", 1)
                ->get();
        }
        return view('super.ppas', ['ppas' => $ppas]);
    }

    public function admindownloadxlsx()
    {
        return Excel::download(new PPAsExport, 'PPAs' . date('YmdHis') . '.xlsx');
    }

    public function oficializar(Request $request)
    {
        ReportePDF::setHeaderCallback(function ($pdf) {
            $image_file = public_path("images/siibien_colores.png");
            $pdf->Image($image_file, 230, 6, 50, '', 'PNG', '', 'T', false, 100, '', false, false, 0, false, false, false);
            $image_file = public_path("images/logo_gabinete.png");
            //$pdf->Image($image_file, 10, 5, 50, '', 'PNG', '', 'T', false, 100, '', false, false, 0, false, false, false);
            $pdf->SetFont('helvetica', 'B', 11);
            //$pdf->SetFont('montserratsemib');

            $pdf->SetY(10);
            $pdf->SetX(15);
            $pdf->SetFontSize(12);
            $pdf->Cell(0, 20, 'Informe de Avances y Resultados de la Transformación de Oaxaca', 0, false, 'L', 0, '', 0, false, 'M', 'M');
            $pdf->SetY(18);
            $pdf->SetX(15);
            $pdf->SetFontSize(11);
            $pdf->Cell(10, 15, 'Oficialización de entrega IARTO. ', 0, false, 'L', 0, '', 0, false, 'M', 'M');
            $pdf->SetDrawColor(104, 27, 46);
            //$pdf->Line(15, 23, 200, 23);
            $pdf->SetLineStyle(array('width' => 1, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array(104, 27, 46)));
            $pdf->Line(15, 15, 120, 15);
        });


        ReportePDF::setFooterCallback(function ($pdf) {
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->SetX(0);
            $pdf->SetY(-15);
            $pdf->SetFontSize(8);
            $pdf->Cell(10, 15, 'Fecha de Impresión: ' . date("Y-m-d H:i:s"), 0, false, 'L', 0, '', 0, false, 'M', 'M');
            $pdf->SetY(-15);
            $pdf->Cell(200, 15, 'Página: ' . $pdf->getAliasNumPage() . "/" . $pdf->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'M', 'M');
        });

        // ReportePDF::SetHeaderData("images/header_line.png", 25, "Reporte de Indicadores Estratégicos", "NINGUNO");
        ReportePDF::SetTitle('Reporte IARTO - Gobierno del Estado');
        ReportePDF::SetMargins(10, 12, 10);
        //ReportePDF::SetHeaderMargin(25);
        ReportePDF::AddPage("L");
        ReportePDF::SetFontSize(10);


        $dependencia = Dependencia::where("idDependencia", session("idDependencia"))->first();


        $ppas = PPA::where("dependencia_id",session('idDependencia'))
                    ->where('periodo',$request->periodo)->get();


        //Titular
        $titular = DB::table("titulares")->where("idDependencia", auth()->user()->idEnlaceDependencia)->first();

        //Enlace
        $enlace = DB::table("enlacedependencia")->where("idEnlaceDependencia", auth()->user()->idEnlaceDependencia)->first();


        $periodo_s = $request->periodo;
        switch ($periodo_s[0]) {
            case 1:
                $periodo = "Enero-Marzo ";
                break;
            case 2:
                $periodo = "Abril-Junio ";
                break;
            case 3:
                $periodo = "Julio-Septiembre ";
                break;
            case 4:
                $periodo = "Octubre-Diciembre ";
                break;
        }

        $periodo .= $periodo_s['1'].$periodo_s['2'].$periodo_s['3'].$periodo_s['4'];

        $html = \View::make("ppa.oficializacion")->with("ppas", $ppas)
            ->with("titular", $titular)
            ->with("enlace", $enlace)
            ->with("dependencia",$dependencia)
            ->with("periodo",$periodo);
        //die($html);

        ReportePDF::writeHTML($html, true, false, true, false, '');

        ReportePDF::Output(public_path('oficializacion' . session("idDependencia") . '.pdf'), 'I');
        //return response()->download(public_path('indicador'.$indicador.'.pdf'));
    }
}
