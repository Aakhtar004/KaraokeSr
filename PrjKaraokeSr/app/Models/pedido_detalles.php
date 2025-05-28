<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class pedido_detalles extends Model
{
    protected $table = 'pedido_detalles';
    protected $primaryKey = 'id_pedido_detalle';

    public $timestamps = false;

    protected $fillable = [
        'id_pedido',
        'id_producto',
        'cantidad',
        'precio_unitario_momento',
        'subtotal',
        'notas_producto',
        'estado_item',
        'id_usuario_preparador',
        'fecha_creacion',
        'fecha_actualizacion_estado',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario_momento' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    // Relaciones
    public function pedido()
    {
        return $this->belongsTo(pedidos::class, 'id_pedido');
    }

    public function producto()
    {
        return $this->belongsTo(productos::class, 'id_producto');
    }

    public function preparador()
    {
        return $this->belongsTo(usuarios::class, 'id_usuario_preparador');
    }

    public function pagos()
    {
        return $this->hasMany(pagos_pedido_detalle::class, 'id_pedido_detalle');
    }
}
