<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class pedidos extends Model
{
    protected $table = 'pedidos';
    protected $primaryKey = 'id_pedido';

    public $timestamps = false;

    protected $fillable = [
        'id_mesa',
        'id_usuario_mesero',
        'fecha_hora_pedido',
        'estado_pedido',
        'total_pedido',
        'notas_adicionales',
    ];

    protected $dates = ['fecha_hora_pedido'];

    public function mesa()
    {
        return $this->belongsTo(mesas::class, 'id_mesa', 'id_mesa');
    }

    public function mesero()
    {
        return $this->belongsTo(usuarios::class, 'id_usuario_mesero');
    }

    public function detalles()
    {
        return $this->hasMany(pedido_detalles::class, 'id_pedido');
    }

    public function comprobante()
    {
        return $this->hasOne(comprobantes::class, 'id_pedido');
    }

}
