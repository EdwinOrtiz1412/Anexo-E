<?php  

namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;



class ValidatePermission 
{   
    public function handle(Request $request, Closure $next):Response
    {
        if (Auth::user()->hasRole(['administrador'])) {
            return $next($request);            
        }
        return redirect('nopermitido');
    }
}