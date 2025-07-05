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
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->foreign(['id_pedido'], 'fk_comprobante_pedido')->references(['id_pedido'])->on('pedidos')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign(['id_usuario_cajero'], 'fk_comprobante_usuario_cajero')->references(['id_usuario'])->on('usuarios')->onUpdate('cascade')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->dropForeign('fk_comprobante_pedido');
            $table->dropForeign('fk_comprobante_usuario_cajero');
        });
    }
};
