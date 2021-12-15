<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UsersController extends Controller
{
    public function crear(Request $req){

        $respuesta = ["status" => 1,"msg"=> "" ];        
        $datos = $req ->getContent();

        //VALIDAR EL JSON hola pepe

        $datos = json_decode($datos); //Se interpreta como objeto. Se puede pasar un parametro para que en su lugar lo devuelva como array.
        
        //VALIDAR LOS DATOS

        $usuario = new User();
        $usuario->name = $datos->name;
        $usuario->salario = $datos->salario;
        $usuario->email = $datos->email;
        $usuario->password = $datos->password;
        $usuario->biografia = $datos->biografia;
        $usuario->puesto = $datos->puesto;

    
        //Escribir en la base de datos
        try{
            $usuario->save();
            $respuesta['msg'] = "Usuario guardada con id ".$usuario->id;
        }catch(\Exception $e){
            $respuesta['status'] = 0;
            $respuesta['msg'] = "Se ha producido un error: ".$e->getMessage();

            
        }

       return response()->json($respuesta);
    
        

    }

    public function listar(){


        $respuesta = ["status" => 1,"msg"=> "" ];
        try{
            $usuarios = User::all();
            $respuesta['datos'] = $usuarios;

        }catch(\Exception $e){
            $respuesta['status'] = 0;
            $respuesta['msg'] = "Se ha producido un error: ".$e->getMessage();
            
        }

        
        return response() ->json($respuesta);
    }
}
