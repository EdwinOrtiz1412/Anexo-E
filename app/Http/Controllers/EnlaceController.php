<?php

namespace App\Http\Controllers;

use App\Exports\EnlacesExport;
use Illuminate\Http\Request;
use App\Models\EnlaceDependencia;
use App\Models\Dependencia;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Http\Utils\ReportePDF;
use Excel;
use App\Models\Notificaciones;
use App\Models\NotificacionUsuario;
use Maatwebsite\Excel\Excel as ExcelExcel;
use Maatwebsite\Excel\Facades\Excel as FacadesExcel;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as ReaderXlsx;


class EnlaceController extends Controller
{
    public function list()
    {
        //$dependencias = Dependencia::leftJoin("titulares","titulares.idDependencia","=","dependencia.idDependencia")->get();
        $enlaces = EnlaceDependencia::where("enlacedependencia.status", 1)
            ->join("dependencia", "enlacedependencia.idDependencia", "=", "dependencia.idDependencia")
            ->join("users", "users.idEnlaceDependencia", "=", "enlacedependencia.idEnlaceDependencia")
            ->select('enlacedependencia.*', 'dependencia.*', 'users.*', 'users.status as statusUser')
            ->get();
        $dependencias = Dependencia::where("status", 1)->get();
        return view("super.enlaces")->with("enlaces", $enlaces)->with("dependencias", $dependencias);
    }

    public function save(Request $req)
    {
        try {
            if ($req->idEnlaceDependencia == "") {

                //nuevo enlace
                DB::beginTransaction();
                try {
                    $enlace = new EnlaceDependencia();
                    $enlace->titulo = $req->titulo;
                    $enlace->nombre = $req->nombre;
                    $enlace->apellidoP = $req->apellidoP;
                    $enlace->apellidoM = $req->apellidoM;
                    $enlace->cargo = $req->cargo;
                    $enlace->tipoEnlace  = $req->tipoEnlace;
                    $enlace->email = $req->email;
                    $enlace->telefono = $req->telefono;
                    $enlace->celular = $req->celular;
                    $enlace->teloficina = $req->teloficina;
                    $enlace->extension = $req->extension;
                    $enlace->idDependencia = $req->idDependencia;
                    $enlace->oficioSolicitud = $req->oficioSolicitud;
                    $enlace->fechaAcuse = $req->fechaAcuse;
                    $enlace->oficioDesignacion = $req->oficioDesignacion;
                    $enlace->fechaRecepcion = $req->fechaRecepcion;
                    $enlace->observaciones = $req->observaciones;
                    $enlace->save();
                    // si todo OK con el enlace, generamos la cuenta de acceso
                    $dependencia = Dependencia::select("dependenciaSiglas")->where("idDependencia", $req->idDependencia)->first();
                    if (isset($dependencia->dependenciaSiglas)) {
                        $password = Str::random(10);
                        $cuenta = "SIIBIEN." . $dependencia->dependenciaSiglas;

                        //validamos si la cuenta ya existe y si es así: iteramos 5 veces para generar la cuenta consecutiva igualmente no ocupada.
                        if($this->cuentavalida($cuenta)){
                            for($x=1;$x<=5;$x++){
                                if(!$this->cuentavalida($cuenta.$x)){
                                    $cuenta=$cuenta.$x;
                                    break;
                                }
                            }
                        }

                        $user = new User();
                        $user->cuenta = $cuenta;
                        $user->name = $req->nombre . " " . $req->apellidoP . " " . $req->apellidoM;
                        $user->password = Hash::make($password);
                        $user->enc = base64_encode($password);
                        $user->idEnlaceDependencia = $enlace->id;
                        $user->save();
                        $user->assignRole('enlace');
                    }

                    //Generamos la Notificación para el nuevo enlace
                    $notificacion = new Notificaciones();
                    $notificacion->tipo = "automatica";
                    $notificacion->descripcion = "Puede descargar su responsiva de usuario en el Siguiente enlace <center><form action='perfil/responsiva' method='GET' target='_blank'><button type='submit' class='btn btn-success'><i class='fas fa-download'></i></button></form></center>";
                    $notificacion->save();

                    //Procedemos asignar la notificacion al usuario
                    $not_user =  new NotificacionUsuario();
                    $not_user->idUser = $user->id;
                    $not_user->idNotificacion = $notificacion->id;
                    $not_user->save();


                    $message = "Enlace almacenado satisfactoriamente!";
                    $icon = "ok";
                    DB::commit();
                } catch (Exception $ex) {
                    $message = "Ocurrió un error durante el Almacenamiento del Enlace, favor de intentar más tarde!" . $ex;
                    $icon = "error";
                    DB::rollBack();
                }
            } else {
                //actualizacion del enlace
                DB::beginTransaction();
                $enlace = EnlaceDependencia::where("status", 1)->where("idEnlaceDependencia", $req->idEnlaceDependencia)->update(
                    [
                        "titulo" => $req->titulo,
                        "nombre" => $req->nombre,
                        "apellidoP" => $req->apellidoP,
                        "apellidoM" => $req->apellidoM,
                        "cargo" => $req->cargo,
                        "tipoEnlace" => $req->tipoEnlace,
                        "email" => $req->email,
                        "telefono" => $req->telefono,
                        "celular" => $req->celular,
                        "teloficina" => $req->teloficina,
                        "extension" => $req->extension,
                        "idDependencia" => $req->idDependencia,
                        "oficioSolicitud" => $req->oficioSolicitud,
                        "fechaAcuse" => $req->fechaAcuse,
                        "oficioDesignacion" => $req->oficioDesignacion,
                        "fechaRecepcion" => $req->fechaRecepcion,
                        "observaciones" => $req->observaciones,
                    ]
                );

                //Actualizamos a su vez el nombre de la cuenta
                User::where("idEnlaceDependencia",$req->idEnlaceDependencia)->update([
                    "name" => $req->titulo." ".$req->nombre." ".$req->apellidoP." ".$req->apellidoM
                ]);
                DB::commit();

                $message = "Enlace actualizado satisfactoriamente!";
                $icon = "ok";
            }
            return response()->json([
                'success' => $icon,
                'message' => $message,
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => 'error',
                'message' => 'Ocurrió un error al procesar el enlace, Intente más tarde!',
            ], 500);
        }
    }

    public function savefromlayout(Request $req)
    {
        try {
                //nuevo enlace
                DB::beginTransaction();
                try {
                    $enlace = new EnlaceDependencia();
                    $enlace->titulo = $req->titulo;
                    $enlace->nombre = $req->nombre;
                    $enlace->apellidoP = $req->apellidoP;
                    $enlace->apellidoM = $req->apellidoM;
                    $enlace->cargo = $req->cargo;
                    $enlace->tipoEnlace  = $req->tipoEnlace;
                    $enlace->email = $req->email;
                    $enlace->telefono = $req->telefono;
                    $enlace->celular = $req->celular;
                    $enlace->teloficina = $req->teloficina;
                    $enlace->extension = $req->extension;
                    $enlace->idDependencia = $req->iddependencia;
                    $enlace->save();
                    // si todo OK con el enlace, generamos la cuenta de acceso
                    $dependencia = Dependencia::select("dependenciaSiglas")->where("idDependencia", $req->iddependencia)->first();
                    if (isset($dependencia->dependenciaSiglas)) {
                        $password = Str::random(10);
                        $cuenta = "SIIBIEN." . $dependencia->dependenciaSiglas;

                        if($this->cuentavalida($cuenta)){
                            for($x=1;$x<=5;$x++){
                                if(!$this->cuentavalida($cuenta.$x)){
                                    $cuenta=$cuenta.$x;
                                    break;
                                }
                            }
                        }

                        $user = new User();
                        $user->cuenta = $cuenta;
                        $user->name = $req->nombre . " " . $req->apellidoP . " " . $req->apellidoM;
                        $user->password = Hash::make($password);
                        $user->enc = base64_encode($password);
                        $user->idEnlaceDependencia = $enlace->id;
                        $user->save();
                        $user->assignRole('enlace');
                    }

                    //Generamos la Notificación para el nuevo enlace
                    $notificacion = new Notificaciones();
                    $notificacion->tipo = "automatica";
                    $notificacion->descripcion = "Puede descargar su responsiva de usuario en el Siguiente enlace <center><form action='perfil/responsiva' method='GET' target='_blank'><button type='submit' class='btn btn-success'><i class='fas fa-download'></i></button></form></center>";
                    $notificacion->save();

                    //Procedemos asignar la notificacion al usuario
                    $not_user =  new NotificacionUsuario();
                    $not_user->idUser = $user->id;
                    $not_user->idNotificacion = $notificacion->id;
                    $not_user->save();
                    DB::commit();
                    return true;
                } catch (Exception $ex) {
                    dd($ex);
                    return false;
                    DB::rollBack();
                }

        } catch (Exception $e) {
            return false;
        }
    }

    public function delete(Request $req)
    {
        try {
            $baja = EnlaceDependencia::where("idEnlaceDependencia", $req->idEnlaceDependencia)->update([
                "status" => false
            ]);
            return response()->json([
                'success' => 'ok',
                'message' => 'Enlace dada de baja Satisfactoriamente!',
            ], 200);
        } catch (Exception $ex) {
            return response()->json([
                'success' => 'error',
                'message' => 'Ocurrió un error al dar de baja al enlace!',
            ], 200);
        }
    }

    public function user(Request $req)
    {
        $usuario = User::where("id", $req->idUser)->first();
        $roles = $usuario->getRoleNames();
        if (count($roles) == 0) {
            $usuario->assignRole('enlace');
        }
        if (!empty($usuario)) {
            return response()->json([
                'success' => 'ok',
                'usuario' => $usuario,
                'rol' => $usuario->hasRole('enlace') ? "1" : "2"
            ], 200);
        } else {
            return response()->json([
                'success' => 'empty',
                'usuario' => null,
            ], 200);
        }
    }

    public function usersave(Request $req)
    {
        try {
            if ($req->cambia != "") {
                $actualiza = User::where("id", $req->idUser)->update([
                    "name" => $req->name,
                    "cuenta" => $req->cuenta,
                    "password" => Hash::make($req->cambia),
                    "enc" => base64_encode($req->cambia),
                    "status" => $req->status == 'on' ? true : false
                ]);
            } else {
                $actualiza = User::where("id", $req->idUser)->update([
                    "name" => $req->name,
                    "cuenta" => $req->cuenta,
                    "status" => $req->status == 'on' ? true : false
                ]);
            }
            $user = User::find($req->idUser);
            $user->roles()->sync([$req->rol]);
            return response()->json([
                'success' => 'ok',
                'message' => 'Usuario actualizado Satisfactoriamente',
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'success' => 'error',
                'message' => 'ocurrio un error al almacenar al usuario, intente más tarde!',
            ]);
        }
    }

    public function updatestatususer(Request $req)
    {
        try {
            $actualiza = User::where("id", $req->idUser)->update([
                "status" => $req->status
            ]);
            return response()->json([
                'success' => 'ok',
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'success' => 'error',
            ]);
        }
    }

    public function downloadenlaces()
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
            $pdf->Cell(10, 15, 'Listado de Enlaces', 0, false, 'L', 0, '', 0, false, 'M', 'M');
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
        ReportePDF::SetTitle('Instancia Técnica de Evaluación - SIIBien Listado de Enlaces');
        ReportePDF::SetMargins(10, 23, 10);
        //ReportePDF::SetHeaderMargin(25);
        ReportePDF::AddPage('L', 'P');

        //Enlace
        $enlaces = EnlaceDependencia::where("enlacedependencia.status", 1)
            ->join("dependencia", "enlacedependencia.idDependencia", "=", "dependencia.idDependencia")
            ->join("users", "users.idEnlaceDependencia", "=", "enlacedependencia.idEnlaceDependencia")
            ->select('enlacedependencia.*', 'dependencia.*', 'users.*', 'users.status as statusUser')
            ->get();

        $html = \View::make("super.downloadenlaces")->with("enlaces", $enlaces);

        ReportePDF::writeHTML($html, true, false, true, false, '');

        ReportePDF::Output(public_path('listado_enlaces.pdf'), 'I');
    }

    public function downloadenlacesxls()
    {
        return Excel::download(new EnlacesExport, 'enlaces' . date('YmdHis') . '.xlsx');
    }

    public function downloadenlacescsv()
    {
        return Excel::download(new EnlacesExport, 'enlaces' . date('YmdHis') . '.csv', \Maatwebsite\Excel\Excel::CSV);
    }

    public function validalayout(Request $request)
    {
        $medio = $request->file('layout');
        $nombreMedio = time() . rand(1, 100) . '.' . $medio->extension();
        try {
            $path = public_path('private/temp/') . $nombreMedio;
            $medio->move(public_path('private/temp/'), $nombreMedio);
            $datas = new ReaderXlsx();
            //$spreadsheet = $datas->load($path);
            //$sheet = $spreadsheet->getActiveSheet();


            $worksheetInfo = $datas->listWorksheetInfo($path);
            $totalColumnas = $worksheetInfo[0]['totalColumns'];
            $letraFinalColumna = $worksheetInfo[0]['lastColumnLetter'];

            if ($totalColumnas == 12 && $letraFinalColumna == "L") {
                return response()->json([
                    'success' => 'ok',
                    'message' => 'Layout cargado satisfactoriamente',
                    'path' => $nombreMedio
                ], 200);
            } else {
                unlink($path);
                return response()->json([
                    'success' => 'error',
                    'message' => 'La plantilla cargada es incorrecta',
                ], 200);
            }
        } catch (Exception $ex) {
            return response()->json([
                'success' => 'error',
                'message' => 'Ocurrió un error al procesar la plantilla '.$ex,
                'path' => $nombreMedio
            ], 500);
        }
    }

    public function leelayout($layout)
    {
        $Dependencia = new Dependencia();
        $path = public_path('private/temp/') . $layout;
        $reader = new ReaderXlsx();
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();
        return view("super.leelayout")->with("sheet", $sheet)->with("Dependencia", $Dependencia);
    }

    public function enlaceupload(Request $request)
    {

        if($this->savefromlayout($request)){
            return response()->json([
                'success' => 'ok',
                'message' => 'Enlace cargado satisfactoriamente'
            ], 200);
        }else{
            return response()->json([
                'success' => 'error',
                'message' => 'Ocurrió un error al almacenar el enlace'
            ], 500);
        }


    }

    private function cuentavalida($cuenta){
        $existe = User::select("id")->where("cuenta",$cuenta)->first();
        if(isset($existe->id)){
            return true;
        }else
        {
            return false;
        }
    }

    public function updateestatuspermiso(Request $req)
    {
        try {
            $actualiza = User::where("id", $req->idUser)->update([
                $req->campo => $req->status=="true"?1:0
            ]);
            return response()->json([
                'success' => 'ok',
            ],200);
        } catch (Exception $ex) {
            return response()->json([
                'success' => 'error',
                'error' => $ex
            ],500);
        }
    }
}
