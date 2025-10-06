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
        Schema::create('columnas', function (Blueprint $table) {
            $table->id('idColumna');
            $table->unsignedBigInteger('idCuadro');
            $table->unsignedBigInteger('idColumnaPadre')->nullable();
            $table->string('nombreColumna', 250);
            $table->tinyInteger('nivel')->unsigned()->default(1);
            $table->integer('orden')->default(1);

            // Índices y restricciones únicas
            $table->unique(['idCuadro', 'idColumna']); // opcional, ya que idColumna es PK
            $table->unique(['idCuadro', 'idColumnaPadre', 'nombreColumna']);
            $table->index(['idCuadro', 'idColumnaPadre', 'nivel', 'orden']);

            // Relaciones
            $table->foreign('idCuadro')
                  ->references('idCuadro')->on('cuadros')
                  ->onDelete('cascade');

            $table->foreign('idColumnaPadre')
                  ->references('idColumna')->on('columnas')
                  ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('columnas');
    }
};
