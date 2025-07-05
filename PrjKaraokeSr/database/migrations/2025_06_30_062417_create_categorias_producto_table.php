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
        Schema::create('categorias_producto', function (Blueprint $table) {
            $table->comment('Categorías de los productos');
            $table->increments('id_categoria_producto');
            $table->string('nombre', 100)->unique('nombre')->comment('Nombre de la categoría (ej: Bebidas, Piqueos, Licores)');
            $table->text('descripcion')->nullable()->comment('Descripción adicional de la categoría');
            $table->boolean('estado')->default(true)->comment('TRUE: activa, FALSE: inactiva');
            $table->timestamp('fecha_creacion')->nullable()->useCurrent();
            $table->timestamp('fecha_actualizacion')->useCurrentOnUpdate()->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categorias_producto');
    }
};
