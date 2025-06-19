<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class promocion_productos extends Model
{
    use HasFactory;

    protected $table = 'promocion_productos';
    protected $primaryKey = 'id_promocion_producto';
    
    protected $fillable = [
        'id_promocion',
        'id_producto',
        'cantidad_producto_en_promo',
        'precio_original_referencia'
    ];

    public $timestamps = false;

    public function promocion()
    {
        return $this->belongsTo(promociones::class, 'id_promocion');
    }

    public function producto()
    {
        return $this->belongsTo(productos::class, 'id_producto');
    }
}

