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
        Schema::create('promociones', function (Blueprint $table) {
            $table->comment('Tabla para almacenar las promociones ofrecidas');
            $table->increments('id_promocion');
            $table->string('nombre_promocion', 150)->unique('nombre_promocion')->comment('Nombre descriptivo de la promoción (ej: Combo Cumpleañero, 2x1 en Cervezas)');
            $table->text('descripcion_promocion')->nullable()->comment('Descripción detallada de la promoción, condiciones, etc.');
            $table->string('codigo_promocion', 50)->nullable()->unique('codigo_promocion')->comment('Código corto opcional para identificar o aplicar la promoción');
            $table->decimal('precio_promocion', 10)->comment('Precio final de la promoción');
            $table->dateTime('fecha_inicio')->comment('Fecha y hora de inicio de validez de la promoción');
            $table->dateTime('fecha_fin')->comment('Fecha y hora de fin de validez de la promoción');
            $table->enum('estado_promocion', ['activa', 'inactiva', 'agotada'])->default('activa')->index('idx_promocion_estado')->comment('Estado actual de la promoción');
            $table->string('imagen_url_promocion')->nullable()->comment('URL de la imagen representativa de la promoción');
            $table->set('dias_aplicables', ['LUN', 'MAR', 'MIE', 'JUE', 'VIE', 'SAB', 'DOM'])->nullable()->comment('Días de la semana en que aplica la promoción (NULL si aplica todos los días dentro del rango de fechas)');
            $table->unsignedInteger('stock_promocion')->nullable()->comment('Cantidad limitada de promociones disponibles (NULL si no hay límite específico de stock para la promoción en sí)');
            $table->timestamp('fecha_creacion')->nullable()->useCurrent();
            $table->timestamp('fecha_actualizacion')->useCurrentOnUpdate()->nullable()->useCurrent();

            $table->index(['fecha_inicio', 'fecha_fin'], 'idx_promocion_fechas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promociones');
    }
};
