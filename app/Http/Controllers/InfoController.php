<?php

namespace App\Http\Controllers;

use App\Models\MetasODS;
use App\Models\ObjetivoODS;
use Illuminate\Http\Request;

class InfoController extends Controller
{
    //
    public function a2030()
    {
        return view("info.a2030");
    }

    public function infoods(Request $req)
    {
        $colors = ["EA3937","DAA43A","459A4A","D13437","EB5036","4EADD7","F4BB40","982539","ED7635","EA3687","F2A33B","D89338","3C783F","367CBB","4FAF4E","22538A","123266"];
        $descods = [
            "Fin de la Pobreza",
            "Hambre Cero",
            "Salud y Bienestar",
            "Educación de Calidad",
            "Igualdad de Género",
            "Agua Limpia y Saneamiento",
            "Energía Asequible y no Contaminante",
            "Trabajo Decente y Crecimiento Económico",
            "Industria, Innovación e Infraestructura",
            "Reducción de las Desigualdades",
            "Ciudades y Comunidades Sostenibles",
            "Producción y Consumo Responsables",
            "Acción por el Clima",
            "Vida Submarina",
            "Vida de Ecosistemas Terrestres",
            "Paz, Justicia e Instituciones Sólidas",
            "Alianzas para Lograr los Objetivos",
        ];
        $ods = $req->ods_id;
        $color = $colors[$ods-1];
        $odsdesc = $descods[$ods-1];

        $odsinfo = ObjetivoODS::where("id", $ods)->first();
        $metasods = MetasODS::where("objetivos_ods_id", $ods)->get();
        return view("info.infoods")->with("ods", $odsinfo)->with("metas", $metasods)->with("color",$color)->with("odsdesc",$odsdesc);
    }

    public function ped(){
        return view("info.ped");
    }

    public function pes(){
        return view("info.pes");
    }
}
