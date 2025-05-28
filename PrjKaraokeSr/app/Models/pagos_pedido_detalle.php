<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class pagos_pedido_detalle extends Model
{
    protected $table = 'pagos_pedido_detalle';
    protected $primaryKey = 'id_pago_pedido_detalle';

    public $timestamps = false;

    protected $fillable = [
        'id_comprobante',
        'id_pedido_detalle',
        'cantidad_item_pagada',
        'monto_pagado',
        'metodo_pago',
        'referencia_pago',
    ];

    // Relaciones
    public function comprobante()
    {
        return $this->belongsTo(comprobantes::class, 'id_comprobante');
    }

    public function detalle()
    {
        return $this->belongsTo(pedido_detalles::class, 'id_pedido_detalle');
    }
}
