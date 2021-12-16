<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifyUserPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if($request->user->puesto =='Direccion' || $request->user->puesto =='RRHH'){
            return $next($request);
        }else{
            $respuesta['msg'] = "Necesitas permisos de administrador para hacer esta accion";   
        }
        return response()->json($respuesta);
    }
}
