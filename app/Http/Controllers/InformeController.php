<?php

namespace App\Http\Controllers;

use Exception;
use ZipArchive;
use Carbon\Carbon;
use App\Models\TemaPED;
use App\Models\LineaPED;
use App\Models\Dependencia;
use App\Models\ParrafoBase;
use App\Models\InformeMedio;
use Illuminate\Http\Request;
use App\Models\InformeAccion;
use App\Models\InformeParrafo;
use Illuminate\Support\Facades\Auth;
use App\Models\InformeCoordinadorContenido;
use PhpOffice\PhpWord\PhpWord;
use App\Exports\PorLineasExport;
use App\Models\AnexoEstadistico;
use App\Models\EnlaceDependencia;
use App\Models\MatrizCoordinacion;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\Language;
use App\Exports\InformeAccionesExport;
use PhpOffice\PhpWord\Style\Alignment;
use PhpOffice\PhpWord\Writer\Word2007;
use PhpOffice\PhpWord\SimpleType\JcTable;
use App\Exports\CumplimientoInformeExport;
use PhpOffice\PhpWord\SimpleType\DocProtect;
use App\Exports\AccionesTemaDependenciaExport;

class InformeController extends Controller
{
    //
    public function index()
    {
        $temase1 = TemaPED::select("ejeped.ejePEDClave", "temaped.*")->join("ejeped", "ejeped.idEjePED", "=", "temaped.idEjePED")->where("temaped.idEjePED", 1)->get();
        $temase2 = TemaPED::select("ejeped.ejePEDClave", "temaped.*")->join("ejeped", "ejeped.idEjePED", "=", "temaped.idEjePED")->where("temaped.idEjePED", 2)->get();
        $temase3 = TemaPED::select("ejeped.ejePEDClave", "temaped.*")->join("ejeped", "ejeped.idEjePED", "=", "temaped.idEjePED")->where("temaped.idEjePED", 3)->get();
        $temase4 = TemaPED::select("ejeped.ejePEDClave", "temaped.*")->join("ejeped", "ejeped.idEjePED", "=", "temaped.idEjePED")->where("temaped.idEjePED", 4)->get();
        $temase5 = TemaPED::select("ejeped.ejePEDClave", "temaped.*")->join("ejeped", "ejeped.idEjePED", "=", "temaped.idEjePED")->where("temaped.idEjePED", 5)->get();
        $temase6 = TemaPED::select("ejeped.ejePEDClave", "temaped.*")->join("ejeped", "ejeped.idEjePED", "=", "temaped.idEjePED")->where("temaped.idEjePED", 6)->get();
        $temase7 = TemaPED::select("ejeped.ejePEDClave", "temaped.*")->join("ejeped", "ejeped.idEjePED", "=", "temaped.idEjePED")->where("temaped.idEjePED", 7)->get();
        $temase8 = TemaPED::select("ejeped.ejePEDClave", "temaped.*")->join("ejeped", "ejeped.idEjePED", "=", "temaped.idEjePED")->where("temaped.idEjePED", 8)->get();
        $temase9 = TemaPED::select("ejeped.ejePEDClave", "temaped.*")->join("ejeped", "ejeped.idEjePED", "=", "temaped.idEjePED")->where("temaped.idEjePED", 9)->get();
        $dependencias = Dependencia::all();
        return view("informe.cargas")->with("temase1", $temase1)
            ->with("temase2", $temase2)
            ->with("temase3", $temase3)
            ->with("temase4", $temase4)
            ->with("temase5", $temase5)
            ->with("temase6", $temase6)
            ->with("temase7", $temase7)
            ->with("temase8", $temase8)
            ->with("temase9", $temase9)
            ->with("dependencias", $dependencias);
    }

    public function redactar()
    {
        $idDependencia = auth()->user()->enlace->idDependencia;
        $temas = MatrizCoordinacion::where("dependencias_id", $idDependencia)->where("informe", "2")
            ->join("temaped", "temaped.idTemaPED", "matriz_coordinacion.idTemaPED")
            ->join("ejeped", "ejeped.idEjePED", "temaped.idEjePED")
            ->orderBy("temaped.idTemaPED", "ASC")
            ->get();

        return view("informe.redactar")->with("temas", $temas);
    }

    public function acciones(Request $request)
    {
        DB::enableQueryLog();
$tema = MatrizCoordinacion::where('matriz_coordinacion.idTemaPED', $request->tema)
    ->where('matriz_coordinacion.dependencias_id', $request->dependencia)
    ->join('temaped', 'temaped.idTemaPED', '=', 'matriz_coordinacion.idTemaPED')
    ->select('temaped.*', 'matriz_coordinacion.tipo', 'matriz_coordinacion.bloqueado') 
    ->first();
        $dependencia = Dependencia::where("idDependencia", $request->dependencia)->first();
        $lineas = LineaPED::select("idLAPED", "laPEDClave", "laPEDDescripcion")
            ->join("estrategiaped", "estrategiaped.idEstrategiaPED", "=", "lineaaccionped.idEstrategiaPED")
            ->join("objetivoped", "objetivoped.idObjetivoPED", "=", "estrategiaped.idObjetivoPED")
            ->join("temaped", "temaped.idTemaPED", "=", "objetivoped.idTemaPED")
            ->where("temaped.idTemaPED", $request->tema)->get();
        $acciones = InformeAccion::where("informe_acciones.idDependencia", auth()->user()->enlace->idDependencia)
            ->where("idTemaPED", $request->tema)
            ->join("dependencia", "dependencia.idDependencia", "=", "informe_acciones.idDependencia")
            ->where("informe_acciones.status","=",1)
            ->get();

        $cuadrosE = AnexoEstadistico::where("idTemaPED", $request->tema)->get();
        //dd($acciones);

        return view("informe.acciones")->with("tema", $tema)->with("dependencia", $dependencia)->with("lineas", $lineas)->with("acciones", $acciones)->with("cuadros", $cuadrosE);
    }

    public function downloadword(Request $request)
    {

        //obtenemos informacion de la dependencia y del tema enviado por POST
        $dependencia = Dependencia::where("idDependencia", $request->dependencia)->first();
        $tema = TemaPED::where("idTemaPED", $request->tema)->first();
        //Al cambiar el año la introduccion y conclusión que estaran en el word seran de ese año
        $anio = 2025;


        $documento = new PhpWord();
        $propiedades = $documento->getDocInfo();
        $propiedades->setCreator("Instancia Técnica de Evaluación");
        $propiedades->setTitle("Texto");
        $documento->getSettings()->setTrackRevisions(true);
        $documento->getSettings()->setDoNotTrackMoves(true);
        $documento->getSettings()->setDoNotTrackFormatting(true);
        //$documentProtection = $documento->getSettings()->getDocumentProtection();
        //$documentProtection->setEditing(DocProtect::READ_ONLY);
        //$documentProtection->setPassword('myPassword');

        # Agregar texto...
        /*
Todos los textos deben estar dentro de una sección
 */

        //Obtenemos la información de relacion en la matriz de coordinacion
     //   $documento->setDefaultParagraphStyle(array(
      //      "spacing" =>240,
       //     "lineHeight" =>1
       // ));

        $infoCoordinacion = MatrizCoordinacion::where("dependencias_id", $request->dependencia)->where("idTemaPED", $request->tema)->first();


        $imgStyle = array(
            "width" => 100,
            "marginTop" => 5,
            "marginLeft" => 5,
            "wrappingStyle" => 'behind',

        );

        $seccion = $documento->addSection();
        // Add first page header
        $header = $seccion->addHeader();
        $header->allPages();
        $table = $header->addTable();
        $table->addRow();
        $cell = $table->addCell(10000);
        $textrun = $cell->addTextRun();
        $fuenteTitulo = [
            "name" => "Times",
            "size" => 12,
            "color" => "9D2449",
        ];
        $textrun->addText(htmlspecialchars('3er. Informe de Gobierno'), $fuenteTitulo,['align'=>'center']);
        //$cell = $table->addCell(5000)->addImage(public_path("images")."/logo_finanzas.png",$imgStyle);
        $table->addRow();
        $cell = $table->addCell(10000);
        $textrun = $cell->addTextRun();
        $fuenteTitulo = [
            "name" => "Times",
            "size" => 12,
            "color" => "9D2449",
        ];
        $textrun->addText(htmlspecialchars('Dependencia: ' . $dependencia->dependenciaNombre . " (" . $dependencia->dependenciaSiglas . ")"), $fuenteTitulo);
        $table->addRow();
        $cell = $table->addCell(10000);
        $textrun = $cell->addTextRun();
        $textrun->addText(htmlspecialchars('Tema: ' . $tema->temaPEDClave . " " . $tema->temaPEDDescripcion), $fuenteTitulo,);

        //DB::enableQueryLog();
        if(isset($request->sinrol) || $infoCoordinacion->tipo == "P"){
            $lastUpdated    =    InformeParrafo::select("informe_parrafos.updated_at as actualizacion")
                                                                        ->join(
                                                                            'informe_acciones',
                                                                            'informe_acciones.id',
                                                                            '=',
                                                                            'informe_parrafos.informe_acciones_id',
                                                                        )
                                                                            ->where(
                                                                                'informe_acciones.idTemaPED',
                                                                                $tema->idTemaPED,
                                                                            )
                                                                            ->where(
                                                                                'informe_acciones.idDependencia',
                                                                                $dependencia->idDependencia,
                                                                            )
                                                                            ->where("informe_acciones.status","=",1)
                                                                            ->latest("informe_parrafos.updated_at")->first();
        }else{

            $lastUpdated    =    InformeParrafo::select("informe_parrafos.updated_at as actualizacion")
                                                                        ->join(
                                                                            'informe_acciones',
                                                                            'informe_acciones.id',
                                                                            '=',
                                                                            'informe_parrafos.informe_acciones_id',
                                                                        )
                                                                            ->where(
                                                                                'informe_acciones.idTemaPED',
                                                                                $tema->idTemaPED,
                                                                            )                                                                                                                                                  
                                                                            ->where("informe_acciones.status","=",1)
                                                                            ->latest("informe_parrafos.updated_at")->first();
        }
        
        //dd(DB::getQueryLog());


        if($lastUpdated!=null){
            $fuenteFecha = [
                "name" => "Times",
                "size" => 10,
                "color" => "gray",
            ];
            $table->addRow();
            $cell = $table->addCell(10000);
            $textrun = $cell->addTextRun();
            $textrun->addText(htmlspecialchars('Última actualización: '.$lastUpdated->actualizacion), $fuenteFecha,);
        }

        //$table->addCell(4500)->addImage('resources/images/logo_ped.png',array('width' => 80, 'height' => 80, 'align' => 'right'));

        # Simple texto
        /*  $seccion->addText("Hola, esto es algo de texto");
        # Con fuentes personalizadas
        $fuente = [
            "name" => "Arial",
            "size" => 12,
            "color" => "8bc34a",
            "italic" => true,
            "bold" => true,
        ];
        $seccion->addText("Hola, esto es algo de texto", $fuente);
        # Hipervínculo
        $fuenteHipervinculo = [
            "name" => "Arial",
            "size" => 12,
            "color" => "ff0000",
            "italic" => true,
        ];
        $seccion->addLink("https://parzibyte.me/blog", "Mi blog", $fuenteHipervinculo);
*/
        # Títulos. Solo modificando depth (el número)
        $fuenteTitulo = [
            "name" => "Times",
            "size" => 12,
            "color" => "000000",
        ];
        $documento->addTitleStyle(1, $fuenteTitulo);
        //$seccion->addTitle(auth()->user()->enlace->dependencia->dependenciaNombre, 1);

        # Texto bajo el título
        $seccion->addText("",);

        //obtenemos todos los parrafos de la dependencia por tema
        if ($infoCoordinacion->tipo == "P" || isset($request->sinrol)) {
            $parrafos = InformeParrafo::select("informe_parrafos.id as idParrafo", "informe_parrafos.*", "dependencia.*", "informe_acciones.ae_cuadros","informe_acciones.id as idPPA")
                ->join("informe_acciones", "informe_acciones.id", "=", "informe_parrafos.informe_acciones_id")
                ->join("dependencia", "dependencia.idDependencia", "=", "informe_acciones.idDependencia")
                ->where("informe_acciones.idDependencia", $request->dependencia)
                ->where("idTemaPED", $request->tema)
                ->where("informe_parrafos.status", 1)
                ->where("informe_acciones.status","=",1)
                ->orderBy("informe_acciones.id", "ASC")
                ->orderBy("informe_parrafos.orden", "ASC")
                ->get();
        } else {
            $parrafos = InformeParrafo::select("informe_parrafos.id as idParrafo", "informe_parrafos.*", "dependencia.*","informe_acciones.ae_cuadros","informe_acciones.id as idPPA")
                ->join("informe_acciones", "informe_acciones.id", "=", "informe_parrafos.informe_acciones_id")
                ->join("dependencia", "dependencia.idDependencia", "=", "informe_acciones.idDependencia")
                ->where("idTemaPED", $request->tema)
                ->where("informe_parrafos.status", 1)
                ->where("informe_acciones.status","=",1)
                ->orderBy("informe_parrafos.orden_ct", "ASC")
                ->get();
        }
        //dd($parrafos);
        $fuente = [
            "name" => "Times",
            "size" => 12,
            "color" => "000000",
            "italic" => false,
            "bold" => false,
            "lineHeight" =>1.5,
            'spaceAfter' => \PhpOffice\PhpWord\Shared\Converter::pointToTwip(.6),
            'spaceBefore' => \PhpOffice\PhpWord\Shared\Converter::pointToTwip(.6),
        ];

        $fuente_c = [
            "name" => "Times",
            "size" => 12,
            "color" => "000000",
            "italic" => true,
            "bold" => true,
        ];



        $pJustify = [
            'align' => 'both', 'spaceBefore' => 0, 'spaceAfter' => 0, 'spacing' => 0
        ];

        //dd($parrafos);
        //Agregamos la introduccion
        $introduccion = InformeCoordinadorContenido::where('idDependencia', $request->dependencia)
            ->where('idTemaPED', $request->tema)
            ->where('anio', $anio)
            ->where('seccion', 'introduccion')
            ->orderBy('orden')
            ->pluck('parrafo');

        $contador = 1;

        foreach ($introduccion as $intro) {
            $textrun = $seccion->addTextRun([
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
                'lineHeight' => 1.5,
                'spaceBefore' => \PhpOffice\PhpWord\Shared\Converter::pointToTwip(.6),
                'spaceAfter' => \PhpOffice\PhpWord\Shared\Converter::pointToTwip(.6),
            ]);

            $textrun->addText('[', $fuente);
            $textrun->addText($contador . ' ', $fuente);
            $textrun->addText('Introducción', [
                'italic' => true,
                'name' => 'Times',
                'size' => $fuente['size'] - 3
            ]);
            $textrun->addText('] ', $fuente);
            $textrun->addText($intro, $fuente);

            $contador++;
        }

        $seccion->addTextBreak(1); // 1 línea de espacio 

        if ($parrafos->count() > 0) {
            foreach ($parrafos as $parrafo) {
                //$seccion->addText($parrafo->resultado.'<w:rPr><w:b w:val="true"/></w:rPr> ('.$parrafo->dependenciaSiglas.')'."<w:br/>",$fuente,$pJustify);


                //Analizamos si tiene complementos asociados
                $complementos = InformeMedio::where("idParrafo", $parrafo->idParrafo)->get();
                $complementos_s = "";
                if ($complementos->count() > 0) {
                    foreach ($complementos as $comple) {
                        $complementos_s .= "[" . $comple->nombre . "], ";
                    }
                }

                //$seccion->addText($parrafo->resultado.'<w:rPr><w:b w:val="true"/></w:rPr>'.$complementos_s."<w:br/>",$fuente,$pJustify);

                //Verificamos si el párrafo es un párrafo con Bullets
                $bullets = explode("**",$parrafo->resultado);


                if ($infoCoordinacion->tipo == "CT" && !isset($request->sinrol)) {
                    if(count($bullets)>0){
                        $cont=0;
                        foreach($bullets as $bullet){
                            if($cont==0)
                                $seccion->addText("[PPA:".$parrafo->idPPA."]"."[".$parrafo->idParrafo."]".$bullet . '<w:rPr><w:b w:val="true"/></w:rPr> (' . $parrafo->dependenciaSiglas . ')', $fuente, $pJustify);
                            else
                                $seccion->addListItem($bullet,0,$fuente);
                            $cont++;
                        }
                        //$seccion->addText("". '<w:rPr><w:b w:val="true"/></w:rPr>', $fuente, $pJustify);
                    }else{
                        $seccion->addText("[PPA:".$parrafo->idPPA."]"."[".$parrafo->idParrafo."]".$parrafo->resultado . '<w:rPr><w:b w:val="true"/></w:rPr> (' . $parrafo->dependenciaSiglas . ')', $fuente, $pJustify);
                    }
                } else {
                    if(count($bullets)>0){
                        $cont=0;
                        foreach($bullets as $bullet){
                            if($cont==0)
                                $seccion->addText("[PPA:".$parrafo->idPPA."]"."[".$parrafo->idParrafo."]".$bullet, $fuente, $pJustify);
                            else
                                $seccion->addListItem($bullet,0,$fuente);
                            $cont++;
                        }
                        //$seccion->addText("". '<w:rPr><w:b w:val="true"/></w:rPr>', $fuente, $pJustify);
                    }else{
                        $seccion->addText("[PPA:".$parrafo->idPPA."]"."[".$parrafo->idParrafo."]".$parrafo->resultado, $fuente, $pJustify);
                    }
                }

                if($complementos_s != ""){
                        $seccion->addText($complementos_s, $fuente_c);
                    }
                if($parrafo->ae_cuadros!=""){
                    //Obtenemos los cuadros que estan alineados a la acción
                    $cuadros = explode("|",$parrafo->ae_cuadros);
                    array_pop($cuadros);
                    foreach($cuadros as $cuad){
                        $ae =  AnexoEstadistico::where("id",$cuad)->first();
                        $seccion->addText("[A_E: ".$ae->numero."] ", $fuente_c);
                    }


                }

            }
        }
        //Se agrega la conclusión 
        $conclusion = InformeCoordinadorContenido::where('idDependencia', $request->dependencia)
            ->where('idTemaPED', $request->tema)
            ->where('anio', $anio)
            ->where('seccion', 'conclusion')
            ->orderBy('orden')
            ->pluck('parrafo');

        if ($conclusion->count() > 0) {
            $seccion->addTextBreak(1); // Espacio antes de la conclusión

            $contador = 1;
            foreach ($conclusion as $conclu) {
 $textrun = $seccion->addTextRun([
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::BOTH,
                'lineHeight' => 1.5,
                'spaceBefore' => \PhpOffice\PhpWord\Shared\Converter::pointToTwip(.6),
                'spaceAfter' => \PhpOffice\PhpWord\Shared\Converter::pointToTwip(.6),
            ]);                $textrun->addText('[', $fuente);
                $textrun->addText($contador . ' ', $fuente);
                $textrun->addText('Conclusión', [
                    'italic' => true,
                    'name' => 'Times',
                    'size' => $fuente['size'] - 3 // Tamaño de la fuente
                ]);
                $textrun->addText('] ', $fuente);

                $textrun->addText($conclu, $fuente);

                $contador++;
            }
            $seccion->addTextBreak(1); // Espacio después
        }


        # Ahora un subtítulo con profundidad de 2
        //   $fuenteSubtitulo = [
        //      "name" => "Verdana",
        //     "size" => 18,
        //    "color" => "000000",
        //];
        //$documento->addTitleStyle(2, $fuenteSubtitulo);
        //$seccion->addTitle("Soy un subtítulo", 2);

        $tableStyle = array(
            'borderColor' => '9D2449',
            'borderSize' =>  1,
            'cellMargin' => 50,
            'alignment' => JcTable::CENTER,
            'valing' => 'center'
        );

        $cellStyle = ['alignment' => Jc::CENTER,'valign'=>'center'];

        $firstRowStyle = array('bgColor' => 'FFFFFF','alignment'=> JcTable::CENTER,);
        $documento->addTableStyle('myTable',$tableStyle,$firstRowStyle);
        $table = $seccion->addTable('myTable');
        $table->addRow(200);
        $cell = $table->addCell(5000,['valign'=>'center']);
        $cell->addText("Elaboró",['bold'=>true],['align'=>'center']);
        $cell = $table->addCell(5000,$cellStyle);
        $cell->addText("Aprobó",['bold'=>true],['align'=>'center']);

        //Obtenemos los nombres de los enlaces
        $enlace_directivo = EnlaceDependencia::where("idDependencia",$request->dependencia)->where("tipoEnlace","directivo")->where("status",1)->first();
        $enlace_operativo = EnlaceDependencia::where("idDependencia",$request->dependencia)->where("tipoEnlace","operativo")->where("status",1)->first();


            $table->addRow(600);

            if($enlace_operativo!=null){
                $cell = $table->addCell(5000,['valign'=>'center']);
                $cell->addText(("<w:br/><w:br/><w:br/><w:br/>".$enlace_operativo->titulo." ".$enlace_operativo->nombre." ".$enlace_operativo->apellidoP." ".$enlace_operativo->apellidoM."<w:br/>".$enlace_operativo->cargo),null,['align'=>'center']);
            }else{
                $cell = $table->addCell(5000,['valign'=>'center']);
                $cell->addText("",['bold'=>true],['align'=>'center']);
            }

            if($enlace_directivo!=null){
                $cell = $table->addCell(5000,$cellStyle);
                $cell->addText(("<w:br/><w:br/><w:br/><w:br/>".$enlace_directivo->titulo." ".$enlace_directivo->nombre." ".$enlace_directivo->apellidoP." ".$enlace_directivo->apellidoM."<w:br/>".$enlace_directivo->cargo),null,['align'=>'center']);
            }else{
                $cell = $table->addCell(5000,$cellStyle);
                $cell->addText("",['bold'=>true],['align'=>'center']);
            }



        //$cell->getStyle()->setGridSpan(5);
        # Para que no diga que se abre en modo de compatibilidad
        $documento->getCompatibility()->setOoxmlVersion(15);
        # Idioma español de México
        $documento->getSettings()->setThemeFontLang(new Language("ES-MX"));

        # Guardarlo
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($documento, "Word2007");

        $filename = $dependencia->dependenciaSiglas . "-" . $tema->temaPEDClave . ".docx";
        $objWriter->save("informe.docx");

        $headers = [
            "Content-type: application/octet-stream",
        ];

        return response()->download("informe.docx", $filename, $headers)->deleteFileAfterSend(true);
    }

    public function saveaccion(Request $request)
    {
        try {
            DB::beginTransaction();

            $accionesN = InformeAccion::where("idDependencia", $request->dependencia)->where("idTemaPED", $request->tema)->where("creacion", "m")->get();
            $maxAcciones = MatrizCoordinacion::select("acciones_max")->where("dependencias_id", $request->dependencia)->where("idTemaPED", $request->tema)->first();

            if ($request->id == "") {
                if ($accionesN->count() >= $maxAcciones->acciones_max) {
                    return response()->json([
                        'result' => 'error',
                        'message' => 'ha rebasado el límite de registro de acciones nuevas!',
                    ]);
                } else {
                    InformeAccion::create([
                        "nombre" => $request->nombre,
                        "idDependencia" => $request->dependencia,
                        "idTemaPED" => $request->tema,
                        "alineacion_la" => $request->lineas,
                        "ae_cuadros" => $request->cuadros,
                        "creacion" => "m"
                    ]);
                }
            } else {
                InformeAccion::where("id", $request->id)->update([
                    "nombre" => $request->nombre,
                    "alineacion_la" => $request->lineas,
                    "ae_cuadros" => $request->cuadros,
                ]);
            }

            DB::commit();
            return response()->json([
                "result" => "ok",
                "message" => "La acción ha sido almacenada Satisfactoriamente!"
            ], 200);
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json([
                "result" => "error",
                "message" => "Ocurrió un error al tratar de almacenar la Acción!"
            ], 500);
        }
    }

    public function getinfoaccion(Request $request)
    {
        $accion_id = $request->id;
        $infoAccion = InformeAccion::where("id", $accion_id)->first();

        if ($infoAccion != null) {

            //obtenemos lineas de accion asociadas
            $lineas_ = explode("|", $infoAccion->alineacion_la);
            if (count($lineas_) > 0) {
                array_pop($lineas_);
                $lineas = "";
                foreach ($lineas_ as  $lin) {
                    $infoLinea = LineaPED::where("idLAPED", $lin)->first();
                    if ($infoLinea != null) {
                        $lineas .= '<tr id="linea' . $infoLinea->idLAPED . '" >' .
                            '<td class="linea_asociada" id="asociada" style="display:none;">' . $infoLinea->idLAPED . '</td>' .
                            '<td style="padding:10px;">' . $infoLinea->laPEDClave . " " . $infoLinea->laPEDDescripcion . '</td>' .
                            '<td style="text-align:center"><button type="button" disabled class="btn btn-danger" @if(false) onclick="quitLinea(' .
                            $infoLinea->idLAPED . ') @endif"><i class="fas fa-trash"></i></button></td>' .
                            '</tr>';
                    }
                }
            }

            //obtenemos cuadros estadisticos asociados

            $cuadros_ = explode("|", $infoAccion->ae_cuadros);
            if (count($cuadros_) > 0) {
                array_pop($cuadros_);
                $cuadros = "";
                foreach ($cuadros_ as  $cuad) {
                    $infoCuad = AnexoEstadistico::where("id", $cuad)->first();
                    if ($infoCuad != null) {
                        $cuadros .= '<tr id="cuadro' . $infoCuad->id . '" >' .
                            '<td class="cuadro_asociado" id="asociada_c" style="display:none;">' . $infoCuad->id . '</td>' .
                            '<td style="padding:10px;">' . $infoCuad->numero . " " . $infoCuad->cuadro . '</td>' .
                            '<td style="text-align:center"><button  type="button" class="btn btn-danger" onclick="quitCuadro(' .
                            $infoCuad->id . ')" ><i class="fas fa-trash"></i></button></td>' .
                            '</tr>';
                    }
                }
            }

            return response()->json([
                "result" => "ok",
                "info" => $infoAccion,
                "lineas" => $lineas,
                "cuadros" => $cuadros
            ]);
        } else {
            return response()->json([
                "result" => "error",
                "message" => "No se localizó la acción indicada!",

            ]);
        }
    }

    public function redactaparrafos($accion_id)
    {
        $infoAccion = InformeAccion::where("id", $accion_id)
            ->join("dependencia", "dependencia.idDependencia", "=", "informe_acciones.idDependencia")
            ->join("temaped", "temaped.idTemaPED", "=", "informe_acciones.idTemaPED")
            ->first();
        $parrafos = InformeParrafo::where("informe_acciones_id", $accion_id)->get();
        if($infoAccion->alineacion_la!=""){
            return view("informe.redactarparrafos")->with("accion", $infoAccion)->with("parrafos", $parrafos);
        }else{
            return redirect()->route('nopermitido');
        }

    }

    public function almacenap(Request $request)
    {
        $user_id = auth()->user()->id;
        $campos = "";
        $texto = "";
        $accion_id = $request->accion_id;
        $parrafoscapturados = InformeParrafo::where("informe_acciones_id", $accion_id)->get();
        $orden = $parrafoscapturados->count() + 1;
        if ($request->plantilla != 4) {
            $modelo = ParrafoBase::where("id", $request->plantilla)->first();
            $modelo = $modelo->cuerpo;
            $campos = $request->campos;
            $texto = $modelo;
            $campos_ = explode("|", $campos);
            $texto = str_replace("&campo1", $campos_[0], $texto);
            $texto = str_replace("&campo2", $campos_[1], $texto);
            $texto = str_replace("&campo3", $campos_[2], $texto);
            $texto = str_replace("&campo4", $campos_[3], $texto);
            $texto = str_replace("&campo5", $campos_[4], $texto);
            $texto = str_replace("&campo6", $campos_[5], $texto);
            $texto = str_replace("&campo7", $campos_[6], $texto);
            $texto = str_replace("&campo8", $campos_[7], $texto);
            $texto = str_replace("&campo9", $campos_[8], $texto);
        } else {
            $modelo = "";
            $texto = $request->texto;
        }
        $parrafos = InformeParrafo::where("informe_acciones_id", $request->accion_id)->get();
        $accion = InformeAccion::where("id", $request->accion_id)->first();

        try {
            DB::beginTransaction();
            if ($request->parrafo_id == "") {
                if ($parrafos->count() >= $accion->parrafos_max) {
                    return response()->json([
                        "result" => "error",
                        "message" => "se ha llegado al límite de parrafos capturados!"
                    ]);
                } else {
                    InformeParrafo::create([
                        "users_id" => $user_id,
                        "campos" => $campos,
                        "resultado" => $texto,
                        "texto" => $modelo,
                        "informe_acciones_id" => $accion_id,
                        "tipo" => $request->plantilla,
                        "orden" => $orden
                    ]);
                }
            } else {
                InformeParrafo::where("id", $request->parrafo_id)->update([
                    "campos" => $campos,
                    "resultado" => $texto,
                    "texto" => $modelo,
                    "tipo" => $request->plantilla,
                ]);
            }
            DB::commit();
            return response()->json([
                "result" => "ok",
                "message" => "Párrafo almacenado satisfactoriamente!"
            ]);
        } catch (Exception $ex) {
            DB::rollBack();
            $error = $ex->getMessage();
            $msg = "Ocurrió un error al intentar almacenar el párrafo favor de intentar más tarde!";
            if(strpos($error,"Data too long")>0)
                $msg = "El párrafo excede el límite de caracteres permitidos";
            return response()->json([
                "result" => "error",
                "message" => $msg
            ]);
        }
    }

    public function updateordenparrafo(Request $request)
    {
        try {
            InformeParrafo::where("id", $request->parrafo)->update([
                "orden" => $request->orden
            ]);
            return response()->json([
                "result" => "ok",
                "message" => "Orden Actualizado correctamente!"
            ]);
        } catch (Exception $ex) {
            return response()->json([
                "result" => "error",
                "message" => "Error al actualizar el orden!"
            ]);
        }
    }

    public function updatestatusparrafo(Request $request)
    {
        try {

            InformeParrafo::where("id", $request->parrafo)->update([
                "status" => $request->status == "false" ? false : true
            ]);
            return response()->json([
                "result" => "ok",
                "message" => "Status Actualizado correctamente!"
            ]);
        } catch (Exception $ex) {
            return response()->json([
                "result" => "error",
                "message" => "Error al actualizar el status!"
            ]);
        }
    }

    public function getinfoparrafo(Request $request)
    {
        $infoParrafo = InformeParrafo::where("id", $request->parrafo)->first();
        return response()->json([
            "result" => "ok",
            "message" => "Párrafo localizado",
            "parrafo" => $infoParrafo
        ]);
    }

    public function uploadComplemento(Request $req)
    {
        try {
            $medio = $req->file('file');
            //dd($medio->getClientOriginalName());
            $extension = $medio->extension();
            $random = time() . rand(1, 100);
            $nombreMedio =  $random . '.' . $medio->extension();
            $carpeta = 'medios/informe/2do/' . $req->idParrafo;
            if (!file_exists($carpeta)) {
                mkdir($carpeta, 0777, true);
            }
            $medio->move(public_path('medios/informe/2do/' . $req->idParrafo . "/"), $nombreMedio);
            DB::beginTransaction();
            $mediog = new InformeMedio();
            $mediog->idParrafo = $req->idParrafo;
            $mediog->ubicacion = $nombreMedio;
            $mediog->nombre = $medio->getClientOriginalName();
            $mediog->save();
            DB::commit();
            return response()->json([
                'result' => 'ok',
                'message' => 'Complemento cargado Satisfactoriamente!',
                'filename' => $nombreMedio,
                'medio_id' => $mediog->id
            ], 200);
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json([
                'result' => 'error',
                'message' => 'Ocurrió un error al cargar el complemento!' . $ex,
            ], 500);
        }
    }

    public function getcomplementos(Request $req)
    {
        $complementos = InformeMedio::where("idParrafo", $req->idParrafo)->get();

        return view("informe.complementos")->with("complementos", $complementos);
    }

    public function deletecomplemento(Request $request)
    {
        $infoMedio = InformeMedio::find($request->idComplemento);
        $file = public_path('medios/informe/2do/') . $request->idParrafo . "/" . $infoMedio->ubicacion;
        try {
            if (file_exists($file)) {
                if (unlink($file)) {
                    $infoMedio->delete();
                }
            } else {
                $infoMedio->delete();
            }

            return response()->json([
                'result' => 'ok',
                'message' => 'Medio eliminado satisfactoriamente!',
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'result' => 'error',
                'message' => 'Ocurrió un error al eliminar el medio!',
            ]);
        }
    }

    public function savecomplementos(Request $request)
    {
        $complementos = $request->complementos;
        $descripciones = $request->descripciones;
        if ($complementos != "") {
            $arrayComplementos = explode("|", $complementos);
            $arrayDescripciones = explode("|", $descripciones);
            array_pop($arrayComplementos);
            array_pop($arrayDescripciones);

            DB::beginTransaction();
            try {
                $contador = 0;
                foreach ($arrayComplementos as $com) {
                    InformeMedio::where("id", $com)->update([
                        "descripcion" => $arrayDescripciones[$contador]
                    ]);
                    $contador++;
                }
                DB::commit();
                return response()->json([
                    "result" => "ok",
                    "message" => "Descripciones de los complementos almacenados satisfactoriamente!"
                ]);
            } catch (Exception $ex) {
                DB::rollBack();
                return response()->json([
                    "result" => "error",
                    "message" => "Ocurrió un error al almacenar las descripciones de los complementos!"
                ]);
            }
        }
    }


    public function deleteparrafo(Request $request)
    {
        $idParrafo = $request->idParrafo;
        try {
            DB::beginTransaction();
            //Antes de Eliminarlo, procedemos a verificar si tiene complementos para realizar el borrado de los archivos cargados
            $complementosCargados = InformeMedio::where("idParrafo", $idParrafo)->get();
            if ($complementosCargados->count() > 0) {
                foreach ($complementosCargados  as $complemento) {
                    $file = public_path('medios/informe/2do/') . $idParrafo . "/" . $complemento->ubicacion;
                    if (file_exists($file)) {
                        unlink($file);
                    }
                }
            }
            InformeParrafo::where("id", $idParrafo)->delete();
            DB::commit();
            return response()->json([
                "result" => "ok",
                "message" => "El párrafo fue eliminado satisfactoriamente así como todos los complementos cargados!"
            ]);
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json([
                "result" => "error",
                "message" => "Ocurrió un error al intentar eliminar el párrafo correspondiente!"
            ]);
        }
    }

    public function checkacciones(Request $request)
    {

        $accionesN = InformeAccion::where("idDependencia", $request->dependencia)->where("idTemaPED", $request->tema)->where("creacion", "m")->get();
        $maxAcciones = MatrizCoordinacion::select("acciones_max")->where("dependencias_id", $request->dependencia)->where("idTemaPED", $request->tema)->first();

        if ($accionesN->count() >= $maxAcciones->acciones_max) {
            return response()->json([
                "result" => "error",
                "message" => "Ha rebasado el límite de registro de acciones nuevas."
            ]);
        } else {
            return response()->json([
                "result" => "ok",
                "message" => "Puede capturar una nueva acción."
            ]);
        }
    }

    public function deleteaccion(Request $request)
    {
        try {
            DB::beginTransaction();
            informeAccion::where("id", $request->idAccion)->update([
                "status" => 0
            ]);
            DB::commit();
            return response()->json([
                "result" => "ok",
                "message" => "La acción se ha eliminado de manera satisfactoria."
            ]);
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json([
                "result" => "error",
                "message" => "Ocurrió un error al intentar eliminar la acción correspondiente."
            ]);
        }
    }

    public function checkparrafos(Request $request)
    {
        $parrafos = InformeParrafo::where("informe_acciones_id", $request->accion_id)->get();
        $accion = InformeAccion::where("id", $request->accion_id)->first();
        if ($parrafos->count() >= $accion->parrafos_max) {
            return response()->json([
                "result" => "error",
                "message" => "Ha excedido el total de párrafos permitidos para esta acción!"
            ]);
        } else {
            return response()->json([
                "result" => "ok",
                "message" => "Puede Proceder con la captura del párrafo!"

            ]);
        }
    }

    public function adminacciones()
    {
        $acciones = InformeAccion::select("informe_acciones.*","dependencia.*","temaped.*","informe_acciones.status as status_accion")->join("dependencia", "dependencia.idDependencia", "=", "informe_acciones.idDependencia")
            ->join("temaped", "temaped.idTemaPED", "=", "informe_acciones.idTemaPED")
            ->get();
        $temas = TemaPED::all();
        $dependencias = Dependencia::all();

        return view("informe.adminacciones")->with("acciones", $acciones)->with("temas",$temas)->with("dependencias",$dependencias);
    }

    public function updatemaxp(Request $request)
    {
        $accion = InformeAccion::where("id", $request->idAccion)->first();
        $max = 0;

        $accion->update([
            "parrafos_max" => $request->max
        ]);
        $max = $accion->parrafos_max;
        return response()->json([
            "maxp" => $max
        ]);
    }

    public function getparrafos(Request $request)
    {
        $parrafos = InformeParrafo::select("informe_parrafos.id as idParrafo", "informe_parrafos.*", "dependencia.*")->where("informe_acciones_id", $request->idAccion)
            ->join("informe_acciones", "informe_acciones.id", "=", "informe_parrafos.informe_acciones_id")
            ->join("dependencia", "dependencia.idDependencia", "=", "informe_acciones.idDependencia")
            ->get();
        return view("informe.getparrafos")->with("parrafos", $parrafos);
    }

    public function getcomplementoszip(Request $request)
    {

        $idTemaPED = $request->idTemaPED;
        $infoTema = TemaPed::where("idTemaPED",$idTemaPED)->first();
        $complementos = InformeMedio::select("informe_medios.idParrafo","informe_medios.nombre as archivo","informe_acciones.idTemaPED","informe_medios.ubicacion")->join("informe_parrafos","informe_parrafos.id","=","informe_medios.idParrafo")
                                                                    ->join("informe_acciones","informe_acciones.id","=","informe_parrafos.informe_acciones_id")
                                                                    ->where("informe_acciones.idTemaPED","=",$idTemaPED)
                                                                    ->get();

        try {
            $zip = new ZipArchive();
            $filename = public_path("medios/informe/2do") . "/complementos_tema_".$infoTema->temaPEDClave.".zip";
            $zip->open($filename, ZipArchive::CREATE);
            foreach($complementos as $complemento){
                if(file_exists(public_path("medios/informe/2do")."/".$complemento->idParrafo."/".$complemento->ubicacion)){
                    $zip->addFile(public_path("medios/informe/2do")."/".$complemento->idParrafo."/".$complemento->ubicacion,$complemento->archivo);
                }
            }
            $zip->close();
            //Sin notificaciones, y que el server no comprima
            @ini_set('error_reporting', E_ALL & ~ E_NOTICE);
            @ini_set('zlib.output_compression', 'Off');
            //Encabezados para archivos .zip
            header('Content-Type: application/zip');
            header('Content-Transfer-Encoding: Binary');

            header('Content-disposition: attachment; filename="' . basename($filename) . '"');
            //Que no haya límite en la ejecución del script
            @set_time_limit(0);

            //Imprime el contenido del archivo
            readfile($filename);

            unlink($filename);
        } catch (Exception $ex) {
            dd($ex);
        }
    }

    public function descargalistado(Request $request){
        $dependencia = auth()->user()->enlace->idDependencia;
        return Excel::download(new AccionesTemaDependenciaExport($request->tema), 'acciones_tema'.$request->tema."_".$dependencia.'.xlsx');
    }

    public function changestatus(Request $request){
        try {
            DB::beginTransaction();
            informeAccion::where("id", $request->idAccion)->update([
                "status" => $request->status
            ]);
            DB::commit();
            return response()->json([
                "result" => "ok",
                "message" => "La acción se ha dado de baja de manera satisfactoria.",
                "status" => $request->status
            ]);
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json([
                "result" => "error",
                "message" => "Ocurrió un error al intentar dar de baja la acción correspondiente."
            ]);
        }
    }

    public function updatecampo(Request $request){
        //actualizamos el campo del indicador
        try{
            InformeAccion::where("id",$request->accion)->update([
                $request->campo => $request->valor
            ]);
            return response()->json([
                'success' => 'ok',
                'valor' => $request->valor
            ]);

        }catch(Exception $ex){
            $valor = InformeAccion::where("id",$request->accion)->select(''+$request->campo+'')->first();
            return response()->json([
                'success' => 'error',
                'valor' => $valor->campo
            ]);

        }
    }

    public function nuevaaccion(Request $request){
        try {
            DB::beginTransaction();
                    InformeAccion::create([
                        "nombre" => $request->nombre,
                        "idDependencia" => $request->dependencia,
                        "idTemaPED" => $request->tema,
                        "creacion" => "s"
                    ]);
            DB::commit();
            return response()->json([
                "result" => "ok",
                "message" => "el PPA ha sido almacenado Satisfactoriamente!"
            ], 200);
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json([
                "result" => "error",
                "message" => "Ocurrió un error al tratar de almacenar el PPA!"
            ], 500);
        }
    }
    public function descargaacciones(Request $request){
        if(isset($request->consulta)){
            return Excel::download(new InformeAccionesExport(1), 'Informe_acciones'.date("Y-m-d_His").'.xlsx');
        }else{
            return Excel::download(new InformeAccionesExport, 'Informe_acciones'.date("Y-m-d_His").'.xlsx');
        }

        
    }

    public function getparrafosct(Request $request){
        $idTemaPED = $request->idTemaPED;
        $parrafos = InformeParrafo::select("informe_parrafos.id as idParrafo", "informe_parrafos.*", "dependencia.*")
                ->join("informe_acciones", "informe_acciones.id", "=", "informe_parrafos.informe_acciones_id")
                ->join("dependencia", "dependencia.idDependencia", "=", "informe_acciones.idDependencia")
                ->where("idTemaPED", $idTemaPED)
                ->where("informe_parrafos.status", 1)
                ->where("informe_acciones.status","=",1)
                ->orderBy("informe_parrafos.orden_ct", "ASC")
                ->get();
        $tema = TemaPED::where("idTemaPED",$idTemaPED)->first();

        if($parrafos->count()>0){
            return view("informe.parrafosorden")->with("parrafos",$parrafos)->with("tema",$tema);
        }
        return null;
    }

    public function updateordenct(Request $request){
        try{
            InformeParrafo::find($request->idParrafo)->update([
                "orden_ct" => $request->orden
            ]);
            return response()->json([
                "result" => "ok",
                "message" => "Se ha actualizado el orden correspondiente"
            ]);
        }catch(Exception $ex){
            return response()->json([
                "result" => "error",
                "message" => "Ocurrió un error al intentar actualizar el orden del párrafo"
            ]);
        }
    }

    public function bloqueotema(Request $request){
        try{
            MatrizCoordinacion::where("dependencias_id",$request->idDependencia)->where("idTemaPED",$request->idTemaPED)->where("informe",$request->informe)->update([
                "bloqueado" => $request->valor
            ]);
            return response()->json([
                "result" => "ok",
                "message" => "Bloqueo actualizado satisfactoriamente"
            ]);
        }catch(Exception $ex){
            return response()->json([
                "result" => "error",
                "message" => "Ocurrió un error al intentar actualizar el bloqueo del tema"
            ]);
        }
    }

    public function cumplimiento(){
        return Excel::download(new CumplimientoInformeExport, 'cumplimiento_informe'.date('Ymd-His').'.xlsx');
    }

    public function resumen(){
        $lineas_a = array();
        for($x=1;$x<=442;$x++)
            $lineas_a[$x] = array();

        $acciones = InformeAccion::where("informe_acciones.status",1)
                    ->join("dependencia","dependencia.idDependencia","=","informe_acciones.idDependencia")
                    ->get();

        foreach($acciones as $accion){
            $parrafos_redactados = InformeParrafo::where("informe_acciones_id",$accion->id)->get()->count();
            $lineas_ = explode('|', $accion->alineacion_la);
            if (count($lineas_) > 0) {
                array_pop($lineas_);
                $acciones_v = array();
                foreach ($lineas_ as $lin) {
                    $infoLinea = LineaPED::where('idLAPED', $lin)->first();
                    if ($infoLinea != null) {
                        //Obtenemos los cuadros alineados a la accion si los hay
                        $cuadros = explode("|",$accion->ae_cuadros);
                        $cuadros_s = "";

                        if(count($cuadros)>0){
                            array_pop($cuadros);
                          foreach($cuadros as $cuadro){
                            $cuad = AnexoEstadistico::where("id",$cuadro)->first();
                            $cuadros_s .= $cuad->numero."<br/>";
                          }
                        }
                        array_push($lineas_a[$lin],$accion->id." ".$accion->nombre ." ".$accion->temaPEDDescripcion."|".$parrafos_redactados."|".$accion->dependenciaSiglas."|".$cuadros_s);
                        //$lineas_a[$lin] .=$accion->id." ".$accion->nombre ." ".$accion->temaPEDDescripcion."|".$parrafos_redactados."\n";
                    }
                }
                //$lineas_a[$lin] = $acciones_v;
            }
        }
        $lineasped = LineaPED::select("*")->join("estrategiaped","estrategiaped.idEstrategiaPED","=","lineaaccionped.idEstrategiaPED")
                    ->join("objetivoped","objetivoped.idObjetivoPED","=","estrategiaped.idObjetivoPED")
                    ->join("temaped","temaped.idTemaPED","=","objetivoped.idTemaPED")
                    ->join("ejeped","ejeped.idEjePED","=","temaped.idEjePED")
                    ->get();

        return view("informe.resumen")->with("valores",$lineas_a)->with("lineas",$lineasped);
    }

    public function porlineas(){
        return Excel::download(new PorLineasExport, 'por_lineas_ped'.date('Ymd-His').'.xlsx');
    }

    public function listadoppas(){
        $acciones = InformeAccion::select("informe_acciones.*","dependencia.*","temaped.*","informe_acciones.status as status_accion")->join("dependencia", "dependencia.idDependencia", "=", "informe_acciones.idDependencia")
            ->join("temaped", "temaped.idTemaPED", "=", "informe_acciones.idTemaPED")
            ->get();
        $temas = TemaPED::all();
        $dependencias = Dependencia::all();

        return view("consulta.ppas")->with("acciones", $acciones)->with("temas",$temas)->with("dependencias",$dependencias);
    }

    // Funcion para boton de ver datos generales
    public function getDatosGenerales(Request $request)
    {
        if (!$request->has('idAccion') || !is_numeric($request->idAccion)) {
            return response()->json([
                'result' => 'error',
                'message' => 'ID inválido'
            ], 400);
        }

        try {
            // Obtener acción principal con su objetivo y descripción
            $accion = DB::table('informe_acciones')
                ->where('id', $request->idAccion)
                ->select('id', 'nombre', 'objetivo', 'descripcion')
                ->first();

            if (!$accion) {
                return response()->json([
                    'result' => 'error',
                    'message' => 'Acción no encontrada'
                ], 404);
            }

            // Helper para concatenar listas o mostrar '-'
            $implodeOrDefault = fn($arr, $sep = '<br>') => count($arr) ? implode($sep, $arr) : '-';

            // Helper general para campos simples desde ia_alineacion
            $extraerAlineacion = function ($table, $campo, $alField, $joinField, $alias) use ($accion) {
                return DB::table('ia_alineacion as al')
                    ->leftJoin("$table as t", "al.$alField", '=', "t.$joinField")
                    ->where('al.ia_id', $accion->id)
                    ->selectRaw("DISTINCT $campo as $alias")
                    ->pluck($alias)
                    ->filter()
                    ->unique()
                    ->toArray();
            };

            // Bienes o servicios
            $bienes = DB::table('ia_bs')
                ->where('ia_id', $accion->id)
                ->pluck('nombreBS')
                ->toArray();

            $accion->bienes = count($bienes)
                ? '<ul><li>' . implode('</li><li>', $bienes) . '</li></ul>'
                : '-';

            // Alineación general
            $accion->eje = $implodeOrDefault($extraerAlineacion('ejeped', "CONCAT(t.ejePEDClave, ' ', t.ejePEDDescripcion)", 'idEjePED', 'idEjePED', 'eje_nombre'));
            $accion->tema = $implodeOrDefault($extraerAlineacion('temaped', "CONCAT(t.temaPEDClave, ' ', t.temaPEDDescripcion)", 'idTemaPED', 'idTemaPED', 'tema_nombre'));
            $accion->objetivo_ped = $implodeOrDefault($extraerAlineacion('objetivoped', "CONCAT(t.objetivoPEDClave, ' ', t.objetivoPEDDescripcion)", 'idObjetivoPED', 'idObjetivoPED', 'objetivo_ped'));

            // Estrategias (vía líneas de acción)
            $estrategias = DB::table('ia_alineacion as al')
                ->join('lineaaccionped as la', DB::raw("FIND_IN_SET(la.idLAPED, REPLACE(REPLACE(REPLACE(al.lineas, '|', ','), ',,', ','), ',', ','))"), '>', DB::raw("0"))
                ->leftJoin('estrategiaped as est', 'la.idEstrategiaPED', '=', 'est.idEstrategiaPED')
                ->where('al.ia_id', $accion->id)
                ->selectRaw("DISTINCT CONCAT(est.estrategiaPEDClave, ' ', est.estrategiaPEDDescripcion) as estrategia_nombre")
                ->pluck('estrategia_nombre')
                ->filter()
                ->unique()
                ->toArray();

            $accion->estrategias = $implodeOrDefault($estrategias);

            // Líneas de acción
            $lineas = DB::table('ia_alineacion as al')
                ->join('lineaaccionped as la', DB::raw("FIND_IN_SET(la.idLAPED, REPLACE(REPLACE(REPLACE(al.lineas, '|', ','), ',,', ','), ',', ','))"), '>', DB::raw("0"))
                ->where('al.ia_id', $accion->id)
                ->selectRaw("DISTINCT CONCAT(la.laPEDClave, ' ', la.laPEDDescripcion) as linea_nombre")
                ->pluck('linea_nombre')
                ->filter()
                ->unique()
                ->toArray();

            $accion->lineas = $implodeOrDefault($lineas);

            // Alineación sectorial
            $accion->sector = $implodeOrDefault($extraerAlineacion('sectores', "CONCAT(t.claveSector, ' ', t.sector)", 'idSector', 'idSector', 'sector_nombre'));
            $accion->obj_sector = $implodeOrDefault($extraerAlineacion('objetivosector', "CONCAT(t.claveObjetivo, ' ', t.objetivo)", 'idObjetivoSector', 'idObjetivo', 'obj_sector_nombre'));
            $accion->estrat_sector = $implodeOrDefault($extraerAlineacion('estrategiasector', "CONCAT(t.claveEstrategia, ' ', t.estrategia)", 'idEstrategiaSector', 'idEstrategia', 'estrat_sector_nombre'));

            // Presupuesto relacionado con los bienes o servicios
            $presupuesto = DB::table('ia_bs as bs')
                ->join('ia_bs_presupuesto as p', 'bs.idBS', '=', 'p.idBS')
                ->leftJoin('programa_presupuestario as prog', 'p.idPrograma', '=', 'prog.idPrograma')
                ->where('bs.ia_id', $accion->id)
                ->select(
                    'bs.nombreBS as bien',
                    'p.anio',
                    'p.tipo',
                    DB::raw("CONCAT(prog.clavePrograma, ' ', prog.descripcionPrograma) as descripcionPrograma"),
                    'p.e1',
                    'p.e2',
                    'p.e3',
                    'p.e4'
                )
                ->orderBy('p.anio')
                ->get();

            $accion->presupuesto = $presupuesto;
            //Entregas relacionaddas con los Bienes o servicios
            $entregas = DB::table('ia_bs as bs')
            ->join('ia_bs_entregas as e','bs.idBs','=','e.idBS')
            ->where('bs.ia_id',$accion->id)
             ->select(
                    'bs.nombreBS as bien',
                    'e.anio',
                    'e.r1',
                    'e.r2',
                    'e.r3',
                    'e.r4'
            )
            ->orderBy('bs.nombreBS')
            ->get();
            $accion->entregas = $entregas;
                    


            return response()->json([
                'result' => 'ok',
                'accion' => $accion
            ]);

        } catch (Exception $e) {
            \Log::error("Error al obtener datos generales de acción: " . $e->getMessage());

            return response()->json([
                'result' => 'error',
                'message' => 'Error del servidor'
            ], 500);
        }
    }
    //Funciones para  la introduccion y conclusion de Informe 
     public function guardarInformeCoordinador(Request $request)
    {
        $request->validate([
            'introduccion.*.parrafo' => 'nullable|string|max:600',
            'conclusion.*.parrafo' => 'nullable|string|max:600',
        ], [
            'introduccion.*.parrafo.max' => 'Cada párrafo de introducción debe tener como máximo 600 caracteres.',
            'conclusion.*.parrafo.max' => 'Cada párrafo de conclusión debe tener como máximo 600 caracteres.',
        ]);


        $user = Auth::user();
        if (!$user || !$user->enlace || !$user->enlace->idDependencia) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo identificar la dependencia del usuario autenticado.'
            ], 403);
        }

        $idTemaPED = $request->input('idTemaPED');
        $anio = $request->input('anio', now()->year); // usa el año enviado o el actual por defecto
        $idDependencia = $user->enlace->idDependencia;

        DB::beginTransaction();

        try {
            // Guardar introducción
            $orden = 1;
            foreach ($request->input('introduccion', []) as $item) {
                $texto = trim($item['parrafo'] ?? '');
                if ($texto !== '') {
                    InformeCoordinadorContenido::updateOrCreate(
                        [
                            'idInformeCT' => $item['idInformeCT'] ?? null, 
                        ],
                        [
                            'idDependencia' => $idDependencia,
                            'idTemaPED' => $idTemaPED,
                            'seccion' => 'introduccion',
                            'orden' => $orden,
                            'anio' => $anio,
                            'parrafo' => $texto,
                        ]
                    );
                    $orden++;
                }
            }

            // Guardar conclusión
            $orden = 1;
            foreach ($request->input('conclusion', []) as $item) {
                $texto = trim($item['parrafo'] ?? '');
                if ($texto !== '') {
                    InformeCoordinadorContenido::updateOrCreate(
                        [
                            'idInformeCT' => $item['idInformeCT'] ?? null,
                        ],
                        [
                            'idDependencia' => $idDependencia,
                            'idTemaPED' => $idTemaPED,
                            'seccion' => 'conclusion',
                            'orden' => $orden,
                            'anio' => $anio,
                            'parrafo' => $texto,
                        ]
                    );
                    $orden++;
                }
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Informe guardado correctamente.'
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el informe: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getInformeCoordinadorContenido(Request $request)
    {
        $user = Auth::user();

        if (!$user || !$user->enlace || !$user->enlace->idDependencia) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado o sin dependencia',
            ], 403);
        }

        $idDependencia = $user->enlace->idDependencia;
        $idTemaPED = $request->input('idTemaPED');
        $anio = $request->input('anio', now()->year); 

        $parrafos = InformeCoordinadorContenido::where('idDependencia', $idDependencia)
            ->where('idTemaPED', $idTemaPED)
            ->where('anio', $anio)
            ->orderBy('seccion')
            ->orderBy('orden')
            ->get()
            ->groupBy('seccion');

        return response()->json([
            'success' => true,
            'introduccion' => $parrafos['introduccion'] ?? [],
            'conclusion' => $parrafos['conclusion'] ?? [],
        ]);
    }
    public function eliminarParrafoInforme(Request $request)
    {
        $user = Auth::user();
        $idParrafo = $request->id;

        try {
            $parrafo = InformeCoordinadorContenido::where('idInformeCT', $idParrafo)
                ->where('idDependencia', $user->enlace->idDependencia)
                ->first();

            if (!$parrafo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Párrafo no encontrado o no autorizado.'
                ], 404);
            }

            $parrafo->delete();

            return response()->json([
                'success' => true,
                'message' => 'Párrafo eliminado correctamente.'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el párrafo: ' . $e->getMessage()
            ], 500);
        }
    }



}