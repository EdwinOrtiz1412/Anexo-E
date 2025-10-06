<?php

namespace App\Http\Controllers;

use App\Models\MediosVariable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\Variable;
use App\Models\ValoresHistoricosVariable;
use App\Models\ValoresProgramadosVariable;
use Exception;

class VariableController extends Controller
{
    //
    public function addhistorico(Request $req): JsonResponse
    {

        DB::beginTransaction();
        try {
            if ($req->idValoresVariableHistorico == "") {
                $valorHistoricoVariable = new ValoresHistoricosVariable();
                $valorHistoricoVariable->valoresAnioMedicion =  $req->valoresVariableAnioMedicionHistorico;
                $valorHistoricoVariable->valoresCicloMedicion = $req->valoresVariableCicloMedicionHistorico;
                $valorHistoricoVariable->valoresHistorico = $req->valoresVariableValorHistorico;
                $valorHistoricoVariable->valoresEstatus = $req->valoresVariableEstatusHistorico;
                $valorHistoricoVariable->valoresObservaciones = $req->valoresVariableObservacionesHistorico;
                $valorHistoricoVariable->idVariable = $req->idVariable;
                $valorHistoricoVariable->save();
            } else {
                $valorHistoricoVariable = ValoresHistoricosVariable::where("idValores", $req->idValoresVariableHistorico)
                    ->update([
                        'valoresAnioMedicion' => $req->valoresVariableAnioMedicionHistorico,
                        'valoresCicloMedicion' => $req->valoresVariableCicloMedicionHistorico,
                        'valoresHistorico' => $req->valoresVariableValorHistorico,
                        'valoresEstatus' => $req->valoresVariableEstatusHistorico,
                        'valoresObservaciones' => $req->valoresVariableObservacionesHistorico,
                    ]);
            }

            DB::commit();

            if ($req->idValoresVariableHistorico == "")
                return response()->json([
                    'success' => 'ok',
                    'message' => 'El valor ha sido almacenado satisfactoriamente! ',
                    'id' => $valorHistoricoVariable->id
                ]);
            else
                return response()->json([
                    'success' => 'ok',
                    'message' => 'El valor ha sido actualizado de manera satisfactoria! ',
                    'id' => $req->idValoresVaiableHistorico
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
        $historicos = ValoresHistoricosVariable::where("idVariable", $req->idVariable)->get();
        return response()->json([
            'success' => 'ok',
            'message' => 'Historicos de la variable',
            'historicos' => $historicos
        ], 200);
    }

    public function deletevalorhistorico(Request $req)
    {

        try {
            DB::beginTransaction();
            ValoresHistoricosVariable::where("idValores", $req->idValoresVariableHistorico)->delete();
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
            if ($req->idValoresVariableProgramado == "") {
                $valorProgramadoVariable = new ValoresProgramadosVariable();
                $valorProgramadoVariable->valoresAnioMedicion =  $req->valoresVariableAnioMedicionProgramado;
                $valorProgramadoVariable->valoresCicloMedicion = $req->valoresVariableCicloMedicionProgramado;
                $valorProgramadoVariable->valoresProgramado = $req->valoresVariableProgramado;
                $valorProgramadoVariable->valoresEstatusP = $req->valoresVariableEstatusProgramado;
                $valorProgramadoVariable->valoresObservaciones = $req->valoresVariableObservacionesProgramado;
                $valorProgramadoVariable->idVariable = $req->idVariable;
                $valorProgramadoVariable->save();
            } else {
                $valorProgramadoVariable = ValoresProgramadosVariable::where("idValores", $req->idValoresVariableProgramado)
                    ->update([
                        'valoresAnioMedicion' => $req->valoresVariableAnioMedicionProgramado,
                        'valoresCicloMedicion' => $req->valoresVariableCicloMedicionProgramado,
                        'valoresProgramado' => $req->valoresVariableProgramado,
                        'valoresEstatusP' => $req->valoresVariableEstatusProgramado,
                        'valoresObservaciones' => $req->valoresVariableObservacionesProgramado,
                    ]);
            }

            DB::commit();

            if ($req->idValoresVariableProgramado == "")
                return response()->json([
                    'success' => 'ok',
                    'message' => 'La meta ha sido almacenada satisfactoriamente! ',
                    'id' => $valorProgramadoVariable->id
                ]);
            else
                return response()->json([
                    'success' => 'ok',
                    'message' => 'La meta ha sido actualizada de manera satisfactoria! ',
                    'id' => $req->idValoresVaiableProgramado
                ]);
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json([
                'success' => 'error',
                'message' => 'Ocurrió un error al Almacenar la meta, intente más tarde! ' . $ex
            ]);
        }
    }

    public function getprogramados(Request $req)
    {
        $programados = ValoresProgramadosVariable::where("idVariable", $req->idVariable)->get();
        return response()->json([
            'success' => 'ok',
            'message' => 'Metas de la variable',
            'programados' => $programados
        ], 200);
    }

    public function deletevalorprogramado(Request $req)
    {

        try {
            DB::beginTransaction();
            ValoresProgramadosVariable::where("idValores", $req->idValoresVariableProgramado)->delete();
            DB::commit();
            return response()->json([
                'success' => 'ok',
                'message' => 'La meta ha sido dado de baja Satisfactoriamente!'
            ]);
        } catch (Exception $ex) {
            DB::rollBack();
        }
    }

    public function updatemeta(Request $req)
    {
        DB::beginTransaction();
        try {

            $valorProgramadoVariable = ValoresProgramadosVariable::where("idValores", $req->idValoresVariableProgramado)
                ->update([                    
                    'valoresReal' => $req->valoresVariableMeta,
                    'valoresEstatus' => $req->valoresVariableEstatusMeta,
                    'valoresObservaciones' => $req->valoresVariableObservacionesProgramado,
                ]);
            DB::commit();
            //Actualizamos las observaciones de los medios cargados
            if(strlen($req->medios)>0){
                $medios = explode("|",$req->medios);
                $descripciones = explode("|",$req->descripciones);
                array_pop($medios);
                array_pop($descripciones);
                for($x=0;$x<count($medios);$x++){
                    MediosVariable::where("idMedio",$medios[$x])->update([
                        "descripcion" => $descripciones[$x]
                    ]);
                }
            }


            return response()->json([
                'success' => 'ok',
                'message' => 'La meta ha sido actualizada de manera satisfactoria! ',
                'id' => $req->idValoresVaiableProgramado
            ]);
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json([
                'success' => 'error',
                'message' => 'Ocurrió un error al Almacenar la meta, intente más tarde! ' . $ex
            ]);
        }
    }
}
