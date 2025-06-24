<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\productos;
use App\Models\categorias_producto;
use App\Models\promociones;

class CartaDigitalController extends Controller
{
    public function index()
    {
        // // Obtener categorías activas que tienen productos activos
        // $categorias = categorias_producto::where('estado', 1)
        //     ->whereHas('productos', function($query) {
        //         $query->where('estado', 1);
        //     })
        //     ->orderBy('nombre')
        //     ->get();

        $categoriasCartaShow = ['Piqueos', 'Cocteles', 'Licores', 'Bebidas', 'Cervezas', 'Jarras', 'Baldes'];
        
        // Obtener solo las categorías específicas para meseros
        $categorias = categorias_producto::whereIn('nombre', $categoriasCartaShow)
            ->whereHas('productos', function($query) {
                $query->where('estado', 1);
            })
            ->orderBy('nombre')
            ->get();

        // Obtener productos activos agrupados por categoría
        $productosPorCategoria = [];
        foreach ($categorias as $categoria) {
            $productos = productos::where('id_categoria_producto', $categoria->id_categoria_producto)
                ->where('estado', 1)
                ->orderBy('nombre')
                ->get();
            
            // NUEVA LÓGICA: Filtrar productos según la categoría
            if ($categoria->nombre === 'Cocteles') {
                // Para cocteles: solo verificar que estén activos (estado = 1)
                $productosDisponibles = $productos; // Ya filtrados por estado = 1 arriba
            } else {
                // Para otros productos: verificar estado Y stock
                $productosDisponibles = $productos->filter(function($producto) {
                    return $producto->stock > 0;
                });
            }
            
            if ($productosDisponibles->isNotEmpty()) {
                $productosPorCategoria[$categoria->nombre] = $productosDisponibles;
            }
        }

        // Obtener promociones activas
        $promocionesActivas = promociones::with(['productos.producto'])
            ->where('estado_promocion', 'activa')
            ->where('fecha_inicio', '<=', now())
            ->where('fecha_fin', '>=', now())
            ->where('stock_promocion', '>', 0)
            ->orderBy('nombre_promocion')
            ->get();

        // Procesar promociones para la carta
        $promocionesParaCarta = [];
        foreach ($promocionesActivas as $promocion) {
            // Verificar que todos los productos tengan stock
            $todosConStock = true;
            $stockMinimo = $promocion->stock_promocion;
            
            foreach ($promocion->productos as $promoProducto) {
                if (!$promoProducto->producto || $promoProducto->producto->estado != 1) {
                    $todosConStock = false;
                    break;
                }
                
                // NUEVA LÓGICA: Aplicar diferenciación para cocteles en promociones
                if (isset($promoProducto->producto->categoria) && $promoProducto->producto->categoria->nombre === 'Cocteles') {
                    // Para cocteles: solo verificar estado
                    if ($promoProducto->producto->estado != 1) {
                        $todosConStock = false;
                        break;
                    }
                } else {
                    // Para otros productos: verificar stock
                    $stockMinimo = min($stockMinimo, $promoProducto->producto->stock);
                    if ($promoProducto->producto->stock <= 0) {
                        $todosConStock = false;
                        break;
                    }
                }
            }
            
            if (!$todosConStock || $promocion->productos->isEmpty()) {
                continue;
            }
            
            // Calcular precios
            $precioOriginal = $promocion->productos->sum('precio_original_referencia');
            $porcentajeDescuento = 0;
            
            if ($precioOriginal > 0) {
                $porcentajeDescuento = round((($precioOriginal - $promocion->precio_promocion) / $precioOriginal) * 100);
            }

            // Badge de promoción
            $promoBadge = '';
            if (stripos($promocion->descripcion_promocion, '2x1') !== false) {
                $promoBadge = '2x1';
            } elseif ($porcentajeDescuento > 0) {
                $promoBadge = "-{$porcentajeDescuento}%";
            }

            $promocionData = (object)[
                'id_producto' => "promo_{$promocion->id_promocion}",
                'nombre' => $promocion->nombre_promocion,
                'descripcion' => $promocion->descripcion_promocion,
                'precio_unitario' => $promocion->precio_promocion,
                'precio_original' => $precioOriginal,
                'stock' => $stockMinimo,
                'imagen_url' => $promocion->imagen_url_promocion,
                'unidad_medida' => 'promoción',
                'promo_badge' => $promoBadge,
                'es_promocion' => true,
                'productos_incluidos' => $promocion->productos->pluck('producto.nombre')->filter()->toArray(),
                'porcentaje_descuento' => $porcentajeDescuento
            ];

            $promocionesParaCarta[] = $promocionData;
        }

        // Definir iconos para categorías
        $iconos = [
            'Piqueos' => 'fas fa-utensils',
            'Cocteles' => 'fas fa-cocktail',
            'Licores' => 'fas fa-glass-whiskey',
            'Bebidas' => 'fas fa-glass-water',
            'Cervezas' => 'fas fa-beer',
            'Jarras' => 'fas fa-wine-bottle',
            'Baldes' => 'fas fa-bucket',
            'Bebidas de Barra' => 'fas fa-glass',
            'Condimentos y Especias' => 'fas fa-seedling',
            'Frutas' => 'fas fa-apple-alt',
            'Ingredientes' => 'fas fa-flask',
            'Licores de Barra' => 'fas fa-wine-glass',
            'Materias Primas' => 'fas fa-boxes',
            'No comestibles' => 'fas fa-tools'
        ];

        return view('carta_digital', compact('categorias', 'productosPorCategoria', 'promocionesParaCarta', 'iconos'));
    }
}