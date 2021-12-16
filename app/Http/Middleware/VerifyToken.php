<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class VerifyToken
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

        $apitoken = $request->api_token;

        $user = User::where('api_token',$apitoken)->first();

        if(!$user){
            die("El token ha sido mal introducido");
        }else{
            $request->user = $user;
            return $next($request);
        }    }
}
