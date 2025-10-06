<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cuadros', function (Blueprint $table) {
            $table->id('idCuadro');
            $table->integer('control');
            $table->string('numero',10);
            $table->string('titulo', 500);
            $table->string('descripcion')->nullable();
            $table->integer('idEjePED');
            $table->integer('idTemaPED');
            $table->integer('idDependencia');
            $table->unsignedInteger('idGrupo')->nullable();


            $table->timestamps();

            //Relaciones 

            $table->foreign('idEjePED')->references('idEjePED')->on('ejeped');
            $table->foreign('idTemaPED')->references('idTemaPED')->on('temaped');
            $table->foreign('idDependencia')->references('idDependencia')->on('dependencia');
            $table->foreign('idGrupo')->references('idGrupo')->on('grupos')->onDelete('set null');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuadros');
    }
};
