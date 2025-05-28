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
        Schema::table('pedido_detalles', function (Blueprint $table) {
            $table->foreign(['id_pedido'], 'fk_pedidodetalle_pedido')->references(['id_pedido'])->on('pedidos')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign(['id_producto'], 'fk_pedidodetalle_producto')->references(['id_producto'])->on('productos')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign(['id_usuario_preparador'], 'fk_pedidodetalle_usuario_preparador')->references(['id_usuario'])->on('usuarios')->onUpdate('cascade')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedido_detalles', function (Blueprint $table) {
            $table->dropForeign('fk_pedidodetalle_pedido');
            $table->dropForeign('fk_pedidodetalle_producto');
            $table->dropForeign('fk_pedidodetalle_usuario_preparador');
        });
    }
};
