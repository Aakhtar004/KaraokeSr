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
        Schema::table('promocion_productos', function (Blueprint $table) {
            $table->foreign(['id_producto'], 'fk_promocionproducto_producto')->references(['id_producto'])->on('productos')->onUpdate('cascade')->onDelete('restrict');
            $table->foreign(['id_promocion'], 'fk_promocionproducto_promocion')->references(['id_promocion'])->on('promociones')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promocion_productos', function (Blueprint $table) {
            $table->dropForeign('fk_promocionproducto_producto');
            $table->dropForeign('fk_promocionproducto_promocion');
        });
    }
};
