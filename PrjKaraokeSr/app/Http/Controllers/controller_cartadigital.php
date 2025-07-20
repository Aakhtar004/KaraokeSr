<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\productos;
use App\Models\categorias_producto;
use App\Models\promociones;
use Illuminate\Support\Facades\Log;

class controller_cartadigital extends Controller
{
    public function index()
    {
        // MODIFICADO: Agregar la categoría Baldes
        $categoriasCartaShow = [
            'Piqueos',
            'Cocteles',
            'Licores',
            'Bebidas',
            'Cervezas',
            'Jarras',
            'Baldes' // AGREGADO: Activar baldes en la carta
        ];

        // CORREGIDO: Obtener categorías con tratamiento especial para Baldes
        $categorias = collect();
        
        // Obtener categorías normales que tienen productos
        $categoriasNormales = categorias_producto::whereIn('nombre', array_diff($categoriasCartaShow, ['Baldes']))
            ->whereHas('productos', function($query) {
                $query->where('estado', 1);
            })
            ->get();
        
        // Agregar categorías normales a la colección
        foreach ($categoriasNormales as $categoria) {
            $categorias->push($categoria);
        }
        
        // AGREGAR CATEGORIA BALDES MANUALMENTE SI TIENE PRODUCTOS GENERADOS
        $baldesGenerados = $this->generarBaldesParaCarta();
        if ($baldesGenerados->isNotEmpty()) {
            // Buscar o crear la categoría Baldes
            $categoriaBaldes = categorias_producto::where('nombre', 'Baldes')->first();
            if (!$categoriaBaldes) {
                // Si no existe, crear una instancia temporal para la vista
                $categoriaBaldes = new categorias_producto([
                    'id_categoria_producto' => 7,
                    'nombre' => 'Baldes',
                    'descripcion' => 'Baldes de cervezas para grupos',
                    'estado' => 1
                ]);
            }
            $categorias->push($categoriaBaldes);
        }
        
        // Reordenar según el orden deseado
        $categorias = $categorias->sortBy(function($categoria) use ($categoriasCartaShow) {
            return array_search($categoria->nombre, $categoriasCartaShow);
        });

        // Obtener productos activos agrupados por categoría
        $productosPorCategoria = [];
        foreach ($categorias as $categoria) {
            if ($categoria->nombre === 'Baldes') {
                // CORREGIDO: Usar los baldes ya generados
                if ($baldesGenerados->isNotEmpty()) {
                    $productosPorCategoria[$categoria->nombre] = $baldesGenerados;
                }
            } else {
                $productos = productos::where('id_categoria_producto', $categoria->id_categoria_producto)
                    ->where('estado', 1)
                    ->orderBy('nombre')
                    ->get();
                
                // Filtrar productos según la categoría
                if ($categoria->nombre === 'Cocteles') {
                    // Para cocteles: solo verificar que estén activos (estado = 1)
                    $productosDisponibles = $productos;
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
        }

        $hoy = now()->toDateString();

        // Obtener promociones activas
        $promocionesActivas = promociones::with(['productos.producto'])
            ->where('estado_promocion', 'activa')
            ->whereDate('fecha_inicio', '<=', $hoy)
            ->whereDate('fecha_fin', '>=', $hoy)
            ->orderBy('nombre_promocion')
            ->get();

        // Creación de array de productos individuales con promociones
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
                continue;
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
                'agotada' => false,
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
            'Baldes' => 'fas fa-archive',
            'Bebidas de Barra' => 'fas fa-glass',
        ];

        foreach ($productosPorCategoria as $categoriaNombre => &$productos) {
            foreach ($productos as &$producto) {
                $producto->en_promocion = false;
                $producto->precio_promocion = null;
                $producto->porcentaje_descuento = 0;
                
                // NUEVO: Para baldes, no aplicar promociones ya que son productos especiales
                if ($categoriaNombre === 'Baldes') {
                    continue; // Los baldes no participan en promociones regulares
                }
                
                // Buscar si el producto está en alguna promoción activa
                foreach ($promocionesActivas as $promocion) {
                    foreach ($promocion->productos as $promoProducto) {
                        if ($promoProducto->producto && isset($producto->id_producto) && $promoProducto->producto->id_producto == $producto->id_producto) {
                            // Calcular el precio promocional individual
                            $precioOriginal = $promoProducto->precio_original_referencia;
                            $precioPromocional = $this->calcularPrecioPromocional($precioOriginal, $promocion->descripcion_promocion);
                            $porcentajeDescuento = $this->calcularPorcentajeDescuento($precioOriginal, $promocion->descripcion_promocion);

                            $producto->en_promocion = true;
                            $producto->precio_promocion = $precioPromocional;
                            $producto->porcentaje_descuento = $porcentajeDescuento;
                            $producto->precio_original = $precioOriginal;
                            break 2;
                        }
                    }
                }
            }
        }

        // Log para debug
        Log::info('Datos finales para carta digital:', [
            'categorias_count' => $categorias->count(),
            'categorias_nombres' => $categorias->pluck('nombre')->toArray(),
            'productos_por_categoria' => array_map('count', $productosPorCategoria),
            'tiene_baldes' => isset($productosPorCategoria['Baldes']),
            'baldes_count' => isset($productosPorCategoria['Baldes']) ? count($productosPorCategoria['Baldes']) : 0
        ]);

        return view('carta_digital', compact('categorias', 'productosPorCategoria', 'promocionesParaCarta', 'iconos'));
    }

    // CORREGIDA: Generar baldes dinámicamente como en el mesero
    private function generarBaldesParaCarta()
    {
        try {
            // CORREGIDO: Usar los mismos nombres exactos que en el mesero
            $cervezasPequenas = productos::whereIn('nombre', [
                'Pilsen pequeña', 
                'Cuzqueña dorada pequeña', 
                'Cuzqueña trigo pequeña', 
                'Cuzqueña negra pequeña', 
                'Corona pequeña'
            ])->where('estado', 1)->get();

            $baldesGenerados = collect();

            Log::info('Generando baldes para carta:', [
                'cervezas_encontradas' => $cervezasPequenas->count(),
                'nombres' => $cervezasPequenas->pluck('nombre')->toArray()
            ]);

            // Generar baldes normales por cada cerveza que tenga stock suficiente
            foreach ($cervezasPequenas as $cerveza) {
                $stockBaldes = intval($cerveza->stock / 6);
                if ($stockBaldes > 0) {
                    $nombreBalde = 'Balde ' . str_replace(' pequeña', '', $cerveza->nombre);
                    $precioUnitario = 60.00; // PRECIO FIJO

                    $baldeProducto = (object)[
                        'id_producto' => 'balde_' . $cerveza->id_producto,
                        'nombre' => $nombreBalde,
                        'descripcion' => '6 cervezas ' . $cerveza->nombre . ' en balde con hielo',
                        'precio_unitario' => $precioUnitario,
                        'stock' => $stockBaldes,
                        'estado' => 1,
                        'categoria' => (object)['nombre' => 'Baldes'],
                        'area_destino' => 'bar',
                        'es_balde' => true,
                        'imagen_url' => null,
                        'unidad_medida' => 'Balde'
                    ];
                    $baldesGenerados->push($baldeProducto);
                }
            }

            // Agregar balde personalizado si hay suficientes cervezas
            $stockTotalCervezas = $cervezasPequenas->sum('stock');
            if ($stockTotalCervezas >= 6) {
                $baldePersonalizado = (object)[
                    'id_producto' => 'balde_personalizado',
                    'nombre' => 'Balde Personalizado',
                    'descripcion' => 'Elige hasta 6 cervezas pequeñas para tu balde',
                    'precio_unitario' => 60.00, // PRECIO FIJO
                    'stock' => 999,
                    'estado' => 1,
                    'categoria' => (object)['nombre' => 'Baldes'],
                    'area_destino' => 'bar',
                    'es_personalizado' => true,
                    'es_balde' => true,
                    'imagen_url' => null,
                    'unidad_medida' => 'Balde'
                ];
                $baldesGenerados->push($baldePersonalizado);
                
                Log::info('Balde personalizado agregado', [
                    'stock_total_cervezas' => $stockTotalCervezas
                ]);
            }

            Log::info('Total baldes generados para carta:', [
                'cantidad' => $baldesGenerados->count()
            ]);
            
            return $baldesGenerados;
            
        } catch (\Exception $e) {
            Log::error('Error al generar baldes para carta:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return collect(); // Retornar colección vacía en caso de error
        }
    }

    // Función para calcular precio promocional individual
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