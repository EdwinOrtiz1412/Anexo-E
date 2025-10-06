<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id('idCategoria');
            $table->string('nombre',200);
            $table->text('descripcion');
            $table->year('anio');
            $table->unsignedInteger('idGrupo')->nullable();
            $table->foreign('idGrupo')->references('idGrupo')->on('grupos')->onDelete('set null');


            //vigente = 1 ; Solicitado=0
            $table->boolean('vigente')->default(1);
            $table->boolean('solicitado')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
