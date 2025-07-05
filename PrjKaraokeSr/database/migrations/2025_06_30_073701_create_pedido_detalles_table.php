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
        Schema::create('pedido_detalles', function (Blueprint $table) {
            $table->comment('Detalle de los productos por pedido y su estado de preparación');
            $table->bigIncrements('id_pedido_detalle');
            $table->unsignedBigInteger('id_pedido')->index('fk_pedidodetalle_pedido')->comment('FK al pedido al que pertenece este detalle');
            $table->unsignedInteger('id_producto')->index('fk_pedidodetalle_producto')->comment('FK al producto solicitado');
            $table->unsignedInteger('cantidad')->default(1)->comment('Cantidad del producto solicitado');
            $table->decimal('precio_unitario_momento', 10)->comment('Precio del producto al momento de realizar el pedido');
            $table->decimal('subtotal', 10)->comment('Subtotal (cantidad * precio_unitario_momento)');
            $table->text('notas_producto')->nullable()->comment('Notas específicas para este producto en el pedido (ej: sin hielo, término medio)');
            $table->enum('estado_item', ['SOLICITADO', 'EN_PREPARACION', 'LISTO_PARA_ENTREGA', 'ENTREGADO_A_MESERO', 'ENTREGADO_A_CLIENTE', 'CANCELADO'])->default('SOLICITADO')->index('idx_pedidodetalle_estado_item')->comment('Estado del ítem dentro del pedido y su preparación');
            $table->unsignedInteger('id_usuario_preparador')->nullable()->index('fk_pedidodetalle_usuario_preparador')->comment('FK al usuario (cocinero/bartender) que preparó/está preparando el ítem (opcional)');
            $table->timestamp('fecha_creacion')->nullable()->useCurrent();
            $table->timestamp('fecha_actualizacion_estado')->useCurrentOnUpdate()->nullable()->useCurrent()->comment('Para rastrear cuándo cambió el estado del ítem');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedido_detalles');
    }
};
