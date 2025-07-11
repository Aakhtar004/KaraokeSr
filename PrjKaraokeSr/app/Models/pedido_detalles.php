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
        // AGREGADOS PARA BALDES
        'nombre_producto_personalizado',
        'tipo_producto',
        'configuracion_especial',
        'id_producto_base'
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario_momento' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'configuracion_especial' => 'array' // AGREGADO PARA BALDES
    ];

    // Relaciones EXISTENTES
    public function pedido()
    {
        return $this->belongsTo(pedidos::class, 'id_pedido');
    }

    public function producto()
    {
        return $this->belongsTo(productos::class, 'id_producto');
    }

    // NUEVA RELACIÓN PARA BALDES
    public function producto_base()
    {
        return $this->belongsTo(productos::class, 'id_producto_base');
    }

    public function preparador()
    {
        return $this->belongsTo(usuarios::class, 'id_usuario_preparador');
    }

    public function pagos()
    {
        return $this->hasMany(pagos_pedido_detalle::class, 'id_pedido_detalle');
    }

    // NUEVO ACCESSOR PARA OBTENER EL NOMBRE CORRECTO
    public function getNombreProductoCompletoAttribute()
    {
        if ($this->tipo_producto === 'balde_personalizado') {
            return $this->nombre_producto_personalizado ?: 'Balde Personalizado';
        } elseif ($this->tipo_producto === 'balde_normal') {
            return $this->nombre_producto_personalizado ?: ($this->producto_base ? 'Balde ' . $this->producto_base->nombre : 'Balde');
        } else {
            return $this->producto ? $this->producto->nombre : 'Producto no encontrado';
        }
    }
}
