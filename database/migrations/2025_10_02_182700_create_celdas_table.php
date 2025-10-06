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
        Schema::create('celdas', function (Blueprint $table) {
            $table->id('idCelda');
            $table->unsignedBigInteger('idCuadro');
            $table->unsignedBigInteger('idFila');
            $table->unsignedBigInteger('idColumna');
            $table->unsignedBigInteger('idCategoria');
            $table->unsignedBigInteger('user_id');
            $table->decimal('valor_numero', 20, 6);
            $table->timestamps();

            $table->unique(['idCuadro', 'idFila', 'idColumna', 'idCategoria']);
            $table->index('idCuadro');
            $table->index('idCategoria');

            $table->foreign('idCuadro')->references('idCuadro')->on('cuadros')->onDelete('cascade');
            $table->foreign('idFila')->references('idFila')->on('filas')->onDelete('cascade');
            $table->foreign('idColumna')->references('idColumna')->on('columnas')->onDelete('cascade');
            $table->foreign('idCategoria')->references('idCategoria')->on('categorias')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('celdas');
    }
};
