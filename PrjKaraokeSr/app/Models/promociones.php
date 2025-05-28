<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class promociones extends Model
{
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
        'stock_promocion',
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
        return $this->belongsToMany(
            productos::class,
            'promocion_productos',
            'id_promocion',
            'id_producto'
        )->withPivot('cantidad_producto_en_promo', 'precio_original_referencia');
    }
}
