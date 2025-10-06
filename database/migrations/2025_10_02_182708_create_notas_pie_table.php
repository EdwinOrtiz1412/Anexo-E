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
        Schema::create('notas_pie', function (Blueprint $table) {
            $table->id('idNotaPie');
            $table->unsignedBigInteger('idCuadro');
            $table->unsignedBigInteger('idCategoria')->nullable();
            $table->integer('idDependencia')->nullable();
            $table->text('texto');
            $table->integer('orden')->default(1);

            $table->index('idCuadro');

            $table->foreign('idCuadro')
                ->references('idCuadro')->on('cuadros')
                ->onDelete('cascade');

            $table->foreign('idCategoria')
                ->references('idCategoria')->on('categorias')
                ->nullOnDelete();

            $table->foreign('idDependencia')
                ->references('idDependencia')->on('dependencia')
                ->nullOnDelete();
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notas_pie');
    }
};
