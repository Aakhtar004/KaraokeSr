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
        Schema::table('pedidos', function (Blueprint $table) {
            $table->foreign(['id_mesa'], 'fk_pedido_mesa')->references(['id_mesa'])->on('mesas')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign(['id_usuario_mesero'], 'fk_pedido_usuario_mesero')->references(['id_usuario'])->on('usuarios')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropForeign('fk_pedido_mesa');
            $table->dropForeign('fk_pedido_usuario_mesero');
        });
    }
};
