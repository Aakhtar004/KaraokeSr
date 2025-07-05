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
        Schema::table('pagos_pedido_detalle', function (Blueprint $table) {
            $table->foreign(['id_comprobante'], 'fk_pagos_pedido_detalle_comprobante')->references(['id_comprobante'])->on('comprobantes')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['id_pedido_detalle'], 'fk_pagos_pedido_detalle_pedido_detalle')->references(['id_pedido_detalle'])->on('pedido_detalles')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pagos_pedido_detalle', function (Blueprint $table) {
            $table->dropForeign('fk_pagos_pedido_detalle_comprobante');
            $table->dropForeign('fk_pagos_pedido_detalle_pedido_detalle');
        });
    }
};
