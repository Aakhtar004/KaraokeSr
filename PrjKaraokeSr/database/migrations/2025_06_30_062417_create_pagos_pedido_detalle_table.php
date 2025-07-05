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
        Schema::create('pagos_pedido_detalle', function (Blueprint $table) {
            $table->comment('Detalla los métodos de pago para ítems específicos o cantidades de ítems dentro de un comprobante.');
            $table->bigIncrements('id_pago_pedido_detalle');
            $table->unsignedBigInteger('id_comprobante')->index('fk_pagos_pedido_detalle_comprobante')->comment('FK al comprobante general al que pertenece este detalle de pago');
            $table->unsignedBigInteger('id_pedido_detalle')->index('fk_pagos_pedido_detalle_pedido_detalle')->comment('FK al ítem específico del pedido (de la tabla Pedido_Detalles) que se está pagando con este método');
            $table->unsignedInteger('cantidad_item_pagada')->default(1)->comment('Cantidad del ítem referenciado en id_pedido_detalle que es cubierta por esta línea de pago. Ej: Si Pedido_Detalles tiene 3 Cervezas y 1 se paga con Yape, cantidad_item_pagada sería 1.');
            $table->decimal('monto_pagado', 10)->comment('Monto exacto cubierto por esta línea de pago para la cantidad_item_pagada. Idealmente, este monto es (Pedido_Detalles.precio_unitario_momento * Pagos_Pedido_Detalle.cantidad_item_pagada).');
            $table->enum('metodo_pago', ['EFECTIVO', 'TARJETA', 'YAPE', 'PLIN', 'TRANSFERENCIA'])->index('idx_pagos_pedido_detalle_metodo_pago')->comment('Método de pago específico utilizado para esta porción del ítem del pedido.');
            $table->string('referencia_pago', 100)->nullable()->comment('Referencia asociada a este pago específico (ej: ID de transacción Yape/Plin, últimos 4 dígitos de tarjeta, etc.)');
            $table->timestamp('fecha_creacion')->nullable()->useCurrent();
            $table->timestamp('fecha_actualizacion')->useCurrentOnUpdate()->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos_pedido_detalle');
    }
};
