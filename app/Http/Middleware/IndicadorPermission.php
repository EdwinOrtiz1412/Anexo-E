<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Indicador;


class indicadorPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::user()->hasRole(['enlace']) || Auth::user()->hasRole(['administrador'])) {

            $indicador_ = $request->id;
            $idDependnecia = session('idDependencia');
            $indicadoresDependencia = Indicador::select('idIndicador', 'en_revision')->where('idDependencia', $idDependnecia)->get();
            $indicadores = $indicadoresDependencia;
            $encontrado = false;
            $editar = false;

            foreach ($indicadores as $indicador) {
                if ($indicador->idIndicador == $indicador_) {
                    $encontrado = true;
                    if (!$indicador->en_revision)
                        $editar = true;
                }
            }
            if ($encontrado && $editar)
                return $next($request);
        }
        return redirect('nopermitido');
    }
}
