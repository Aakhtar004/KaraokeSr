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
        Schema::create('pedidos', function (Blueprint $table) {
            $table->comment('Pedidos de los clientes');
            $table->bigIncrements('id_pedido');
            $table->unsignedInteger('id_mesa')->index('fk_pedido_mesa')->comment('FK a la mesa donde se realiza el pedido');
            $table->unsignedInteger('id_usuario_mesero')->index('fk_pedido_usuario_mesero')->comment('FK al usuario (mesero) que tomó el pedido');
            $table->timestamp('fecha_hora_pedido')->nullable()->useCurrent()->index('idx_pedido_fecha')->comment('Fecha y hora en que se realizó el pedido');
            $table->enum('estado_pedido', ['PENDIENTE', 'EN_PREPARACION', 'LISTO_PARA_SERVIR', 'SERVIDO', 'CANCELADO', 'PAGADO'])->default('PENDIENTE')->index('idx_pedido_estado')->comment('Estado general del pedido');
            $table->decimal('total_pedido', 10)->default(0)->comment('Monto total del pedido (calculado)');
            $table->text('notas_adicionales')->nullable()->comment('Instrucciones especiales o comentarios del cliente para el pedido general');
            $table->timestamp('fecha_actualizacion')->useCurrentOnUpdate()->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
