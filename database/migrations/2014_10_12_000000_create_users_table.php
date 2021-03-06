<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->float('salario');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('biografia');
            $table->enum('puesto', ['Direccion', 'RRHH', 'Empleado']);
            $table->timestamp('email_verified_at')->nullable();
            //$table->rememberToken();
            $table->string('api_token')->nullable()->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
