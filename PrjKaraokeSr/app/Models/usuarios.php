<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Foundation\Auth\User as Authenticatable;


class usuarios extends Authenticatable
{
    // Nombre exacto de la tabla
    protected $table = 'usuarios';

    // Clave primaria personalizada
    protected $primaryKey = 'id_usuario';

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'codigo_usuario',
        'usuario',
        'contrasena',
        'nombres',
        'rol',
        'estado',
        'fecha_creacion',
        'fecha_actualizacion',
    ];

    // Laravel no usará created_at y updated_at
    public $timestamps = false;

    // Puedes definir mutadores si necesitas trabajar con contraseñas o roles

    // Ejemplo: Si quisieras ocultar la contraseña al serializar
    protected $hidden = [
        'contrasena',
    ];

    // Define el campo de autenticación
    public function getAuthPassword()
    {
        return $this->contrasena;
    }

    // Relaciones
    public function pedidosTomados()
    {
        return $this->hasMany(pedidos::class, 'id_usuario_mesero');
    }

    public function pedidosPreparados()
    {
        return $this->hasMany(pedido_detalles::class, 'id_usuario_preparador');
    }

    public function comprobantesEmitidos()
    {
        return $this->hasMany(comprobantes::class, 'id_usuario_cajero');
    }
}
