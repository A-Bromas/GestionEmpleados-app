<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\passwordEmail;
use Illuminate\Support\Facades\DB;
class UsersController extends Controller
{
    public function crear(Request $req){

        $respuesta = ["status" => 1, "msg" => ""];
        $validator = Validator::make(json_decode($req->
        getContent(),true), [
            "name" => 'required|max:50',
            "salario" => 'required|numeric',
            "email" => 'required|email|unique:App\Models\User,email|max:30',
            "password" => 'required|regex:/(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).{6,}/',
            "biografia" => 'required|max:100',
            "puesto" => 'required|in:Direccion,RRHH,Empleado'
        ]);

        if($validator -> fails()){
            $respuesta["status"] = 0;
            $respuesta["msg"] = $validator->errors(); 
        } else {

            $datos = $req -> getContent();
            $datos = json_decode($datos); 
    
            $usuario = new User();
            $usuario -> name = $datos -> name;
            $usuario -> salario = $datos -> salario;
            $usuario -> email = $datos -> email;
            $usuario -> password = Hash::make($datos->password);
            $usuario -> biografia = $datos -> biografia;
            $usuario -> puesto = $datos -> puesto;

            try {
                $usuario->save();
                $respuesta["msg"] = "Usuario guardado con id ".$usuario->id;
            }catch (\Exception $e) {
                $respuesta["status"] = 0;
                $respuesta["msg"] = "Se ha producido un error".$e->getMessage();  
            }
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
    public function ver(Request $req){

        $respuesta = ["status" => 1, "msg" => ""];
        $usuario = User::find($req->user->id);

        if($usuario){
            $usuario -> makevisible( 'password');
            $respuesta['perfil'] = $usuario;
        } else {
            $respuesta["status"] = 0;
            $respuesta["msg"] = "Se ha producido un error";  
        }
        return response()->json($respuesta);
    }

    public function login(Request $req){

        $respuesta = ["status" => 1, "msg" => ""];

        $datos = $req -> getContent();
        $datos = json_decode($datos); 
        $email = $req->email;
        $usuario = User::where('email', '=', $datos->email)->first();

        if ($usuario){
            if (Hash::check($datos->password, $usuario -> password)){

                do {
                    $token = Hash::make($usuario->id.now());
                } while(User::where('api_token', $token) -> first());

                $usuario -> api_token = $token;
                $usuario -> save();
                $respuesta["msg"] = "Login correcto, tu api token es: ".$usuario -> api_token;  

            } else {
                $respuesta["status"] = 0;
                $respuesta["msg"] = "La contraseña no es correcta";  
            }

        } else {
            $respuesta["status"] = 0;
            $respuesta["msg"] = "Usuario no encontrado";  
        }

        return response()->json($respuesta);  
    }
    public function detalle(Request $req, $id){

        $respuesta = ["status" => 1, "msg" => ""];
        $usuario = User::find($id);
        $bool = false;

        if($usuario){

            if ($usuario->puesto == 'Direccion'){

                if($usuario -> puesto != "Direccion")
                    $bool = true;
    
                    if($bool) {
                        $trabajador = User::whereIn('users.puesto', ['Empleado', 'RRHH'])
                            ->where('users.id',$usuario->id)
                            ->select('users.id','users.name','users.puesto','users.salario')
                            ->first(); 
                        $respuesta['detalle'] = $trabajador;
                    } else  {
                        $respuesta["status"] = 0;
                        $respuesta["msg"] = "No puedes ver los datos de directivos";
                    }

            } elseif ($request->usuario->puesto_trabajo == 'RRHH'){

                if($usuario -> puesto != "Direccion" && $usuario -> puesto != "RRHH")
                    $bool = true;   
        
                    if($bool) {
                        $trabajador = User::where('users.puesto', 'Empleado')
                            ->where('users.id',$usuario->id)
                            ->select('users.id','users.name','users.puesto','users.salario')
                            ->first();
                        $respuesta['detalle'] = $trabajador;
                    } else {
                        $respuesta["status"] = 0;
                        $respuesta["msg"] = "No puedes ver los datos de directivos o de RRHH";
                    }

            } else {
                $respuesta["status"] = 0;
                $respuesta["msg"] = "Se ha producido un error";
            }
        } else {
            $respuesta["status"] = 0;
            $respuesta["msg"] = "Usuario no encontrado";
        }

        return response()->json($respuesta);
    }
    public function recuperarPassword(Request $req){

        //Obtenemos el email
        $datos = $req->getContent();
        $datos = json_decode($datos);

        //Buscar el email
        $email = $datos->email;

        //Validacion
        $user = User::where('email',$email)->first();
        try{
            if($user){

                $user = User::where('email',$email)->first();

                $user->api_token = null;
                
                //Generamos nueva contraseña aleatoria
                $characters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcdefghijklmnopqrstuvwxyz";
                $characterLength = strlen($characters);
                $newPassword = '';
                for ($i=0; $i < 8; $i++) { 
                    $newPassword .= $characters[rand(0, $characterLength - 1)];
                } 
                $user->password = Hash::make($newPassword);
                $user->save();
                Mail::to($user->email)->send(new passwordEmail($newPassword));  
                //$respuesta['msg'] = "La nueva contraseña es ".$newPassword;
                
            }else{
                
                $respuesta['msg'] = "Usuario no registrado";
            }
            
        }catch(\Exception $e){
            $respuesta['status'] = 0;
            $respuesta['msg'] = "Se ha producido un error: ".$e->getMessage();
        }

        return response()->json($respuesta);


    }
    public function listaEmpleados(Request $req){

        $respuesta = ["status" => 1, "msg" => ""];
        $usuario = User::where('api_token', '=', $req->api_token)->first();
        if($usuario){
            if ($usuario->puesto == 'Direccion'){

                $empleados = User::whereIn('users.puesto', ['Empleado', 'RRHH'])
                    ->select('users.id','users.name','users.puesto','users.salario')
                    ->get(); 
            $respuesta['listaempleados'] = $empleados;

            } elseif ($usuario->puesto == 'RRHH'){

                $empleados = User::where('users.puesto', 'Empleado')
                    ->select('users.id','users.name','users.puesto','users.salario')
                    ->get(); 
                $respuesta['listaempleados'] = $empleados;

            } else {
                $respuesta["status"] = 0;
                $respuesta["msg"] = "Se ha producido un error";
            }
        }else{
            $respuesta["msg"] = "Usuario no encontrado";
        }
        return response()->json($respuesta);
    }

}