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
        Schema::create('usuarios', function (Blueprint $table) {
            $table->comment('Tabla de usuarios del sistema');
            $table->increments('id_usuario');
            $table->string('codigo_usuario', 14)->unique('codigo_usuario')->comment('ID personalizado formato YYMMDDHHMMSS + opcional secuencial para unicidad');
            $table->string('usuario', 50)->unique('usuario')->comment('Nombre de usuario para login');
            $table->string('contrasena')->comment('Contraseña hasheada');
            $table->string('nombres', 150)->comment('Nombres completos del usuario');
            $table->enum('rol', ['administrador', 'mesero', 'bartender', 'cocinero'])->index('idx_usuario_rol')->comment('Rol del usuario en el sistema');
            $table->boolean('estado')->default(true)->index('idx_usuario_estado')->comment('TRUE: activo, FALSE: inactivo');
            $table->timestamp('fecha_creacion')->nullable()->useCurrent();
            $table->timestamp('fecha_actualizacion')->useCurrentOnUpdate()->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
