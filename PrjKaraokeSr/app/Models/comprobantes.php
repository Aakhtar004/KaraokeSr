<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class comprobantes extends Model
{
    protected $table = 'comprobantes';
    protected $primaryKey = 'id_comprobante';

    public $timestamps = false;

    protected $fillable = [
        'id_pedido',
        'id_usuario_cajero',
        'tipo_documento_cliente',
        'numero_documento_cliente',
        'nombre_razon_social_cliente',
        'direccion_cliente',
        'serie_comprobante',
        'numero_correlativo_comprobante',
        'fecha_emision',
        'moneda',
        'subtotal_comprobante',
        'igv_aplicado_tasa',
        'monto_igv',
        'monto_total_comprobante',
        'tipo_comprobante',
        'metodo_pago',
        'referencia_pago',
        'estado_comprobante',
        'qr_code_data',
        'hash_sunat',
        'notas_comprobante',
        'fecha_anulacion',
    ];

    // Relaciones
    public function pedido()
    {
        return $this->belongsTo(pedidos::class, 'id_pedido');
    }

    public function cajero()
    {
        return $this->belongsTo(usuarios::class, 'id_usuario_cajero');
    }

    public function pagosDetalle()
    {
        return $this->hasMany(pagos_pedido_detalle::class, 'id_comprobante');
    }
}
