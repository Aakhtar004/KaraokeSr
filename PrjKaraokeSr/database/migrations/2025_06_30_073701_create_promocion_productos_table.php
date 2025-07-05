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
        Schema::create('promocion_productos', function (Blueprint $table) {
            $table->comment('Tabla de enlace entre promociones y los productos que las componen');
            $table->bigIncrements('id_promocion_producto');
            $table->unsignedInteger('id_promocion')->index('fk_promocionproducto_promocion')->comment('FK a la promoción');
            $table->unsignedInteger('id_producto')->index('fk_promocionproducto_producto')->comment('FK al producto incluido en la promoción');
            $table->unsignedInteger('cantidad_producto_en_promo')->default(1)->comment('Cantidad de este producto que se incluye en la promoción (ej: 2 para un 2x1)');
            $table->decimal('precio_original_referencia', 10)->nullable()->comment('Precio unitario del producto al momento de añadirlo a la promo (solo referencia, el precio de venta es el de la promoción)');
            $table->timestamp('fecha_creacion')->nullable()->useCurrent();

            $table->unique(['id_promocion', 'id_producto'], 'uq_promocion_producto_unico');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promocion_productos');
    }
};
