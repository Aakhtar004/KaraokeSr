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
        Schema::create('productos', function (Blueprint $table) {
            $table->comment('Productos ofrecidos en el establecimiento');
            $table->increments('id_producto');
            $table->unsignedInteger('id_categoria_producto')->nullable()->index('fk_producto_categoria')->comment('FK a Categorias_Producto');
            $table->enum('area_destino', ['cocina', 'bar', 'ambos'])->comment('Indica el área de preparación o gestión principal del producto (ej: cocina para platos, bar para bebidas)');
            $table->string('codigo_interno', 50)->nullable()->unique('codigo_interno')->comment('Código interno o SKU del producto');
            $table->string('nombre', 150)->index('idx_producto_nombre')->comment('Nombre del producto');
            $table->text('descripcion')->nullable()->comment('Descripción detallada del producto');
            $table->decimal('precio_unitario', 10)->comment('Precio de venta del producto');
            $table->unsignedInteger('stock')->default(0)->comment('Cantidad disponible en inventario');
            $table->string('unidad_medida', 50)->nullable()->comment('Ej: Unidad, Botella, Plato, Litro');
            $table->string('imagen_url')->nullable()->comment('URL de la imagen del producto');
            $table->boolean('estado')->default(true)->index('idx_producto_estado')->comment('TRUE: disponible, FALSE: no disponible/agotado');
            $table->timestamp('fecha_creacion')->nullable()->useCurrent();
            $table->timestamp('fecha_actualizacion')->useCurrentOnUpdate()->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
