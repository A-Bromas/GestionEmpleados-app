<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UsersController extends Controller
{
    public function crear(Request $req){

        $respuesta = ["status" => 1,"msg"=> "" ];        
        $datos = $req ->getContent();

        //VALIDAR EL JSON hola pepe

        $datos = json_decode($datos); //Se interpreta como objeto. Se puede pasar un parametro para que en su lugar lo devuelva como array.
        
        //VALIDAR LOS DATOS

        $usuario = new Usuario();
        $usuario->nombre = $datos->nombre;
        $usuario->foto = $datos->foto;
        $usuario->email = $datos->email;
        $usuario->contraseña = $datos->contraseña;
        $usuario->activado = $datos->activado = 1;


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
}
