<?php

namespace App\Http\Controllers;

use App\Models\EstrategiaSector;
use App\Models\ObjetivoSector;
use App\Models\ProductoSector;
use Illuminate\Http\Request;

class SectorialController extends Controller
{
    public function  getobjetivossector(Request $req){
        $objetivos = ObjetivoSector::join("subsectores","subsectores.idSubsector","=","objetivosector.idSubsector")
                                        ->join("sectores","sectores.idSector","=","subsectores.idSector")
                                        ->where("sectores.idSector",$req->idSector)->get();
        return response()->json([
            'success' => 'ok',
            'objetivos' => $objetivos
        ]);
    }

    public function  getestrategiassector(Request $req){
        $estrategiasSector = EstrategiaSector::where("idObjetivo",$req->idObjetivoSector)->get();
        return response()->json([
            'success' => 'ok',
            'estrategias' => $estrategiasSector
        ]);
    }

    public function  getproductossector(Request $req){
        $productosSector = ProductoSector::where("idEstrategia",$req->idEstrategiaSector)->get();
        return response()->json([
            'success' => 'ok',
            'productos' => $productosSector
        ]);
    }
}
