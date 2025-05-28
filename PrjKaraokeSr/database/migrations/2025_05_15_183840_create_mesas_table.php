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
        Schema::create('mesas', function (Blueprint $table) {
            $table->comment('Mesas del establecimiento');
            $table->increments('id_mesa');
            $table->string('numero_mesa', 10)->unique('numero_mesa')->comment('Número o código identificador de la mesa');
            $table->string('ubicacion', 100)->nullable()->comment('Descripción de la ubicación (ej: Salón Principal, Terraza, VIP)');
            $table->unsignedInteger('capacidad')->default(4)->comment('Número de personas que puede albergar la mesa');
            $table->enum('estado', ['disponible', 'ocupada', 'reservada', 'mantenimiento'])->default('disponible')->index('idx_mesa_estado')->comment('Estado actual de la mesa');
            $table->timestamp('fecha_creacion')->nullable()->useCurrent();
            $table->timestamp('fecha_actualizacion')->useCurrentOnUpdate()->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mesas');
    }
};
