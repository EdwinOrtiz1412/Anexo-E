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
        Schema::create('filas', function (Blueprint $table) {
            $table->id('idFila');
            $table->unsignedBigInteger('idCuadro');
            $table->string('nombreFila', 250);
            $table->tinyInteger('nivel')->unsigned()->default(1);
            $table->integer('orden')->default(1);
            $table->string('estilos', 10)->nullable();

            $table->unique(['idCuadro', 'nombreFila', 'nivel']);
            $table->unique(['idCuadro', 'idFila']);
            $table->unique(['idCuadro', 'nivel', 'orden']);
            // $table->timestamps();

            //Relaciones

            $table->foreign('idCuadro')->references('idCuadro')->on('cuadros')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filas');
    }
};
