<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class mesas extends Model
{
    protected $table = 'mesas';
    protected $primaryKey = 'id_mesa';

    public $timestamps = false;

    protected $fillable = [
        'numero_mesa',
        'ubicacion',
        'capacidad',
        'estado',
    ];

    // Relación con el modelo Pedidos
    public function pedidos()
    {
        return $this->hasMany(pedidos::class, 'id_mesa');
    }
}
