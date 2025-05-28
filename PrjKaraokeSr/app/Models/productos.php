<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class productos extends Model
{
    // Nombre exacto de la tabla
    protected $table = 'productos';

    // Clave primaria personalizada
    protected $primaryKey = 'id_producto';

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'id_categoria_producto',
        'area_destino',
        'codigo_interno',
        'nombre',
        'descripcion',
        'precio_unitario',
        'stock',
        'unidad_medida',
        'imagen_url',
        'estado',
        'fecha_creacion',
        'fecha_actualizacion',
    ];

    // Laravel no usará created_at y updated_at
    public $timestamps = false;

    // Relación: Un producto pertenece a una categoría
    public function categoria()
    {
        return $this->belongsTo(categorias_producto::class, 'id_categoria_producto');
    }
    public function detalles()
    {
        return $this->hasMany(pedido_detalles::class, 'id_producto');
    }

    public function promociones()
    {
        return $this->belongsToMany(
            promociones::class,
            'promocion_productos',
            'id_producto',
            'id_promocion'
        )->withPivot('cantidad_producto_en_promo', 'precio_original_referencia');
    }
}
