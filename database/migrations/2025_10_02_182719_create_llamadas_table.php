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
        Schema::create('llamadas', function (Blueprint $table) {
            $table->id('idLlamada');
            $table->unsignedBigInteger('idCuadro');
            $table->unsignedBigInteger('idFila')->nullable();
            $table->unsignedBigInteger('idColumna')->nullable();
            $table->unsignedBigInteger('idCategoria');
            $table->text('nota');
            $table->integer('orden')->default(1);
            $table->timestamps();

            $table->index('idCuadro');
            $table->index(['idCuadro', 'idFila']);
            $table->index(['idCuadro', 'idColumna']);

            $table->foreign('idCuadro')->references('idCuadro')->on('cuadros')->onDelete('cascade');
            $table->foreign('idFila')->references('idFila')->on('filas')->nullOnDelete();
            $table->foreign('idColumna')->references('idColumna')->on('columnas')->nullOnDelete();
            $table->foreign('idCategoria')->references('idCategoria')->on('categorias')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('llamadas');
    }
};
