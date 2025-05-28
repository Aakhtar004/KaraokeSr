<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class categorias_producto extends Model
{
    //
    protected $table = 'categorias_producto';
    protected $primaryKey = 'id_categoria_producto';
    protected $fillable = [
        'nombre',
        'descripcion',
        'estado',
        'fecha_creacion',
        'fecha_actualizacion',

    ];
    public $timestamps = false;
    // Definimos la relación con el modelo Producto
    public function productos()
    {
        return $this->hasMany(productos::class, 'id_categoria_producto');
    }
}
