<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\passwordEmail;
class UsersController extends Controller
{
    public function crear(Request $req){

        $respuesta = ["status" => 1, "msg" => ""];
        $validator = Validator::make(json_decode($req->
        getContent(),true), [
            "name" => 'required|max:50',
            "salario" => 'required|numeric',
            "email" => 'required|email|unique:App\Models\User,email|max:50',
            "password" => 'required|regex:/(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).{6,}/',
            "biografia" => 'required|max:400',
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
                $respuesta["status"] = 1;
                $respuesta["msg"] = "Usuario creado";
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
                $usuarios = User::where('api_token', '=', $token)->first();
                $perfil = User::where('users.id', '=', $usuarios->id)
                    ->select('users.puesto')
                    ->get(); 

                    
                $respuesta['listaempleados'] = $perfil;
                $respuesta["status"] = 1;
                $respuesta["msg"] = $token;  

            } else {
                $respuesta["status"] = 401;
                $respuesta["msg"] = "La contrase??a no es correcta";  
            }

        } else {
            $respuesta["status"] = 401;
            $respuesta["msg"] = "Usuario no encontrado";  
        }

        return response()->json($respuesta);  
    }
    public function detalle(Request $req, $id){

        $respuesta = ["status" => 1, "msg" => ""];
        $empleado = User::find($id);
        

        if($empleado){
            $usuario = User::where('api_token', '=', $req->api_token)->first();
            if ($usuario->puesto == 'Direccion'){
                if ($usuario->puesto == 'Direccion'){
                    $empleados = User::whereIn('users.puesto', ['Empleado', 'RRHH'])
                        ->select('users.name','users.email','users.biografia','users.puesto','users.salario')
                        ->where('users.id', '=', $id)
                        ->get(); 
                    $respuesta['detalle'] = $empleados;
                }else  {
                    $respuesta["status"] = 0;
                    $respuesta["msg"] = "No puedes ver los datos de Direccion";
                }

            } elseif ($usuario->puesto == 'RRHH'){

                if($usuario -> puesto = "RRHH"){
                    $empleados = User::where('users.puesto', 'Empleado')
                    ->select('users.name','users.email','users.biografia','users.puesto','users.salario')
                    ->where('users.id', '=', $id)
                    ->get(); 
                    $respuesta['detalle'] = $empleados;
                }else  {
                    $respuesta["status"] = 0;
                    $respuesta["msg"] = "No puedes ver los datos de RRHH";
                }

            } else {
                $respuesta["status"] = 0;
                $respuesta["msg"] = "Se ha producido un error";
            }

        } else {
            $respuesta["status"] = 0;
            $respuesta["msg"] = "El usuario no ha sido encontrado";
        }

        return response()->json($respuesta);
    }
    public function recuperarPassword(Request $req){

        //Obtenemos el email
        $datos = $req->getContent();
        $datos = json_decode($datos);

        //Buscar el email
        $email = $datos->email;
        $respuesta = ["status" => 1, "msg" => ""];
        //Validacion
        $user = User::where('email',$email)->first();
        try{
            if($user){


                $user->api_token = null;
                
                //Generamos nueva contrase??a aleatoria
                $characters = "ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcdefghijklmnopqrstuvwxyz";
                $characterLength = strlen($characters);
                $newPassword = '';
                for ($i=0; $i < 8; $i++) { 
                    $newPassword .= $characters[rand(0, $characterLength - 1)];
                } 
                $user->password = Hash::make($newPassword);
                $user->save();
                Mail::to($user->email)->send(new passwordEmail($newPassword));  
                $respuesta['msg'] = "Se ha enviado su nueva contrase??a";
                
            }else{
                
                $respuesta['msg'] = "Ese usuario no esta registrado";
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
                    ->select('users.name','users.puesto','users.biografia','users.salario')
                    ->get(); 
            $respuesta['listaempleados'] = $empleados;

            } elseif ($usuario->puesto == 'RRHH'){

                $empleados = User::where('users.puesto', 'Empleado')
                    ->select('users.name','users.biografia','users.puesto','users.salario')
                    ->get(); 
                $respuesta['listaempleados'] = $empleados;

            } else {
                $respuesta["status"] = 0;
                $respuesta["msg"] = "Se ha producido un error";
            }
        }else{
            $respuesta["msg"] = "El usuario no ha sido encontrado";
        }
        return response()->json($respuesta);
    }
    function profile(Request $req)
    {

        $respuesta = ["status" => 1, "msg" => ""];

        // $data = $req->getContent();
        // $data = json_decode($data);

        try {
            $usuario = User::where('api_token', '=', $req->api_token)->first();
            $perfil = User::where('users.id', '=', $usuario->id)
                    ->select('users.name','users.email','users.biografia','users.puesto','users.salario')
                    ->get(); 

                    $respuesta['listaempleados'] = $perfil;

            
        } catch (\Exception $e) {
            $respuesta['status'] = 0;
            $respuesta['msg'] = "Se ha producido un error: " . $e->getMessage();
        }
        return response()->json($respuesta);
    }


}