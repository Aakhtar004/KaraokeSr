<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\productos;
use App\Models\categorias_producto;
use App\Models\promociones;


class controller_cartadigital extends Controller
{
    public function index()
    {
        $categoriasCartaShow = [
            'Piqueos',
            'Cocteles',
            'Licores',
            'Bebidas',
            'Cervezas',
            'Jarras'
            //Balde retirado momentaneamente
        ];

        $categorias = categorias_producto::whereIn('nombre', $categoriasCartaShow)
            ->whereHas('productos', function($query) {
                $query->where('estado', 1);
            })
            ->orderByRaw(
                "FIELD(nombre, ".implode(',', array_map(fn($n) => "'{$n}'", $categoriasCartaShow)).")"
            )
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

        $hoy = now()->toDateString(); // Solo la fecha, sin hora

        // Obtener promociones activas
        $promocionesActivas = promociones::with(['productos.producto'])
            ->where('estado_promocion', 'activa')
            ->whereDate('fecha_inicio', '<=', $hoy)
            ->whereDate('fecha_fin', '>=', $hoy)
            ->orderBy('nombre_promocion')
            ->get();

        // Creacion de array de productos individuales con promociones
        $productosConPromocion = [];
        foreach ($promocionesActivas as $promocion) {
            foreach ($promocion->productos as $promoProducto) {
                if ($promoProducto->producto && $promoProducto->producto->estado == 1) {
                    $productosConPromocion[$promoProducto->producto->id_producto] = [
                        'promocion_id' => $promocion->id_promocion,
                        'nombre_promocion' => $promocion->nombre_promocion,
                        'descripcion_promocion' => $promocion->descripcion_promocion,
                        'precio_original' => $promoProducto->precio_original_referencia,
                        'precio_promocional' => $this->calcularPrecioPromocional($promoProducto->precio_original_referencia, $promocion->descripcion_promocion),
                        'tipo_promocion' => $this->detectarTipoPromocion($promocion->descripcion_promocion),
                        'porcentaje_descuento' => $this->calcularPorcentajeDescuento($promoProducto->precio_original_referencia, $promocion->descripcion_promocion)
                    ];
                }
            }
        }

        // Procesar promociones para la carta (promociones completas)
        $promocionesParaCarta = [];
        foreach ($promocionesActivas as $promocion) {
            $productosIncluidos = [];
            $productosAgotados = 0;

            foreach ($promocion->productos as $promoProducto) {
                if ($promoProducto->producto && $promoProducto->producto->estado == 1) {
                    $stock = $promoProducto->producto->stock;
                    $agotado = $stock <= 0;
                    if ($agotado) $productosAgotados++;

                    $productosIncluidos[] = [
                        'nombre' => $promoProducto->producto->nombre,
                        'precio_original' => $promoProducto->precio_original_referencia,
                        'precio_promocional' => $this->calcularPrecioPromocional($promoProducto->precio_original_referencia, $promocion->descripcion_promocion),
                        'unidad_medida' => $promoProducto->producto->unidad_medida ?? '',
                        'stock' => $stock,
                        'agotado' => $agotado,
                    ];
                }
            }

            // Solo mostrar la promoción si al menos un producto tiene stock
            if (count($productosIncluidos) === 0 || $productosAgotados === count($productosIncluidos)) {
                continue; // No mostrar la promoción si todos los productos están agotados o no hay productos válidos
            }

            $precioOriginal = array_sum(array_column($productosIncluidos, 'precio_original'));
            $porcentajeDescuento = 0;
            if ($precioOriginal > 0) {
                $precioPromocion = array_sum(array_column($productosIncluidos, 'precio_promocional'));
                $porcentajeDescuento = round((($precioOriginal - $precioPromocion) / $precioOriginal) * 100);
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
                'productos_incluidos' => $productosIncluidos,
                'agotada' => false, // la promoción no se muestra si todos están agotados
                'imagen_url' => $promocion->imagen_url_promocion,
                'promo_badge' => $promoBadge,
                'es_promocion' => true,
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

        foreach ($productosPorCategoria as $categoriaNombre => &$productos) {
            foreach ($productos as &$producto) {
                $producto->en_promocion = false;
                $producto->precio_promocion = null;
                $producto->porcentaje_descuento = 0;
                // Buscar si el producto está en alguna promoción activa
                foreach ($promocionesActivas as $promocion) {
                    foreach ($promocion->productos as $promoProducto) {
                        if ($promoProducto->producto && $promoProducto->producto->id_producto == $producto->id_producto) {
                            // Calcular el precio promocional individual
                            $precioOriginal = $promoProducto->precio_original_referencia;
                            $precioPromocional = $this->calcularPrecioPromocional($precioOriginal, $promocion->descripcion_promocion);
                            $porcentajeDescuento = $this->calcularPorcentajeDescuento($precioOriginal, $promocion->descripcion_promocion);

                            $producto->en_promocion = true;
                            $producto->precio_promocion = $precioPromocional;
                            $producto->porcentaje_descuento = $porcentajeDescuento;
                            $producto->precio_original = $precioOriginal; // Para mostrar el tachado si aplica
                            break 2; // Salir de ambos foreach
                        }
                    }
                }
            }
        }

        return view('carta_digital', compact('categorias', 'productosPorCategoria', 'promocionesParaCarta', 'iconos'));
    }

    //  Función para calcular precio promocional individual
    private function calcularPrecioPromocional($precioOriginal, $descripcionPromocion)
    {
        if (stripos($descripcionPromocion, '10%') !== false) {
            return $precioOriginal * 0.9;
        } elseif (stripos($descripcionPromocion, '50%') !== false) {
            return $precioOriginal * 0.5;
        } elseif (stripos($descripcionPromocion, '2x1') !== false) {
            return $precioOriginal / 2;
        }
        return $precioOriginal;
    }

    // Función para calcular porcentaje de descuento
    private function calcularPorcentajeDescuento($precioOriginal, $descripcionPromocion)
    {
        $precioPromocional = $this->calcularPrecioPromocional($precioOriginal, $descripcionPromocion);
        if ($precioOriginal > 0 && $precioPromocional < $precioOriginal) {
            return round((($precioOriginal - $precioPromocional) / $precioOriginal) * 100);
        }
        return 0;
    }

    // Función para detectar tipo de promoción
    private function detectarTipoPromocion($descripcion)
    {
        if (stripos($descripcion, '2x1') !== false) {
            return '2x1';
        } elseif (stripos($descripcion, '10%') !== false) {
            return '10%';
        } elseif (stripos($descripcion, '50%') !== false) {
            return '50%';
        }
        return 'personalizada';
    }
}