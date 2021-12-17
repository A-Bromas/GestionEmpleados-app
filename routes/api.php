<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsersController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::prefix('users')->group(function(){
   // Route::put('/crear',[UsersController::class,'crear']);
   // Route::get('/listar',[UsersController::class,'listar']); 
    Route::post('/login',[UsersController::class,'login']); 
    Route::get('/recuperarPassword',[UsersController::class, 'recuperarPassword']);
 });
 Route::middleware(['login-con-token','permission'])->prefix('users')->group(function(){
    Route::put('/crear',[UsersController::class,'crear']);
    Route::get('/ver',[UsersController::class, 'ver']);
    Route::get('/listaEmpleados',[UsersController::class, 'listaEmpleados']);
    Route::get('/detalle/{id}',[UsersController::class, 'detalle']);
});