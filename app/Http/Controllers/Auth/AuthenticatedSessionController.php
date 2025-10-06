<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Accesos;
use App\Models\EnlaceDependencia;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        //Obtenemos y seteamos variables de sesion del usuario
        $idUsuario = Auth::id();
        $idEnlaceDependencia = User::select("idEnlaceDependencia")->where("id",$idUsuario)->first();
        $infoEnlace = EnlaceDependencia::select("*")->where("idEnlaceDependencia",$idEnlaceDependencia->idEnlaceDependencia)->first();
        session([
            "idDependencia" => $infoEnlace->idDependencia,
            "enlace" => $infoEnlace->titulo." ".$infoEnlace->nombre." ".$infoEnlace->apellidoP." ".$infoEnlace->apellidoM,
            "idEnlaceDependencia" => $infoEnlace->idEnlaceDependencia,
            "mod" => $request->mod
        ]);

        Accesos::create([
            'users_id'=> Auth::id(),
            'tipo' => 'acceso'
        ]);

        /*if (auth()->user()->hasRole("administrador"))
            return redirect()->route('admin.indicadores');
        else
            //return redirect()->intended(RouteServiceProvider::HOME);
            return redirect()->route('indicador.list');*/
        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Accesos::create([
            "users_id" => Auth::id(),
            "tipo" => "salida"
        ]);

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function destroyg(): RedirectResponse
    {
        if(Auth::id() != null){
            Accesos::create([
                "users_id" => Auth::id(),
                "tipo" => "salida"
            ]);

            Auth::guard('web')->logout();
        }
        return redirect('/');
    }
}
