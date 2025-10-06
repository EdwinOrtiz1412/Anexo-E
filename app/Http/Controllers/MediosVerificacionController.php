<?php

namespace App\Http\Controllers;

use App\Models\MediosIndicador;
use App\Models\MediosVariable;
use Exception;
use Faker\Provider\Medical;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MediosVerificacionController extends Controller
{
    public function storevalindicador(Request $req): JsonResponse
    {

        try {
            $idIndicador = $req->idIndicadorF;
            $idValores = $req->idValoresIndicadorF;
            $medio = $req->file('file');
            $nombreMedio = time() . rand(1, 100) . '.' . $medio->extension();
            $medio->move(public_path('medios/' . $idIndicador . "/" . $idValores), $nombreMedio);
            DB::beginTransaction();
            $medioindicador = new MediosIndicador();
            $medioindicador->archivo = $medio->getClientOriginalName();
            $medioindicador->filename = $nombreMedio;
            $medioindicador->idValoresIndicador = $idValores;
            $medioindicador->save();
            DB::commit();
            return response()->json([
                'success' => 'ok',
                'message' => 'Medio cargado Satisfactoriamente!',
                'idMedio' => $medioindicador->id,
                'filename' => $nombreMedio
            ]);
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json([
                'success' => 'error',
                'message' => 'Ocurrió un error al cargar el medio!',
            ]);
        }
    }

    public function getmediosbyindicador(Request $req)
    {
        $medios = MediosIndicador::where("idValoresIndicador", $req->idValoresIndicador)->get();
        return response()->json([
            'success' => 'ok',
            'medios' => $medios
        ]);
    }

    public function deletemedio(Request $req)
    {
        try {
            DB::beginTransaction();
            $filename = MediosIndicador::select("filename")->where("idMedio", $req->idMedioIndicador)->first();
            if (file_exists('medios/' . $req->idIndicador . "/" . $req->idValoresIndicador . "/" . $filename->filename))
                unlink(public_path('medios/' . $req->idIndicador . "/" . $req->idValoresIndicador . "/" . $filename->filename));
            MediosIndicador::where("idMedio", $req->idMedioIndicador)->delete();
            DB::commit();
            return response()->json([
                'success' => 'ok',
                'message' => 'El medio ha sido eliminado Satisfactoriamente!'
            ]);
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json([
                'success' => 'error',
                'message' => 'El medio no ha sido eliminado, intente más tarde!'
            ]);
        }
    }

    public function storevalvariable(Request $req): JsonResponse
    {

        try {
            $idIndicador = $req->idIndicadorFV;
            $idVariable = $req->idVariableF;
            $idValores = $req->idValoresVariableF;
            $medio = $req->file('file');
            $nombreMedio = time() . rand(1, 100) . '.' . $medio->extension();
            $medio->move(public_path('medios/' . $idIndicador . "/variables/" . $idVariable . "/" . $idValores), $nombreMedio);
            DB::beginTransaction();
            $mediovariable = new MediosVariable();
            $mediovariable->archivo = $medio->getClientOriginalName();
            $mediovariable->filename = $nombreMedio;
            $mediovariable->idValoresVariable = $idValores;
            $mediovariable->save();
            DB::commit();
            return response()->json([
                'success' => 'ok',
                'message' => 'Medio cargado Satisfactoriamente!',
                'idMedio' => $mediovariable->id,
                'filename' => $nombreMedio
            ]);
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json([
                'success' => 'error',
                'message' => 'Ocurrió un error al cargar el medio!',
            ]);
        }
    }

    public function getmediosbyvariable(Request $req)
    {
        $medios = MediosVariable::where("idValoresVariable", $req->idValoresVariable)->get();
        return response()->json([
            'success' => 'ok',
            'medios' => $medios
        ]);
    }

    public function deletemediovariable(Request $req)
    {
        try {
            DB::beginTransaction();
            $filename = MediosVariable::select("filename")->where("idMedio", $req->idMedioVariable)->first();
            unlink(public_path('medios/' . $req->idIndicador . "/variables/" . $req->idVariable . "/" . $req->idValoresVariable . "/" . $filename->filename));
            MediosVariable::where("idMedio", $req->idMedioVariable)->delete();
            DB::commit();
            return response()->json([
                'success' => 'ok',
                'message' => 'El medio ha sido eliminado Satisfactoriamente!'
            ]);
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json([
                'success' => 'error',
                'message' => 'El medio no ha sido eliminado, intente más tarde!' . $ex
            ]);
        }
    }
}
