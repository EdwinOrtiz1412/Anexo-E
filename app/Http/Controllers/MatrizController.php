<?php

namespace App\Http\Controllers;

use App\Models\Dependencia;
use App\Models\MatrizCoordinacion;
use App\Models\TemaPED;
use Illuminate\Http\Request;

class MatrizController extends Controller
{
    //
    public function index()
    {
        $dependencias = Dependencia::all();
        $temas = TemaPED::all();
        return view("informe.matriz")->with("dependencias", $dependencias)->with("temas_base",$temas->toArray());
    }

    public function uptroltema(Request $request)
    {
        $dependencia = $request->dependencia;
        $tema = $request->tema;
        $rol = $request->rol;

        $relacion = MatrizCoordinacion::where("dependencias_id", $dependencia)->where("idTemaPED", $tema)->where("informe","2")->first();
        try {
            if ($relacion == null) {
                if($rol=="CT"){
                    //buscamos si ya existe una coordinadora de tema para el tema en cuestion
                    $ct = MatrizCoordinacion::where("idTemaPED", $tema)->where("informe","2")->where("tipo","CT")->first();
                    if($ct!=null){
                        return response()->json([
                            "result" => "error",
                            "rol" => null,
                            "message" => "Ya existe una Coordinadora para este tema"
                        ]);
                    }else{
                        MatrizCoordinacion::create([
                            "dependencias_id"=>$dependencia,
                            "informe"=>'2',
                            "idTemaPED"=>$tema,
                            "tipo"=>$rol
                        ]);
                    }
                }else{
                    MatrizCoordinacion::create([
                        "dependencias_id"=>$dependencia,
                        "informe"=>'2',
                        "idTemaPED"=>$tema,
                        "tipo"=>$rol
                    ]);
                }
            } else {
                if ($rol == "") {
                    MatrizCoordinacion::where("dependencias_id", $dependencia)->where("idTemaPED", $tema)->where("informe","2")->delete();
                } else {

                    if($rol=="CT"){
                        //buscamos si ya existe una coordinadora de tema para el tema en cuestion
                        $ct = MatrizCoordinacion::where("idTemaPED", $tema)->where("informe","2")->where("tipo","CT")->first();
                        if($ct!=null){
                            return response()->json([
                                "result" => "error",
                                "rol" => null,
                                "message" => "Ya existe una Coordinadora para este tema"
                            ]);
                        }else{
                            MatrizCoordinacion::where("dependencias_id", $dependencia)->where("idTemaPED", $tema)->where("informe","2")->update([
                                "tipo"=>$rol
                            ]);

                        }
                    }else{
                        //dd($relacion->count());
                        MatrizCoordinacion::where("dependencias_id", $dependencia)->where("idTemaPED", $tema)->where("informe","2")->update([
                            "tipo"=>$rol
                        ]);
                    }


                }
            }
            return response()->json([
                "result" => "ok",
                "rol" => $rol,
            ]);
        } catch (Exception $ex) {
            return response()->json([
                "result" => "error",
                "rol" => null,
                "message" => "Ocurrió un error al actualizar la relación"
            ]);
        }


        //Validaciones



    }
}
