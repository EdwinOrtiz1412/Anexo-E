<?php

namespace App\Http\Controllers;

use App\Models\IndicadorCrema;
use Excel;
use Exception;
use Faker\Core\Color;
use App\Models\EjePED;
use App\Models\Sector;
use App\Models\Variable;
use App\Models\Indicador;
use App\Models\Dependencia;
use App\Models\ObjetivoODS;
use App\Models\ObjetivoPED;
use App\Models\IndicadorOds;
use Illuminate\Http\Request;
use App\Http\Utils\ReportePDF;
use App\Models\ObjetivoSector;
use App\Models\IndicadorSector;
use App\Models\MediosIndicador;
use App\Models\EstrategiaSector;
use Illuminate\Http\JsonResponse;
use App\Exports\IndicadoresExport;
use App\Models\IndicadorObjetivos;
use App\Models\IndicadorProgramas;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\ProgramaPresupuestario;
// use App\Models\ProgramasPresupuestales;
use App\Exports\IndicadoresDetallesExport;
use App\Models\ValoresHistoricosIndicador;
use App\Models\ValoresProgramadosIndicador;
use Illuminate\Validation\Rule;
use App\Models\CremaComentario;
class IndicadorController extends Controller
{

    //
    public function index(): View
    {
        //die(Hash::make("g4b1n3t3"));
        $objetivos = ObjetivoPED::all();
        $objetivosods = ObjetivoODS::all();
        // $programaspresupuestales = ProgramasPresupuestales::all();
        $ejes = EjePED::all();

   $anios = ProgramaPresupuestario::query()
        ->whereNotNull('anio')
        ->whereBetween('anio', [2000, 2100])
        ->select('anio')
        ->distinct()
        ->orderByDesc('anio')
        ->pluck('anio');              

    $anioSeleccionado = $anios->first(); // el más reciente

   $programaspresupuestales = $anioSeleccionado
        ? ProgramaPresupuestario::where('anio', $anioSeleccionado)
            ->orderBy('clavePrograma')
            ->get()
        : collect();
        return view("indicador.index")->with('objetivos', $objetivos)->with('objetivosods', $objetivosods)->with('programaspresupuestales', $programaspresupuestales)->with('ejes',$ejes)->with('anios', $anios)->with('anioSeleccionado', $anioSeleccionado)->with('programaspresupuestales', $programaspresupuestales);
    }

    public function create(Request $ind): JsonResponse
    {
        DB::beginTransaction();
        try {
            //Almacenamos el indicador
            $indicador = new Indicador();
            $indicador->indicadorNombre = $ind->indicadorNombre;
            $indicador->indicadorObjetivo = $ind->indicadorObjetivo;
            $indicador->indicadorTipo = $ind->indicadorTipo;
            $indicador->indicadorDimension = $ind->indicadorDimension;
            $indicador->indicadorMetodo = $ind->indicadorMetodo;
            $indicador->indicadorFormula = $ind->indicadorFormula;
            $indicador->indicadorUM = $ind->indicadorUM;
            $indicador->indicadorInterpretacion = $ind->indicadorInterpretacion;
            $indicador->indicadorFrecuencia = $ind->indicadorFrecuencia;
            $indicador->indicadorTipoPeriodo = $ind->indicadorTipoPeriodo;
            $indicador->indicadorSentido = $ind->indicadorSentido;
            $indicador->indicadorDesagregacion = $ind->indicadorDesagregacion;
            $indicador->indicadorAnioLB = $ind->indicadorLB;
            $indicador->idDependencia = ((session("idDependencia") == "0") ? 1 : session("idDependencia"));
            $indicador->observaciones = $ind->indicadorObservaciones;
            $indicador->valorAnioLB = $ind->valorAnioLB;
            $indicador->fuente_informacion = $ind->fuente_informacion;
            $indicador->status = 1;
            $indicador->tipo = "IE";
            $indicador->proxima_actualizacion = $ind->proxima_actualizacion;
            $indicador->save();

            if ($ind->filled('idSector')) {
    IndicadorSector::create([
        'idIndicador' => $indicador->id,
        'idSector' => $ind->idSector,
        'idObjetivo',
        'idSector',
    ]);
}


            //Procedemos a almacenar las variables correspondientes
            $variablesNombres = explode("|", $ind->variablesNombres);
            $variablesUnidades = explode("|", $ind->variablesUnidades);
            array_pop($variablesNombres);
            array_pop($variablesUnidades);
            for ($x = 0; $x < count($variablesUnidades); $x++) {
                $variable = new Variable();
                $variable->variableNombre = $variablesNombres[$x];
                $variable->variableUM = $variablesUnidades[$x];
                $variable->idIndicador = $indicador->id;
                $variable->save();
            }

            //Ahora almacenamos las alineaciones
            $objetivos = explode("|", $ind->objetivos);
            $objetivosods = explode("|", $ind->objetivosods);
            $programaspresupuestales = explode("|", $ind->programaspresupuestales);
            $niveles = explode("|", $ind->niveles);
            array_pop($objetivos);
            array_pop($objetivosods);
            array_pop($programaspresupuestales);
            array_pop($niveles);

            $this->saveAlineacion($objetivos, $objetivosods, $programaspresupuestales,$niveles, $indicador->id);


            DB::commit();
            return response()->json([
                'success' => 'ok',
                'message' => 'El Indicador ha sido almacenado de manera Satisfactoria',
                'indicador' => $ind->indicadorNombre
            ], 200);
        } catch (Exception $ex) {
            DB::rollBack();
            die($ex);
        }
    }

    public function list(): View
    {
        $Indicadores = $this->getIndicadores();
        return view("indicador.list")->with('indicadores', $Indicadores);
    }
    public function info(Request $req): View
    {

        //Información del Indicador
        $infoIndicador = Indicador::select("*", "dependenciaNombre")
            ->join("dependencia", "dependencia.idDependencia", "=", "indicador.idDependencia")
            ->where("idIndicador", $req->indicador)->first();

        //Variables del Indicador
        $variables = Variable::select("*")->where("idIndicador", $req->indicador)->get();

        //Objetivos alineados PED
        $objetivos = IndicadorObjetivos::select("*", "objetivoPEDClave", "objetivoPEDDescripcion","temaPEDClave","temaPEDDescripcion","ejePEDClave","ejePEDDescripcion")
            ->join("objetivoped", "objetivoped.idObjetivoPED", "=", "indicadorobjetivos.idObjetivoPED")
            ->join("temaped", "temaped.idTemaPED", "=", "objetivoped.idTemaPED")
            ->join("ejeped", "ejeped.idEjePED", "=", "temaped.idEjePED")
            ->where("indicadorobjetivos.idIndicador", $req->indicador)->get();

        //ObjetivosODS alineados al PED
        $objetivosods = IndicadorOds::select("*", "clave", "descripcion")
            ->join("objetivos_ods", "objetivos_ods.id", "=", "indicadorods.idODS")
            ->where("indicadorods.idIndicador", $req->indicador)->get();

        //Programas Presupuestales
        $programas = IndicadorProgramas::select(
            'indicadorprogramas.*',
            'pp.clavePrograma',
            'pp.descripcionPrograma',
            'pp.anio'
        )
        ->join('programa_presupuestario as pp', 'pp.idPrograma', '=', 'indicadorprogramas.idPrograma')
        ->where('indicadorprogramas.idIndicador', $req->indicador)
        ->orderBy('pp.anio')
        ->orderBy('pp.clavePrograma')
        ->get();
    
        //Alineacion con Sectores
        $sectores = IndicadorSector::where("idIndicador",$req->indicador)
                ->join("sectores","sectores.idSector","=","indicadorsector.idSector")->get();

        return view("indicador.info")->with("indicador", $infoIndicador)->with("variables", $variables)->with("objetivos", $objetivos)->with("objetivosods", $objetivosods)->with("programas", $programas)->with("sectores",$sectores);
    }

    public function edit($id): View
    {
        $indicador = Indicador::select("*")->where("idIndicador", $id)->first();
        $objetivos = ObjetivoPED::all();
        $objetivosods = ObjetivoODS::all();
        $ejes = EjePED::all();
            $anios = ProgramaPresupuestario::query()
        ->select('anio')
        ->whereNotNull('anio')
        ->distinct()
        ->orderBy('anio', 'asc')
        ->pluck('anio');

    $anioSeleccionado = $anios->first();

    // Programas del año seleccionado
    $programaspresupuestales = ProgramaPresupuestario::query()
        ->where('anio', $anioSeleccionado)
        ->orderBy('clavePrograma')   
        ->get();
        $variables = Variable::all()->where("idIndicador", $id);
            $sectores = Sector::all(); // <--- Agregado aquí
            $sectorAsignado = IndicadorSector::where('idIndicador', $id)->first();

        $indicadorObjetivos = DB::table("indicadorobjetivos")->where("idIndicador", $id)
                                ->join("objetivoped","objetivoped.idObjetivoPED","=","indicadorobjetivos.idObjetivoPED")
                                ->join("temaped","temaped.idTemaPED","=","objetivoped.idTemaPED")
                                ->get();
        $indicadorObjetivosods = DB::table("indicadorods")->where("idIndicador", $id)->get();
        $indicadorProgramas = DB::table("indicadorprogramas")->where("idIndicador", $id)->get();
        return view("indicador.edit", compact('objetivos', 'objetivosods', 'programaspresupuestales', 'indicador', 'variables', 'indicadorObjetivos', 'indicadorObjetivosods', 'indicadorProgramas','ejes', 'sectores', 'sectorAsignado','anios', 'anioSeleccionado'));
    }

    public function update(Request $data)
    {
        DB::beginTransaction();
        try {
            $indicador = Indicador::where("idIndicador", $data->idIndicador)
                ->update([
                    'indicadorNombre' => $data->indicadorNombre,
                    'indicadorObjetivo' => $data->indicadorObjetivo,
                    'indicadorTipo' => $data->indicadorTipo,
                    'indicadorDimension' => $data->indicadorDimension,
                    'indicadorMetodo' => $data->indicadorMetodo,
                    'indicadorFormula' => $data->indicadorFormula,
                    'indicadorUM' => $data->indicadorUM,
                    'indicadorInterpretacion' => $data->indicadorInterpretacion,
                    'indicadorFrecuencia' => $data->indicadorFrecuencia,
                    'indicadorTipoPeriodo' => $data->indicadorTipoPeriodo,
                    'indicadorSentido' => $data->indicadorSentido,
                    'indicadorDesagregacion' => $data->indicadorDesagregacion,
                    'indicadorAnioLB' => $data->indicadorLB,
                    'fuente_informacion' => $data->fuente_informacion,
                    'valorAnioLB' => $data->valorAnioLB,
                    'proxima_actualizacion' => $data->proxima_actualizacion,
                    'observaciones' => $data->indicadorObservaciones
                ]);

            //Procesamos las variables que se actualizan
            $actualizadas = $data->actualiza;
            $borradas = $data->borra;
            $nuevas = $data->nueva;

            if (strlen($actualizadas) > 0) {
                $actualizadas = explode(";", $actualizadas);
                array_pop($actualizadas);
                foreach ($actualizadas  as $act) {
                    $vals = explode("|", $act);
                    if (count($vals) == 3) {
                        $variable = Variable::where("idVariable", $vals[0])
                            ->update([
                                "variableNombre" => $vals[1],
                                "variableUM" => $vals[2]
                            ]);
                    }
                }
            }

            //Borramos las variables indicadas
            if (strlen($borradas) > 0) {
                $ids = explode("|", $borradas);
                array_pop($ids);
                foreach ($ids as $id) {
                    Variable::where("idVariable", $id)->delete();
                }
            }

            //Procesamos las variables nuevas si las hay
            if (strlen($nuevas) > 0) {

                $varnuevas = explode(";", $nuevas);
                array_pop($varnuevas);
                foreach ($varnuevas as $varnueva) {
                    $vals = explode("|", $varnueva);
                    $variable = new Variable();
                    $variable->variableNombre = $vals[0];
                    $variable->variableUM = $vals[1];
                    $variable->idIndicador = $data->idIndicador;
                    $variable->save();
                }
            }

            //procedemos a realizar la actualización de la alineación
            DB::table("indicadorobjetivos")->where("idIndicador", $data->idIndicador)->delete();
            DB::table("indicadorods")->where("idIndicador", $data->idIndicador)->delete();
            DB::table("indicadorprogramas")->where("idIndicador", $data->idIndicador)->delete();

            //Ahora almacenamos las alineaciones
            $objetivos = explode("|", $data->objetivos);
            $objetivosods = explode("|", $data->objetivosods);
            $programaspresupuestales = explode("|", $data->programaspresupuestales);
            $niveles = explode("|", $data->niveles);

            array_pop($objetivos);
            array_pop($objetivosods);
            array_pop($programaspresupuestales);
            array_pop($niveles);

            $this->saveAlineacion($objetivos, $objetivosods, $programaspresupuestales, $niveles, $data->idIndicador);
            IndicadorSector::where('idIndicador', $data->idIndicador)->delete();

if ($data->filled('idSector')) {
    IndicadorSector::create([
        'idIndicador' => $data->idIndicador,
        'idSector' => $data->idSector,
        'idObjetivo' => $data->idObjetivo,
        'idEstrategia' => $data->idEstrategia,
    ]);
}


            DB::commit();
            return response()->json([
                'success' => 'ok',
                'message' => 'El Indicador ha sido actualizado de manera Satisfactoria',
                'indicador' => $data->indicadorNombre
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
        }
        //Actualizamos todos los datos del indicador
    }

    public function delete(Request $data): JsonResponse
    {
        try {
            Indicador::where("idIndicador", $data->idIndicador)->update([
                "status" => 0
            ]);
            return response()->json([
                'success' => 'ok',
                'message' => 'El Indicador ha sido dado de baja de manera Satisfactoria!'
            ], 200);
        } catch (Exception $ex) {
            return response()->json([
                'success' => 'error',
                'message' => 'Ocurrió un error al intentar dar de baja, intente más tarde!'
            ], 200);
        }
    }

    public function download($indicador)
    {
        $html = "<h1 style='color:red;'>Hola mundo!</h1>";

        ReportePDF::setHeaderCallback(function ($pdf) {
            $image_file = public_path("images/siibien_colores.png");
            $pdf->Image($image_file, 150, 5, 50, '', 'PNG', '', 'T', false, 100, '', false, false, 0, false, false, false);
            $image_file = public_path("images/logo_finanzas.png");
            $pdf->Image($image_file, 10, 5, 50, '', 'PNG', '', 'T', false, 100, '', false, false, 0, false, false, false);
            $pdf->SetFont('helvetica', 'B', 11);
            //$pdf->SetFont('montserratsemib');

            $pdf->SetY(10);
            $pdf->SetX(65);
            $pdf->SetFontSize(16);
            $pdf->Cell(0, 20, 'Ficha Técnica del Indicador', 0, false, 'L', 0, '', 0, false, 'M', 'M');
            $pdf->SetY(18);
            $pdf->SetX(65);
            $pdf->SetFontSize(11);
            $pdf->Cell(10, 15, 'Reporte de Desempeño y Seguimiento', 0, false, 'L', 0, '', 0, false, 'M', 'M');
            $pdf->SetDrawColor(104, 27, 46);
            //$pdf->Line(15, 23, 200, 23);
            $pdf->SetLineStyle(array('width' => 1, 'cap' => 'butt', 'join' => 'miter','dash'=>0,'color'=>array(104,27,46)));
            $pdf->Line(65, 15, 120,15);
        });


        ReportePDF::setFooterCallback(function ($pdf) {
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->SetX(0);
            $pdf->SetY(-15);
            $pdf->SetFontSize(8);
            $pdf->Cell(10, 15, 'Fecha de Impresión: '.date("Y-m-d H:i:s"), 0, false, 'L', 0, '', 0, false, 'M', 'M');
            $pdf->SetY(-15);
            $pdf->Cell(200, 15, 'Página: '.$pdf->getAliasNumPage()."/".$pdf->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'M', 'M');
        });

       // ReportePDF::SetHeaderData("images/header_line.png", 25, "Reporte de Indicadores Estratégicos", "NINGUNO");
        ReportePDF::SetTitle('Reporte de Indicador - Instancia Técnica de Evaluación');
        ReportePDF::SetMargins(10, 23, 10);
        //ReportePDF::SetHeaderMargin(25);
        ReportePDF::AddPage();
        ReportePDF::SetFontSize(10);


        //Información del Indicador
        $infoIndicador = Indicador::select("*", "dependenciaNombre")
            ->join("dependencia", "dependencia.idDependencia", "=", "indicador.idDependencia")
            ->where("idIndicador", $indicador)->first();

        //Variables del Indicador
        $variables = Variable::select("*")->where("idIndicador", $indicador)->get();

        //Objetivos alineados PED
        $objetivos = IndicadorObjetivos::select("*", "objetivoPEDClave", "objetivoPEDDescripcion")
            ->join("objetivoped", "objetivoped.idObjetivoPED", "=", "indicadorobjetivos.idObjetivoPED")
            ->join("temaped", "temaped.idTemaPED", "=", "objetivoped.idTemaPED")
            ->join("sector","sector.idSector","=","temaped.idSector")
            ->join("ejeped", "ejeped.idEjePED", "=", "temaped.idEjePED")

            ->where("indicadorobjetivos.idIndicador", $indicador)->get();


        //ObjetivosODS alineados al PED
        $objetivosods = IndicadorOds::select("*", "clave", "descripcion")
            ->join("objetivos_ods", "objetivos_ods.id", "=", "indicadorods.idODS")
            ->where("indicadorods.idIndicador", $indicador)->get();

        //Programas Presupuestales
        $programas = IndicadorProgramas::select(
            'indicadorprogramas.*',
            'pp.clavePrograma',
            'pp.descripcionPrograma',
            'pp.anio'
        )
        ->join('programa_presupuestario as pp', 'pp.idPrograma', '=', 'indicadorprogramas.idPrograma')
        ->where('indicadorprogramas.idIndicador',$indicador)
        ->orderBy('pp.anio')
        ->orderBy('pp.clavePrograma')
        ->get();

        $programasPorAnio = $programas->groupBy('anio');

        //Titular
        $titular = DB::table("titulares")->where("idDependencia",$infoIndicador->idDependencia)->first();

        //Enlace
        $enlace = DB::table("enlacedependencia")->where("idDependencia",$infoIndicador->idDependencia)
                ->where("status","=",1)
                ->where("tipoEnlace","=","directivo")
                ->first();;

        //Valores Programados del Indicador
        $valoresProgramados = ValoresProgramadosIndicador::where("idIndicador",$indicador)->get();
        $valoresHistoricos = ValoresHistoricosIndicador::where("idIndicador",$indicador)->get();
        $mediosIndicador = MediosIndicador::select("descripcion","archivo","valoresindicador.*","filename")
                            ->join("valoresindicador","valoresindicador.idValoresIndicador","=","mediosindicador.idValoresIndicador")
                            ->join("indicador","indicador.idIndicador","=","valoresindicador.idIndicador")
                            ->where('indicador.idIndicador',$indicador)->get();
        $historicosi = [
            "2017" => '',
            "2018" => '',
            "2019" => '',
            "2020" => '',
            "2021" => '',
            "2022" => ''
        ];

        $vals = [
            "2022"=>'',
            "2023"=>'',
            "2024"=>'',
            "2025"=>'',
            "2026"=>'',
            "2027"=>'',
            "2028"=>'',
        ];
        $valsr = [
            "2022"=>'',
            "2023"=>'',
            "2024"=>'',
            "2025"=>'',
            "2026"=>'',
            "2027"=>'',
            "2028"=>'',
        ];

        foreach($valoresProgramados as $valor){
            $vals[$valor->valoresCicloMedicion] = number_format($valor->valoresProgramado,2);
            $valsr[$valor->valoresCicloMedicion] = number_format($valor->valoresReal,2);
        }

        foreach($valoresHistoricos as $valhist){
            $historicosi[$valhist->valoresCicloMedicion] = number_format($valhist->valoresValor,2);
        }
        //validacion crema
        $crema = IndicadorCrema::where('idIndicador', $indicador)->first();
        
        //Comentarios
        $comentariosCrema = [];
        if($crema) {
            $comentarios = DB::table('crema_comentarios')
            ->where('idValidacionCrema', $crema->idValidacionCrema)
            ->get()
            ->groupBy('criterio');
            
            $comentariosCrema = $comentarios->map(function ($items){
                return $items->pluck('comentario')->implode(',');
            })->toArray();
        }

        //Alineacion con Sectores
        $sectores = IndicadorSector::where("idIndicador",$indicador)
                ->join("sectores","sectores.idSector","=","indicadorsector.idSector")->get();

        $html = \View::make("indicador.download3")->with("indicador", $infoIndicador)->with("variables", $variables)->with("objetivos", $objetivos)->with("objetivosods", $objetivosods)->with("programas", $programas)->with("titular",$titular)->with("enlace",$enlace)->with('valoresprogramados',$vals)->with('valoresreales',$valsr)->with('valoreshistoricos',$historicosi)->with('mediosindicador',$mediosIndicador)->with("sectores",$sectores)->with("programasPorAnio", $programasPorAnio)->with("crema",$crema)->with("comentariosCrema",$comentariosCrema);
        //die($html);

        ReportePDF::writeHTML($html, true, false, true, false, '');

        ReportePDF::Output(public_path('indicador' . $indicador . '.pdf'), 'I');
        //return response()->download(public_path('indicador'.$indicador.'.pdf'));

    }

    public function programacion(): View
    {
        if (session("idDependencia") == "0")
            $indicadores = Indicador::where("status", 1)->get()->sortBy("idIndicador");
        else
            $indicadores = Indicador::where("status", 1)->where("idDependencia", session("idDependencia"))->get()->sortBy("idIndicador");
        return view("indicador.programacion", compact('indicadores'));
    }

    public function addhistorico(Request $req): JsonResponse
    {

        DB::beginTransaction();
        try {
            if ($req->idValoresIndicador == "") {
                $valorHistoricoIndicador = new ValoresHistoricosIndicador();
                $valorHistoricoIndicador->valoresAnioMedicion =  $req->valoresAnioMedicion;
                $valorHistoricoIndicador->valoresCicloMedicion = $req->valoresCicloMedicion;
                $valorHistoricoIndicador->valoresValor = $req->valoresValor;
                $valorHistoricoIndicador->valoresEstatus = $req->valoresEstatus;
                $valorHistoricoIndicador->valoresObservaciones = $req->valoresObservaciones;
                $valorHistoricoIndicador->idIndicador = $req->idIndicador;
                $valorHistoricoIndicador->save();
            } else {
                $valorHistoricoIndicador = ValoresHistoricosIndicador::where("idValoresIndicador", $req->idValoresIndicador)
                    ->update([
                        'valoresAnioMedicion' => $req->valoresAnioMedicion,
                        'valoresCicloMedicion' => $req->valoresCicloMedicion,
                        'valoresValor' => $req->valoresValor,
                        'valoresEstatus' => $req->valoresEstatus,
                        'valoresObservaciones' => $req->valoresObservaciones,
                    ]);
            }


            DB::commit();

            if ($req->idValoresIndicador == "")
                return response()->json([
                    'success' => 'ok',
                    'message' => 'El valor ha sido almacenado satisfactoriamente! ',
                    'id' => $valorHistoricoIndicador->id
                ]);
            else
                return response()->json([
                    'success' => 'ok',
                    'message' => 'El valor ha sido actualizado de manera satisfactoria! ',
                    'id' => $req->idValoresIndicador
                ]);
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json([
                'success' => 'error',
                'message' => 'Ocurrió un error al Almacenar el valor, intente más tarde! ' . $ex
            ]);
        }
    }

    public function gethistoricos(Request $req)
    {
        $historicos = ValoresHistoricosIndicador::where("idIndicador", $req->idIndicador)->orderBy("valoresAnioMedicion","ASC")->get();
        return response()->json([
            'success' => 'ok',
            'message' => 'Historicos del Indicador',
            'historicos' => $historicos
        ], 200);
    }

    public function deletevalorhistorico(Request $req)
    {

        try {
            DB::beginTransaction();
            ValoresHistoricosIndicador::where("idValoresIndicador", $req->idValoresIndicador)->delete();
            DB::commit();
            return response()->json([
                'success' => 'ok',
                'message' => 'El valor Histórico ha sido dado de baja Satisfactoriamente!'
            ]);
        } catch (Exception $ex) {
            DB::rollBack();
        }
    }

    public function addprogramado(Request $req): JsonResponse
    {

        DB::beginTransaction();
        try {
            if ($req->idValoresIndicadorProgramado == "") {
                $valorProgramadoIndicador = new ValoresProgramadosIndicador();
                $valorProgramadoIndicador->valoresAnioMedicion =  $req->valoresAnioMedicionProgramado;
                $valorProgramadoIndicador->valoresCicloMedicion = $req->valoresCicloMedicionProgramado;
                $valorProgramadoIndicador->valoresProgramado = $req->valoresValorProgramado;
                $valorProgramadoIndicador->valoresEstatusP = $req->valoresEstatusProgramado;
                $valorProgramadoIndicador->valoresObservaciones = $req->valoresObservacionesProgramado;
                $valorProgramadoIndicador->idIndicador = $req->idIndicador;
                $valorProgramadoIndicador->save();
            } else {
                $valorProgramadoIndicador = ValoresProgramadosIndicador::where("idValoresIndicador", $req->idValoresIndicadorProgramado)
                    ->update([
                        'valoresAnioMedicion' => $req->valoresAnioMedicionProgramado,
                        'valoresCicloMedicion' => $req->valoresCicloMedicionProgramado,
                        'valoresProgramado' => $req->valoresValorProgramado,
                        'valoresEstatusP' => $req->valoresEstatusProgramado,
                        'valoresObservaciones' => $req->valoresObservacionesProgramado,
                    ]);
            }


            DB::commit();

            if ($req->idValoresIndicadorProgramado == "")
                return response()->json([
                    'success' => 'ok',
                    'message' => 'El valor programado ha sido almacenado satisfactoriamente! ',
                    'id' => $valorProgramadoIndicador->id
                ]);
            else
                return response()->json([
                    'success' => 'ok',
                    'message' => 'El valor programado ha sido actualizado de manera satisfactoria! ',
                    'id' => $req->idValoresIndicadorProgramado
                ]);
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json([
                'success' => 'error',
                'message' => 'Ocurrió un error al Almacenar el valor programado, intente más tarde! ' . $ex
            ]);
        }
    }
    public function getprogramados(Request $req)
    {
        $programados = ValoresProgramadosIndicador::where("idIndicador", $req->idIndicador)->orderBy("valoresAnioMedicion","ASC")->get();
        return response()->json([
            'success' => 'ok',
            'message' => 'Historicos del Indicador',
            'programados' => $programados
        ], 200);
    }

    public function deletevalorprogramado(Request $req)
    {

        try {
            DB::beginTransaction();
            ValoresProgramadosIndicador::where("idValoresIndicador", $req->idValoresIndicador)->delete();
            DB::commit();
            return response()->json([
                'success' => 'ok',
                'message' => 'El valor Programado ha sido dado de baja Satisfactoriamente!'
            ]);
        } catch (Exception $ex) {
            DB::rollBack();
        }
    }

    public function getvariables(Request $req)
    {
        $variables = Variable::where("idIndicador", $req->idIndicador)->get();
        return response()->json([
            'success' => 'ok',
            'variables' => $variables
        ], 200);
    }

    public function monitoreo(Request $req): View
    {
        if (session("idDependencia") == "0")
            $indicadores = Indicador::where("status", 1)->get()->sortBy("idIndicador");
        else
            $indicadores = Indicador::where("status", 1)->where("idDependencia", session("idDependencia"))->get()->sortBy("idIndicador");
        return view("indicador.monitoreo", compact('indicadores'));
    }

    public function updatemeta(Request $req)
    {
        DB::beginTransaction();
        try {

            $valorProgramadoIndicador = ValoresProgramadosIndicador::where("idValoresIndicador", $req->idValoresIndicadorProgramado)
                ->update([
                    'valoresReal' => $req->valoresValorMeta,
                    'valoresEstatus' => $req->valoresEstatusMeta,
                    'valoresObservaciones' => $req->valoresObservacionesProgramado,
                ]);

            //Actualizamos las observaciones de los medios cargados
            if (strlen($req->medios) > 0) {
                $medios = explode("|", $req->medios);
                $descripciones = explode("|", $req->descripciones);
                array_pop($medios);
                array_pop($descripciones);
                for ($x = 0; $x < count($medios); $x++) {
                    MediosIndicador::where("idMedio", $medios[$x])->update([
                        "descripcion" => $descripciones[$x]
                    ]);
                }
            }
            DB::commit();

            return response()->json([
                'success' => 'ok',
                'message' => 'la Meta ha sido actualizada de manera satisfactoria! ',
                'id' => $req->idValoresIndicadorProgramado
            ]);
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json([
                'success' => 'error',
                'message' => 'Ocurrió un error al Almacenar la Meta, intente más tarde! ' . $ex
            ]);
        }
    }

    private function saveAlineacion($objetivos, $objetivosods, $programaspresupuestales,$niveles, $idIndicador)
    {

        try {
            if (count($objetivos) > 0) {
                for ($x = 0; $x < count($objetivos); $x++) {
                    $objetivosped = new IndicadorObjetivos();
                    $objetivosped->idIndicador = $idIndicador;
                    $objetivosped->idObjetivoPED = $objetivos[$x];
                    $objetivosped->save();
                }
            }

            if (count($objetivosods) > 0) {
                for ($x = 0; $x < count($objetivosods); $x++) {
                    $objetivosodsm = new IndicadorOds();
                    $objetivosodsm->idIndicador = $idIndicador;
                    $objetivosodsm->idODS = $objetivosods[$x];
                    $objetivosodsm->save();
                }
            }
            if (count($programaspresupuestales) > 0) {
                for ($x = 0; $x < count($programaspresupuestales); $x++) {
                    $programaspresupuestalesm = new IndicadorProgramas();
                    $programaspresupuestalesm->idIndicador = $idIndicador;
                    $programaspresupuestalesm->idPrograma = $programaspresupuestales[$x];
                    $programaspresupuestalesm->nivel = $niveles[$x];
                    $programaspresupuestalesm->save();
                }
            }
            return true;
        } catch (Exception $ex) {
            return false;
        }
    }

    private function getIndicadores(){
        if (session("idDependencia") == "0" || Auth::user()->hasRole("consulta"))
            $Indicadores = Indicador::select("indicador.*", "dependencia.dependenciaSiglas","ejeped.idEjePED")
                ->join("dependencia", "dependencia.idDependencia", "=", "indicador.idDependencia")
                ->join("indicadorobjetivos", "indicadorobjetivos.idIndicador", "=", "indicador.idIndicador")
                ->join("objetivoped", "objetivoped.idObjetivoPED", "=", "indicadorobjetivos.idObjetivoPED")
                ->join("temaped", "objetivoped.idTemaPED", "=", "temaped.idTemaPED")
                ->join("ejeped", "ejeped.idEjePED", "=", "temaped.idEjePED")
                ->get()->sortBy("idIndicador");
        else
            $Indicadores = Indicador::select("indicador.*", "dependencia.dependenciaSiglas","ejeped.idEjePED")
                ->join("dependencia", "dependencia.idDependencia", "=", "indicador.idDependencia")
                ->join("indicadorobjetivos", "indicadorobjetivos.idIndicador", "=", "indicador.idIndicador")
                ->join("objetivoped", "objetivoped.idObjetivoPED", "=", "indicadorobjetivos.idObjetivoPED")
                ->join("temaped", "objetivoped.idTemaPED", "=", "temaped.idTemaPED")
                ->join("ejeped", "ejeped.idEjePED", "=", "temaped.idEjePED")
                ->where("indicador.idDependencia", session("idDependencia"))->get()->sortBy("idIndicador");
        return $Indicadores;
    }

    public function reportes(){
        $Indicadores = $this->getIndicadores();
        $dependenciasIds = Indicador::select("idDependencia")->distinct()->get();
        $dependencias = Dependencia::select("idDependencia","dependenciaNombre","dependenciaSiglas")->whereIn("idDependencia",$dependenciasIds)->get();
        return view("indicador.reportes")->with('indicadores', $Indicadores)->with('dependencias',$dependencias);

    }

    public function adminindicadores(){
        $Indicadores = $this->getIndicadores();
        $Dependencias = Dependencia::all();
        return view("super.indicadores")->with('indicadores', $Indicadores)->with("dependencias",$Dependencias);
    }

    public function updateresponsable(Request $request){
        try{
            Indicador::where("idIndicador",$request->indicador)->update([
                'idDependencia' => $request->responsable
            ]);

            $siglas = Dependencia::select("dependenciaSiglas")->where("idDependencia",$request->responsable)->first();
            return response()->json([
                'success' => 'ok',
                'message' => 'Reasignacion exitosa!',
                'siglas' => $siglas->dependenciaSiglas
            ]);
        }catch(Exception $ex){
            return response()->json([
                'success' => 'error',
                'message' => 'Error!'.$ex
            ]);
        }
    }

    public function adminedit($id)
    {
        $indicador = Indicador::select("*")->where("idIndicador", $id)->first();
        $objetivos = ObjetivoPED::all();
        $objetivosods = ObjetivoODS::all();
        $ejes = EjePED::all();
            // Años distintos
    $anios = ProgramaPresupuestario::query()
        ->select('anio')
        ->whereNotNull('anio')
        ->distinct()
        ->orderBy('anio', 'asc')
        ->pluck('anio');

    $anioSeleccionado = $anios->first();

    // Programas del año seleccionado
    $programaspresupuestales = ProgramaPresupuestario::query()
        ->where('anio', $anioSeleccionado)
        ->orderBy('clavePrograma')   
        ->get();

        $variables = Variable::all()->where("idIndicador", $id);
        $sectores = Sector::all();
        $sectorAsignado = IndicadorSector::select('idSector','idObjetivo','idEstrategia')->where('idIndicador', $id)->first();
        $objetivosSector   = ObjetivoSector::all();
        $estrategiasSector = EstrategiaSector::all();

        
        $indicadorObjetivos = DB::table("indicadorobjetivos")->where("idIndicador", $id)
                                ->join("objetivoped","objetivoped.idObjetivoPED","=","indicadorobjetivos.idObjetivoPED")
                                ->join("temaped","temaped.idTemaPED","=","objetivoped.idTemaPED")
                                ->get();
        $indicadorObjetivosods = DB::table("indicadorods")->where("idIndicador", $id)->get();
        $indicadorProgramas = DB::table("indicadorprogramas")->where("idIndicador", $id)->get();
        if($indicador->status==1){
            return view("super.indicadoredit", compact('objetivos', 'objetivosods', 'programaspresupuestales', 'indicador', 'variables', 'indicadorObjetivos', 'indicadorObjetivosods', 'indicadorProgramas','ejes','sectores','sectorAsignado','objetivosSector','estrategiasSector','anios','anioSeleccionado'));
        }else{
            return redirect()->route("admin.indicadores");
        }


    }

    public function updateeditar(Request $request){
        try{
            Indicador::where("idIndicador",$request->indicador)->update([
                "en_revision" => $request->editar
            ]);
            return response()->json([
                'success' => 'ok',
                'message' => 'Editar Actualizado Satisfactoriamente!'
            ]);
        }catch(Exception $ex){
            return response()->json([
                'success' => 'error',
                'message' => 'Ocurrió un error al actualizar el campo editar!'.$ex
            ]);
        }
    }

    public function getstatus(Request $request){
        try{
            $info = Indicador::select("en_revision","prog","moni","crema")->where("idIndicador",$request->indicador)->first();
            return response()->json([
                'success' => 'ok',
                'status' => $info->en_revision,
                'programacion' => $info->prog,
                'monitoreo' => $info->moni,
                'crema'        => (int)$info->crema,

            ]);
        }catch(Exception $ex){
            return response()->json([
                'success' => 'error',
                'status' => '1'
            ]);
        }
    }

    public function admindownloadxlsx(){
        return Excel::download(new IndicadoresExport, 'indicadoresSIIBien'.date('YmdHis').'.xlsx');
    }

    public function admindownloadxlsxdetallado(){

        return Excel::download(new IndicadoresDetallesExport, 'indicadoresSIIBien-Detallado'.date('YmdHis').'.xlsx');
        //dd($indicadores);
    }

    public function updatedata(Request $request){
        //actualizamos el campo del indicador
        try{
            Indicador::where("idIndicador",$request->indicador)->update([
                $request->campo => $request->valor
            ]);
            return response()->json([
                'success' => 'ok',
                'valor' => $request->valor
            ]);

        }catch(Exption $ex){
            $valor = Indicador::where("idIndicador",$request->idIndicador)->select(''+$request->campo+'')->first();
            return response()->json([
                'success' => 'error',
                'valor' => $valor->$request->campo
            ]);

        }
    }

    public function admindownload($indicador)
    {
        $html = "<h1 style='color:red;'>Hola mundo!</h1>";

        ReportePDF::setHeaderCallback(function ($pdf) {
            $image_file = public_path("images/siibien_colores.png");
            $pdf->Image($image_file, 150, 5, 50, '', 'PNG', '', 'T', false, 100, '', false, false, 0, false, false, false);
            $image_file = public_path("images/logo_finanzas.png");
            $pdf->Image($image_file, 10, 5, 50, '', 'PNG', '', 'T', false, 100, '', false, false, 0, false, false, false);
            $pdf->SetFont('helvetica', 'B', 11);
            //$pdf->SetFont('montserratsemib');

            $pdf->SetY(10);
            $pdf->SetX(65);
            $pdf->SetFontSize(16);
            $pdf->Cell(0, 20, 'Ficha Técnica del Indicador', 0, false, 'L', 0, '', 0, false, 'M', 'M');
            $pdf->SetY(18);
            $pdf->SetX(65);
            $pdf->SetFontSize(11);
            $pdf->Cell(10, 15, 'Reporte de Desempeño y Seguimiento', 0, false, 'L', 0, '', 0, false, 'M', 'M');
            $pdf->SetDrawColor(104, 27, 46);
            //$pdf->Line(15, 23, 200, 23);
            $pdf->SetLineStyle(array('width' => 1, 'cap' => 'butt', 'join' => 'miter','dash'=>0,'color'=>array(104,27,46)));
            $pdf->Line(65, 15, 120,15);
        });


        ReportePDF::setFooterCallback(function ($pdf) {
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->SetX(0);
            $pdf->SetY(-15);
            $pdf->SetFontSize(8);
            $pdf->Cell(10, 15, 'Fecha de Impresión: '.date("Y-m-d H:i:s"), 0, false, 'L', 0, '', 0, false, 'M', 'M');
            $pdf->SetY(-15);
            $pdf->Cell(200, 15, 'Página: '.$pdf->getAliasNumPage()."/".$pdf->getAliasNbPages(), 0, false, 'R', 0, '', 0, false, 'M', 'M');
        });

       // ReportePDF::SetHeaderData("images/header_line.png", 25, "Reporte de Indicadores Estratégicos", "NINGUNO");
        ReportePDF::SetTitle('Reporte de Indicador - Instancia Técnica de Evaluación');
        ReportePDF::SetMargins(10, 23, 10);
        //ReportePDF::SetHeaderMargin(25);
        ReportePDF::AddPage();
        ReportePDF::SetFontSize(10);


        //Información del Indicador
        $infoIndicador = Indicador::select("*", "dependenciaNombre")
            ->join("dependencia", "dependencia.idDependencia", "=", "indicador.idDependencia")
            ->where("idIndicador", $indicador)->first();

        //Variables del Indicador
        $variables = Variable::select("*")->where("idIndicador", $indicador)->get();

        //Objetivos alineados PED
        $objetivos = IndicadorObjetivos::select("*", "objetivoPEDClave", "objetivoPEDDescripcion")
            ->join("objetivoped", "objetivoped.idObjetivoPED", "=", "indicadorobjetivos.idObjetivoPED")
            ->join("temaped", "temaped.idTemaPED", "=", "objetivoped.idTemaPED")
            ->join("sector","sector.idSector","=","temaped.idSector")
            ->join("ejeped", "ejeped.idEjePED", "=", "temaped.idEjePED")

            ->where("indicadorobjetivos.idIndicador", $indicador)->get();


        //ObjetivosODS alineados al PED
        $objetivosods = IndicadorOds::select("*", "clave", "descripcion")
            ->join("objetivos_ods", "objetivos_ods.id", "=", "indicadorods.idODS")
            ->where("indicadorods.idIndicador", $indicador)->get();

        //Programas Presupuestales
        $programas = IndicadorProgramas::select(
            'indicadorprogramas.*',
            'pp.clavePrograma',
            'pp.descripcionPrograma',
            'pp.anio'
        )
            ->join('programa_presupuestario as pp', 'pp.idPrograma', '=', 'indicadorprogramas.idPrograma')
            ->where('indicadorprogramas.idIndicador', $indicador)
            ->orderBy('pp.anio')
            ->orderBy('pp.clavePrograma')
            ->get();

        $programasPorAnio = $programas->groupBy('anio');

        //Titular
        $titular = DB::table("titulares")->where("idDependencia",$infoIndicador->idDependencia)->first();

        //Enlace
        $enlace = DB::table("enlacedependencia")->where("idDependencia",$infoIndicador->idDependencia)
                ->where("status","=",1)
                ->where("tipoEnlace","=","directivo")
                ->first();

        //Valores Programados del Indicador
        $valoresProgramados = ValoresProgramadosIndicador::where("idIndicador",$indicador)->get();
        $valoresHistoricos = ValoresHistoricosIndicador::where("idIndicador",$indicador)->get();
        $mediosIndicador = MediosIndicador::select("descripcion","archivo","valoresindicador.*","filename")
                            ->join("valoresindicador","valoresindicador.idValoresIndicador","=","mediosindicador.idValoresIndicador")
                            ->join("indicador","indicador.idIndicador","=","valoresindicador.idIndicador")
                            ->where('indicador.idIndicador',$indicador)->get();
        $historicosi = [
            "2017" => '',
            "2018" => '',
            "2019" => '',
            "2020" => '',
            "2021" => '',
            "2022" => ''
        ];

        $vals = [
            "2022"=>'',
            "2023"=>'',
            "2024"=>'',
            "2025"=>'',
            "2026"=>'',
            "2027"=>'',
            "2028"=>'',
        ];
        $valsr = [
            "2022"=>'',
            "2023"=>'',
            "2024"=>'',
            "2025"=>'',
            "2026"=>'',
            "2027"=>'',
            "2028"=>'',
        ];

        foreach($valoresProgramados as $valor){
            $vals[$valor->valoresCicloMedicion] = number_format($valor->valoresProgramado,2);
            $valsr[$valor->valoresCicloMedicion] = number_format($valor->valoresReal,2);
        }

        foreach($valoresHistoricos as $valhist){
            $historicosi[$valhist->valoresCicloMedicion] = number_format($valhist->valoresValor,2);
        }

        //validacion crema
        $crema = IndicadorCrema::where('idIndicador', $indicador)->first();
        
        //Justificiacon 
        $comentariosCrema = [];
        if($crema) {
            $comentarios = DB::table('crema_comentarios')
            ->where('idValidacionCrema', $crema->idValidacionCrema)
            ->get()
            ->groupBy('criterio');
            
            $comentariosCrema = $comentarios->map(function ($items){
                return $items->pluck('comentario')->implode(',');
            })->toArray();
        }
        //Alineacion con Sectores
        $sectores = IndicadorSector::where("idIndicador",$indicador)
                ->join("sectores","sectores.idSector","=","indicadorsector.idSector")->get();

        $html = \View::make("indicador.download3")->with("indicador", $infoIndicador)->with("variables", $variables)->with("objetivos", $objetivos)->with("objetivosods", $objetivosods)->with("programas", $programas)->with("titular",$titular)->with("enlace",$enlace)->with('valoresprogramados',$vals)->with('valoresreales',$valsr)->with('valoreshistoricos',$historicosi)->with('mediosindicador',$mediosIndicador)->with("sectores",$sectores)->with("programasPorAnio", $programasPorAnio)->with("crema",$crema)->with("comentariosCrema",$comentariosCrema);
        //die($html);

        ReportePDF::writeHTML($html, true, false, true, false, '');

        ReportePDF::Output(public_path('indicador' . $indicador . '.pdf'), 'I');
        //return response()->download(public_path('indicador'.$indicador.'.pdf'));

    }

    public function getindicadoresbyfiltros(Request $request){
        $dependencia = $request->dependencia;
        $eje = $request->eje;
        $sector = $request->sector;

        $Indicadores = Indicador::select("indicador.*", "dependencia.dependenciaSiglas","ejeped.idEjePED")
                ->join("dependencia", "dependencia.idDependencia", "=", "indicador.idDependencia")
                ->join("indicadorobjetivos", "indicadorobjetivos.idIndicador", "=", "indicador.idIndicador")
                ->join("objetivoped", "objetivoped.idObjetivoPED", "=", "indicadorobjetivos.idObjetivoPED")
                ->join("temaped", "objetivoped.idTemaPED", "=", "temaped.idTemaPED")
                ->join("ejeped", "ejeped.idEjePED", "=", "temaped.idEjePED")
                ->where("indicador.status", 1);

        if($eje!="0"){
            $Indicadores->where("ejeped.idEjePED",$eje);
        }

        if($dependencia!="0"){
            $Indicadores->where("indicador.idDependencia",$dependencia);
        }

        $indicadoresList = $Indicadores->get()->sortBy("idIndicador");
        return view("super.indicadoresfiltering",[
            "indicadores" => $indicadoresList

        ]);
    }

    public function updatepermission(Request $request){
        $campo = $request->campo;
        $indicador = $request->indicador;
        $valor = $request->valor;


        try{
            Indicador::where("idIndicador",$indicador)->update([
                $campo => $valor
            ]);
            return response()->json([
                'success' => 'ok',
            ]);
        }catch(Exception $ex){
            return response()->json([
                'success' => 'error',
                'error' => $ex
            ]);
        }
        dd($request);
    }

    public function guardarIndicadorCrema(Request $request, $idIndicador)
    {
        try {
            $request->validate([
                'crema.claro' => 'sometimes|boolean',
                'crema.relevante' => 'sometimes|boolean',
                'crema.economico' => 'sometimes|boolean',
                'crema.monitoreable' => 'sometimes|boolean',
                'crema.adecuado' => 'sometimes|boolean',
                'crema.aporteMarginal' => 'sometimes|boolean',

            ]);

            $valores = [
                'claro' => (int) $request->input('crema.claro', 0),
                'relevante' => (int) $request->input('crema.relevante', 0),
                'economico' => (int) $request->input('crema.economico', 0),
                'monitoreable' => (int) $request->input('crema.monitoreable', 0),
                'adecuado' => (int) $request->input('crema.adecuado', 0),
                'aporteMarginal' => (int) $request->input('crema.aporteMarginal', 0),
            ];

            IndicadorCrema::updateOrCreate(
                ['idIndicador' => (int) $idIndicador],
                $valores
            );

            return response()->json([
                'success' => true,
                'message' => 'Validación  guardada correctamente.',
                'score' => array_sum($valores),
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al guardar la validación.',
                'error' => $e->getMessage(), 
            ], 500);
        }
    }
    public function mostrarIndicadorCrema($idIndicador)
    {
        $validacion = IndicadorCrema::where('idIndicador', $idIndicador)->first();

        if (!$validacion) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'El indicador aún no tiene validación CREMA.',
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'claro' => (int) $validacion->claro,
                'relevante' => (int) $validacion->relevante,
                'economico' => (int) $validacion->economico,
                'monitoreable' => (int) $validacion->monitoreable,
                'adecuado' => (int) $validacion->adecuado,
                'aporteMarginal' => (int) $validacion->aporteMarginal,
            ],
            'message' => 'Validación CREMA encontrada.',
        ]);
    }

    public function guardarComentarioCrema(Request $request, $idIndicador)
    {
        $data = $request->validate([
            'idComentario' => ['nullable', 'integer'],
            'criterio' => ['required', Rule::in(['claro', 'relevante', 'economico', 'monitoreable', 'adecuado', 'aporteMarginal'])],
            'comentario' => ['required', 'string', 'max:1000'],
        ]);

        // Obtener idValidacionCrema a partir del indicador
        $idValidacionCrema = DB::table('indicador_crema')
            ->where('idIndicador', (int) $idIndicador)
            ->value('idValidacionCrema');

        if (!$idValidacionCrema) {
            return response()->json(['message' => 'No existe una validación CREMA para este indicador.'], 422);
        }

        // Si viene idComentario -> actualiza; si no, crea
        if (!empty($data['idComentario'])) {
            $comentario = CremaComentario::updateOrCreate(
                ['idComentario' => (int) $data['idComentario']], // clave de búsqueda
                [
                    'idValidacionCrema' => $idValidacionCrema,
                    'criterio' => $data['criterio'],
                    'comentario' => $data['comentario'],
                ]
            );
            $mensaje = 'Comentario actualizado correctamente.';
        } else {
            $comentario = CremaComentario::create([
                'idValidacionCrema' => $idValidacionCrema,
                'criterio' => $data['criterio'],
                'comentario' => $data['comentario'],
            ]);
            $mensaje = 'Comentario creado correctamente.';
        }

        return response()->json([
            'message' => $mensaje,
            'comentario' => [
                'idComentario' => $comentario->idComentario,
                'criterio' => $comentario->criterio,
                'comentario' => $comentario->comentario,
            ],
        ], 200);
    }

    public function mostrarComentariosCrema(Request $request, $idIndicador)
    {
        $data = $request->validate([
            'criterio' => [
                'required',
                Rule::in(['claro', 'relevante', 'economico', 'monitoreable', 'adecuado', 'aporteMarginal']),
            ],
        ]);

        // Buscar idValidacionCrema para ese indicador
        $idValidacionCrema = DB::table('indicador_crema')
            ->where('idIndicador', (int) $idIndicador)
            ->value('idValidacionCrema');

        if (!$idValidacionCrema) {
            return response()->json([
                'success' => true,
                'comentarios' => [],
                'message' => 'Este indicador aún no tiene validación CREMA asociada.',
            ]);
        }

        // Obtener los comentarios de ese criterio
        $comentarios = DB::table('crema_comentarios')
            ->select('idComentario', 'criterio', 'comentario', 'updated_at')
            ->where('idValidacionCrema', $idValidacionCrema)
            ->where('criterio', $data['criterio'])
            ->orderByDesc('idComentario')
            ->get();

        return response()->json([
            'success' => true,
            'comentarios' => $comentarios,
            'message' => 'Comentarios cargados correctamente.',
        ]);
    }

    public function eliminarComentario(Request $request, $idIndicador, $comentarioId)
    {
        $idValidacionCrema = DB::table('indicador_crema')
            ->where('idIndicador', (int) $idIndicador)
            ->value('idValidacionCrema');

        if (!$idValidacionCrema) {
            return response()->json([
                'message' => 'No existe una validación CREMA para este indicador.'
            ], 404);
        }

        $comentario = CremaComentario::where('idComentario', (int) $comentarioId)
            ->where('idValidacionCrema', (int) $idValidacionCrema)
            ->firstOrFail();

        $comentario->delete();

        return response()->json(['message' => 'Comentario eliminado correctamente.']);
    }


}
