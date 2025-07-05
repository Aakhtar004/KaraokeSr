<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class promociones extends Model
{
    use HasFactory;

    protected $table = 'promociones';
    protected $primaryKey = 'id_promocion';

    public $timestamps = false;

    protected $fillable = [
        'nombre_promocion',
        'descripcion_promocion',
        'codigo_promocion',
        'precio_promocion',
        'fecha_inicio',
        'fecha_fin',
        'estado_promocion',
        'imagen_url_promocion',
        'dias_aplicables',
        // 'stock_promocion',
        'fecha_creacion',
        'fecha_actualizacion',
    ];

    protected $casts = [
        'dias_aplicables' => 'array',
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];

    // Relaciones
    public function productos()
    {
        return $this->hasMany(promocion_productos::class, 'id_promocion');
    }
}

