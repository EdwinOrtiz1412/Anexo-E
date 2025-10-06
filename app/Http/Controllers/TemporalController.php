<?php

namespace App\Http\Controllers;

use Excel;
use Exception;
use App\Models\Indicador;
use App\Models\Asistencias;
use Illuminate\Http\Request;
use App\Models\EncuestaSiibien;
use App\Exports\EncuestasExport;
use App\Exports\Encuesta2025Export;
use App\Exports\AsistenciasExport;
use App\Models\Dependencia;
use App\Models\EjePED;
use App\Models\EncuestaSiibien2025;
use App\Models\EnlaceDependencia;
use App\Models\IAAlineacion;
use App\Models\IABS;
use App\Models\IAMedio;
use App\Models\IAObservacion;
use App\Models\IAPoblacion;
use App\Models\IAPoblacionAnual;
use App\Models\IAPresupuestoTipoG;
use App\Models\InformeAccion;
use App\Models\Region;
use App\Models\Titular;
use Illuminate\Support\Facades\DB;
use TCPDF;
use App\Models\Registro;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TemporalController extends Controller
{
    public function registraasistencia(Request $request){

        $this->validate($request,[
            "nombre" => 'required',
            "cargo" => 'required',
            "dependencia" => 'required',
            "email" => 'required|email',
            "telefono" => 'required',
            "tipo_enlace" => "required",
            "perfil" => "required"
        ]);

        try{
            Asistencias::create([
                "nombre" => $request->nombre,
                "cargo" => $request->cargo,
                "dependenciasId" => $request->dependencia,
                "email" => $request->email,
                "tipo_enlace" => $request->tipo_enlace,
                "perfil" => $request->perfil,
                "telefono" => $request->telefono,
                "evento"=>"itar",
            ]);
            $resultado = true;
            $nombre = $request->nombre;
        }catch(Exception $ex){
            $resultado = false;
            $nombre = "";
        }
        return view('temporal.resultadoregistro')->with("resultado",$resultado)->with("nombre",$nombre);
    }
    public function downloadasistencias(){
        try{
            return Excel::download(new AsistenciasExport, 'asistencias'.date('YmdHis').'.xlsx');
        }catch(Exception $ex){
           dd($ex);
        }

    }

    public function registraencuesta(Request $request){
        $this->validate($request,[
            "p1" => 'required',
            "p2" => 'required',
            "p3" => 'required',
            "p4" => 'required',
            "p5" => 'required',
            "p6" => 'required',
        ]);


        try{
            DB::beginTransaction();
            EncuestaSiibien::create([
                'p1' => $request->p1,
                'p2' => $request->p2,
                'p3' => $request->p3,
                'p4' => $request->p4,
                'p5' => $request->p5,
                'p6' => $request->p6,
                'p7' => $request->p7
            ]);
            $resultado=true;
            DB::commit();
        }catch(Exception $ex){
            dd($ex);
            $resultado=false;
            DB::rollback();
        }
        return view('temporal.resulencuesta')->with("resultado",$resultado);
    }
    public function downloadresultadosencuesta(){
        try{
            return Excel::download(new EncuestasExport, 'resultencuesta'.date('YmdHis').'.xlsx');
        }catch(Exception $ex){
           dd($ex);
        }

    }

    public function downloadresultadosencuesta2025(){
        try{
            return Excel::download(new Encuesta2025Export, 'resultencuesta'.date('YmdHis').'.xlsx');
        }catch(Exception $ex){
           dd($ex);
        }

    }

    public function indicadoreseje($eje_id){
        $eje = EjePED::where("idEjePED",$eje_id)->first();
        switch($eje_id){
            case 1:
                $color = "rgb(78,172,162)";
                break;
            case 2:
                $color = "rgb(155,39,69)";
                break;
            case 3:
                $color = "rgb(97,119,172)";
                break;
            case 4:
                $color = "rgb(113,173,74)";
                break;
            case 5:
                $color = "rgb(225,136,64)";
                break;
            default:
                $color = "rgb(0,0,0)";
                break;
        }
        $dependencias = Dependencia::all();

        $Indicadores = Indicador::select("indicador.*", "dependencia.dependenciaSiglas","ejeped.idEjePED")
                ->join("dependencia", "dependencia.idDependencia", "=", "indicador.idDependencia")
                ->join("indicadorobjetivos", "indicadorobjetivos.idIndicador", "=", "indicador.idIndicador")
                ->join("objetivoped", "objetivoped.idObjetivoPED", "=", "indicadorobjetivos.idObjetivoPED")
                ->join("temaped", "objetivoped.idTemaPED", "=", "temaped.idTemaPED")
                ->join("ejeped", "ejeped.idEjePED", "=", "temaped.idEjePED")
                ->where("indicador.status", 1)
                ->where("ejeped.idEjePED",$eje_id)->get()->sortBy("idIndicador");
        return view("temporal.indicadoreseje")->with("indicadores",$Indicadores)->with("eje",$eje)->with('color',$color)->with("dependencias",$dependencias);
    }

    public function registraencuesta2025(Request $request){
        $this->validate($request,[
            "p1" => 'required',
            "p2" => 'required',
            "p3" => 'required',
            "p4" => 'required',                        
        ]);


        try{
            DB::beginTransaction();
            EncuestaSiibien2025::create([
                'p1' => $request->p1,
                'p2' => $request->p2,
                'p3' => $request->p3,
                'p4' => $request->p4,
                'p5' => $request->p5,                
            ]);
            $resultado=true;
            DB::commit();
        }catch(Exception $ex){
            dd($ex);
            $resultado=false;
            DB::rollback();
        }
        return view('temporal.resulencuesta2025')->with("resultado",$resultado);
    }
    public function downloadpdf() //Funcion para la generación del reporte de productos sectoriales
    {
        // Crear una nueva instancia de TCPDF
        $pdf = new TCPDF();

        // Configuración de márgenes
        $pdf->SetMargins(15, 5, 15);  // Márgenes izquierdo, superior, derecho
        $pdf->SetAutoPageBreak(true, 5);  // Habilitar el salto de página automático con 15 mm de margen inferior
        $pdf->setPrintHeader(True);  // Desactiva el encabezado
        $pdf->setPrintFooter(false);  // Desactiva el pie de página

        // Agregar una página
        $pdf->AddPage();

        // Agregar la imagen del encabezado
        $pdf->Image(public_path('images/encabezado-pdf.png'), 15, 5, 180); // Ajusta el tamaño según sea necesario

        // Establecer la fuente
        $pdf->SetFont('helvetica', '', 12);

        // HTML del contenido
        $html = view('ia.reporteproductosectorial')->render();  // Usando Blade para cargar el HTML

        // Verificar si hay contenido para evitar crear una página vacía
        if (!empty($html)) {
            // Agregar el HTML al PDF solo si no está vacío
            $pdf->writeHTML($html, true, false, true, false, '');
        }

        // Descargar el archivo PDF
        $pdf->Output('Ficha Tecnica Del Indicador.pdf', 'D');
    }

    public function verItarReporteAnual(Request $request)
    {
        $pdf = new CustomPDF(); // Crear una nueva instancia de TCPDF
        $pdf->SetMargins(15, 32, 15);  // Márgenes izquierdo, superior (ajustado para espacio de encabezado), derecho
        $pdf->SetAutoPageBreak(true, 18);  // Habilitar el salto de página automático con 15 mm de margen inferior
        $pdf->setPrintFooter(false);  // Desactiva el pie de página
        $pdf->AddPage('L', array(400, 285)); // Agregar una página
        $pdf->SetFont('helvetica', '', 12); // Establecer la fuente
        $anio = $request->anio;
        $idPPA = $request->idPPA;
        $infoPPA = InformeAccion::where("id",$idPPA)
                    ->join("dependencia","dependencia.idDependencia","=","informe_acciones.idDependencia")
                    ->first();
        $presupuesto = IAPresupuestoTipoG::select("ia_presupuesto_tipog.*","programa_presupuestario.*")->join("ia_presupuesto_general","ia_presupuesto_general.id","=","ia_presupuesto_tipog.ia_presupuesto_general_id")
                                            ->where("ia_presupuesto_general.anio",$request->anio)
                                            ->where("ia_presupuesto_general.ia_id",$request->idPPA)
                                            ->leftjoin("programa_presupuestario","programa_presupuestario.idPrograma","=","ia_presupuesto_tipog.pp_id")
                                            ->get();        

        //dd($presupuesto);  
        $poblacion = IAPoblacion::where("ia_id",$request->idPPA)
                    ->leftjoin("itar_poblacion","itar_poblacion.id","=","tipo_poblacion_id")
                    ->first();      
        $infoP = null;
        if($poblacion !=null ){
            $infoP = IAPoblacionAnual::where("idPoblacion","=",$poblacion->idPoblacion)->where("anio","=",$request->anio)->first();
        }
        
        $bss = IABS::where("ia_id",$request->idPPA)->get();
        $medios1 = IAMedio::where("ia_id",$request->idPPA)->where("anio",$request->anio,)->where("trimestre","1")->get();
        $medios2 = IAMedio::where("ia_id",$request->idPPA)->where("anio",$request->anio,)->where("trimestre","2")->get();
        $medios3 = IAMedio::where("ia_id",$request->idPPA)->where("anio",$request->anio,)->where("trimestre","3")->get();
        $medios4 = IAMedio::where("ia_id",$request->idPPA)->where("anio",$request->anio,)->where("trimestre","4")->get();

        $obs1 = IAObservacion::where("ia_id",$request->idPPA)->where("anio",$request->anio,)->where("trimestre","1")->first();
        $obs2 = IAObservacion::where("ia_id",$request->idPPA)->where("anio",$request->anio,)->where("trimestre","2")->first();
        $obs3 = IAObservacion::where("ia_id",$request->idPPA)->where("anio",$request->anio,)->where("trimestre","3")->first();
        $obs4 = IAObservacion::where("ia_id",$request->idPPA)->where("anio",$request->anio,)->where("trimestre","4")->first();

        $titular  = Titular::where("idDependencia",$infoPPA->idDependencia)->where("status",1)->first();
        $enlaceDirectivo = EnlaceDependencia::where("idDependencia",$infoPPA->idDependencia)->where("tipoEnlace","Directivo")->where("status",1)->first();
        $enlaceOperativo = EnlaceDependencia::where("idDependencia",$infoPPA->idDependencia)->where("tipoEnlace","operativo")->where("status",1)->first();        

        $alineacion = IAAlineacion::where("ia_id",$request->idPPA)
                    ->leftjoin("ejeped","ejeped.idEjePED","=","ia_alineacion.idEjePED")
                    ->leftjoin("temaped","temaped.idTemaPED","=","ia_alineacion.idTemaPED")
                    ->leftjoin("objetivoped","objetivoped.idObjetivoPED","=","ia_alineacion.idObjetivoPED")
                    ->leftjoin("sectores","sectores.idSector","=","ia_alineacion.idSector")
                    ->leftjoin("objetivosector","objetivosector.idObjetivo","=","ia_alineacion.idObjetivoSector")
                    ->leftjoin("estrategiasector","estrategiasector.idEstrategia","=","ia_alineacion.idEstrategiaSector")
                    ->first();  

        $regiones = Region::all();        
        

        $html = view('ia.itar-reporte-anual')
                ->with("anio",$anio)
                ->with("presupuesto",$presupuesto)
                ->with("poblacion",$poblacion)
                ->with("infoP",$infoP)
                ->with("bss",$bss)
                ->with("medios1",$medios1)
                ->with("medios2",$medios2)
                ->with("medios3",$medios3)
                ->with("medios4",$medios4)
                ->with("obs1",$obs1)
                ->with("obs2",$obs2)
                ->with("obs3",$obs3)
                ->with("obs4",$obs4)
                ->with("idPPA",$idPPA)
                ->with("infoPPA",$infoPPA) 
                ->with("alineacion",$alineacion)
                ->with("regiones",$regiones) 
                ->with("titular",$titular)
                ->with("enlaceD",$enlaceDirectivo)
                ->with("enlaceO",$enlaceOperativo)   
                ->render(); // Usando Blade para cargar el HTML
        if (!empty($html)) {
            $pdf->writeHTML($html, true, false, true, false, ''); // Agregar el HTML al PDF solo si no está vacío
        }
        //$pdf->AddPage('L', array(400, 280)); // Agregar una segunda página
        // Después de agregar una nueva página, garantizamos que el contenido no se superponga al encabezado
        $pdf->Ln(25); // Agregar espacio después del encabezado
        $pdf->Output('itar-reporte-anual'.$anio.'.pdf', 'I'); // Descargar el archivo PDF
    }

    public function verItarTrimestral(Request $request)
    {


        $anio = $request->anio;
        $idPPA = $request->idPPA;
        $trimestre = $request->trim;
        $trimestres = ["Enero-Marzo","Abril-Junio","Julio-Septiembre","Octubre-Diciembre"];

        $infoPPA = InformeAccion::where("id",$idPPA)
                    ->join("dependencia","dependencia.idDependencia","=","informe_acciones.idDependencia")
                    ->first();
        
        $alineacion = IAAlineacion::where("ia_id",$request->idPPA)
            ->leftjoin("ejeped","ejeped.idEjePED","=","ia_alineacion.idEjePED")
            ->leftjoin("temaped","temaped.idTemaPED","=","ia_alineacion.idTemaPED")
            ->leftjoin("objetivoped","objetivoped.idObjetivoPED","=","ia_alineacion.idObjetivoPED")
            ->leftjoin("sectores","sectores.idSector","=","ia_alineacion.idSector")
            ->leftjoin("objetivosector","objetivosector.idObjetivo","=","ia_alineacion.idObjetivoSector")
            ->leftjoin("estrategiasector","estrategiasector.idEstrategia","=","ia_alineacion.idEstrategiaSector")
            ->first();  
        
        $presupuesto = IAPresupuestoTipoG::select("ia_presupuesto_tipog.*","programa_presupuestario.*")->join("ia_presupuesto_general","ia_presupuesto_general.id","=","ia_presupuesto_tipog.ia_presupuesto_general_id")
            ->where("ia_presupuesto_general.anio",$request->anio)
            ->where("ia_presupuesto_general.ia_id",$request->idPPA)
            ->leftjoin("programa_presupuestario","programa_presupuestario.idPrograma","=","ia_presupuesto_tipog.pp_id")
            ->get(); 
        
        $poblacion = IAPoblacion::where("ia_id",$request->idPPA)
            ->leftjoin("itar_poblacion","itar_poblacion.id","=","tipo_poblacion_id")
            ->first();      

        $infoP = null;
        if($poblacion !=null ){
            $infoP = IAPoblacionAnual::where("idPoblacion","=",$poblacion->idPoblacion)->where("anio","=",$request->anio)->first();
        }

        $bss = IABS::where("ia_id",$request->idPPA)->get();

        $titular  = Titular::where("idDependencia",$infoPPA->idDependencia)->where("status",1)->first();
        $enlaceDirectivo = EnlaceDependencia::where("idDependencia",$infoPPA->idDependencia)->where("tipoEnlace","Directivo")->where("status",1)->first();
        $enlaceOperativo = EnlaceDependencia::where("idDependencia",$infoPPA->idDependencia)->where("tipoEnlace","operativo")->where("status",1)->first();        
        
        $medios = IAMedio::where("ia_id",$request->idPPA)->where("anio",$request->anio,)->where("trimestre",$trimestre)->get();
        //dd($medios);
        $pdf = new CustomPDF(); // Crear una nueva instancia de TCPDF
        $pdf->SetMargins(15, 32, 15);  // Márgenes izquierdo, superior (ajustado para espacio de encabezado), derecho
        $pdf->SetAutoPageBreak(true, 18);  // Habilitar el salto de página automático con 15 mm de margen inferior
        $pdf->setPrintFooter(false);  // Desactiva el pie de página
        $pdf->AddPage('L', array(400, 320)); // Agregar una página
        $pdf->SetFont('helvetica', '', 12); // Establecer la fuente
        $html = view('ia.itar-trimestral')
                ->with("anio",$anio)  
                ->with("idPPA",$idPPA)     
                ->with("trim",$trimestre)     
                ->with("infoPPA",$infoPPA) 
                ->with("trimestres",$trimestres)
                ->with("alineacion",$alineacion)
                ->with("presupuesto",$presupuesto)
                ->with("poblacion",$poblacion)
                ->with("infoP",$infoP)
                ->with("bss",$bss)
                ->with("titular",$titular)
                ->with("enlaceD",$enlaceDirectivo)
                ->with("enlaceO",$enlaceOperativo)   
                ->with("medios",$medios)   
                ->render(); // Usando Blade para cargar el HTML        
        if (!empty($html)) {
            $pdf->writeHTML($html, true, false, true, false, ''); // Agregar el HTML al PDF solo si no está vacío
        }
       
        $pdf->Ln(25); // Agregar espacio después del encabezado
        $pdf->Output('itar-trimestral.pdf', 'I'); // Descargar el archivo PDF
    }
        //Nuevo
    public function nuevoRegistro(Request $request)
    {
        $request->validate([
            "tipo_enlace" => 'required|string|max:255',
            "nombre" => 'required|string|max:255',
            "dependencia" => 'required|integer|exists:dependencia,idDependencia',
            "cargo" => 'required|string|max:255',
            "perfil" => 'required|string|max:255',
            "email" => 'required|email|max:255',
            "telefono" => 'required|string|max:50',
        ]);

        try {
            $email = mb_strtolower($request->email);

            //  "Mismo nombre + misma dependencia = registro existe"
                 
            $nombreNormalizado = $this->normalizarTexto($request->nombre);

            $existeMismoNombre = Registro::where('idDependencia', (int) $request->dependencia)
                ->whereRaw("
                LOWER(
                  REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(nombre,'á','a'),'é','e'),'í','i'),'ó','o'),'ú','u'),'ü','u')
                ) = ?
            ", [$nombreNormalizado])
                ->exists();

            if ($existeMismoNombre) {
                return back()->withInput()->withErrors([
                    'nombre' => 'Ya existe un registro con este nombre para la institución seleccionada.',
                ]);
            }

            // "Mismo email + misma dependencia = ACTUALIZA"       
            // Buscamos por (email, dependencia). Si existe, es edición.     
            // Si NO existe, pasamos a crear            
            $registro = Registro::firstOrNew([
                'email' => $email,
                'idDependencia' => (int) $request->dependencia,
            ]);

            // Si no existía, es un alta. Si existía, es actualización.
            $esNuevo = !$registro->exists;

            // (Sólo en altas) Generamos un QR único por persona             
            if ($esNuevo) {
                $registro->qr_uuid = (string) Str::uuid();
            }

            // Campos comunes (se actualizan o se establecen en altas)
            $registro->nombre = $request->nombre;
            $registro->cargo = $request->cargo;
            $registro->telefono = $request->telefono;
            $registro->perfil = $request->perfil;
            $registro->tipo_enlace = $request->tipo_enlace;

            // Guarda (crea o actualiza según corresponda)
            $registro->saveOrFail();

            //  "Nombre nuevo + misma dependencia + email distinto"  
            //           => CREA NUEVO (esto ya ocurrió cuando $esNuevo=true)
            //           y por eso generamos el QR sólo en este caso.        
            $qr_svg = null;
            if ($esNuevo) {

                $qr_svg = QrCode::format('svg')
                    ->size(380)
                    ->margin(4)
                    ->errorCorrection('M')
                    ->color(0, 0, 0)
                    ->backgroundColor(255, 255, 255)
                    ->generate($registro->qr_uuid);
            }

            return view('eventos.resultadoRegistro', [
                'resultado' => true,
                'nombre' => $registro->nombre,
                'esNuevo' => $esNuevo,
                'qr_svg' => $qr_svg,
            ]);

        } catch (\Throwable $ex) {
            // Si algo inesperado ocurre, mostramos una vista amable
            return view('eventos.resultadoRegistro', [
                'resultado' => false,
                'nombre' => '',
                'esNuevo' => null,
                'qr_svg' => null,
            ]);
        }
    }

    //  normalizar el texto (acentos y minúsculas)
    private function normalizarTexto(string $texto): string
    {
        $texto = mb_strtolower($texto);
        $texto = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ü'], ['a', 'e', 'i', 'o', 'u', 'u'], $texto);
        $texto = preg_replace('/\s+/', ' ', trim($texto));
        return $texto;
    }
}
// Clase CustomPDF
class CustomPDF extends TCPDF {
    // Sobrescribir el método Header para agregar el encabezado en todas las páginas
    public function Header() {
        $anchoPagina = $this->getPageWidth();
        // Ajusta la imagen del encabezado a las dimensiones de la página
        $this->Image(public_path('images/encabezado-H.png'), 5, 5, $anchoPagina); 
        // Agregar espacio después de la imagen
        $this->Ln(35); // Espacio de 25mm después del encabezado
    }
}