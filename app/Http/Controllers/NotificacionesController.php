<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notificaciones;
use App\Models\EnlaceDependencia;
use App\Models\NotificacionUsuario;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificacionesController extends Controller
{
    public function index()
    {
        $notificaciones = Notificaciones::where("status", 1)->get();
        $users = EnlaceDependencia::where("enlacedependencia.status", 1)
            ->join("dependencia", "enlacedependencia.idDependencia", "=", "dependencia.idDependencia")
            ->join("users", "users.idEnlaceDependencia", "=", "enlacedependencia.idEnlaceDependencia")
            ->select('enlacedependencia.*', 'dependencia.*', 'users.*', 'users.status as statusUser')
            ->get();
        return view("super.notificaciones")->with('notificaciones', $notificaciones)->with('usuarios', $users);
    }

    public function save(Request $req)
    {


        try {
            DB::beginTransaction();
            //Procedemos a almacenar la notificación
            $notificacion = new Notificaciones();
            $notificacion->tipo = $req->tipo;
            $notificacion->descripcion = $req->descripcion;
            $notificacion->save();

            //Procedemos asignar la notificacion a los usuarios
            $idNotificacion = $notificacion->id;            
            foreach($req->user as $user){
                $not_user =  new NotificacionUsuario();
                $not_user->idUser = $user;
                $not_user->idNotificacion = $idNotificacion;
                $not_user->save();
            }

            DB::commit();
            return response()->json([
                'success' => 'ok',
                'message' => 'La notificación ha sido Almacenada Satisfactoriamente y se notificaron a los usuarios seleccionados',
            ], 200);
        } catch (Exception $ex) {
            DB::rollBack();
            return response()->json([
                'success' => 'error',
                'message' => 'Ocurrió un error al Registrar la notificación'.$ex,
            ], 200);
        }
    }

    public function getnotificaciones(Request $req){
        $notificaciones = NotificacionUsuario::where("idUser",$req->idUser)->where("visto",0)->where("notificaciones.status",1)->join("notificaciones","notificaciones.idNotificacion","=","notificacion_usuario.idNotificacion")->get();
        return response()->json([
            'success' => 'ok',
            'notificaciones' => $notificaciones
        ], 200);
    }

    public function info(Request $req){
        $notificacion = Notificaciones::where("idNotificacion",$req->idNotificacion)->first();
        NotificacionUsuario::where("idNotificacion",$req->idNotificacion)->where("idUser",Auth::id())->update(["visto"=>1,"fecha_visto"=>date("Y-m-d")]);
        return response()->json([
            'success' => 'ok',
            'info' => $notificacion
        ], 200);
    }

    public function getallnotificaciones(Request $req){
        $notificaciones = NotificacionUsuario::where("idUser",$req->idUser)->where("notificaciones.status",1)->join("notificaciones","notificaciones.idNotificacion","=","notificacion_usuario.idNotificacion")->get();
        return response()->json([
            'success' => 'ok',
            'notificaciones' => $notificaciones
        ], 200);
    }

    public function getusers(Request $req){
        $users = NotificacionUsuario::where("idNotificacion",$req->idNotificacion)
                ->join("users","users.id","=","notificacion_usuario.idUser")
                ->join("enlacedependencia","enlacedependencia.idEnlaceDependencia","=","users.idEnlaceDependencia")
                ->join("dependencia","dependencia.idDependencia","=","enlacedependencia.idDependencia")
                ->select("users.id","cuenta","name","dependenciaNombre","dependenciaSiglas")
                ->get();
        return response()->json([
            'success' => 'ok',
            'users' => $users
        ], 200);
    }

    public function delete(Request $req){
        try{
            DB::beginTransaction();
            Notificaciones::where("idNotificacion",$req->idNotificacion)->update([
                "status" => 0
            ]);
            DB::commit();
            return response()->json([
                'success' => 'ok',
                'message' => "Notificación dada de baja satisfactoriamente!"
            ], 200);

        }catch(Exception $ex){
            DB::rollBack();
            return response()->json([
                'success' => 'error',
                'message' => "Ocurrió un erro al dar de baja la Notificación!"
            ], 200);

        }
    }

}


