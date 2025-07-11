<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

// Importar modelos
use App\Models\categorias_producto;
use App\Models\productos;
use App\Models\pedidos;
use App\Models\mesas;
use App\Models\usuarios;
use App\Models\pedido_detalles;
use App\Models\comprobantes;
use App\Models\pagos_pedido_detalle;
use App\Models\promociones;
use App\Models\promocion_productos;

class controller_mesero extends Controller
{
    public function ver_mozo_historial() 
    {
        $idUsuario = Auth::id();
        
        // Excluir pedidos que ya tienen comprobante (facturados)
        $pedidos = pedidos::with(['mesa', 'comprobante', 'detalles'])
            ->where('id_usuario_mesero', $idUsuario)
            ->whereDoesntHave('comprobante') // Sirve para solo mostrar pedidos SIN comprobante
            ->orderBy('fecha_hora_pedido', 'desc')
            ->get();

        
        return view('view_mozo.mozo_historial', compact('pedidos'));
    }

    public function ver_mozo_mesa() 
    {
        $mesas = mesas::with(['pedidos.detalles'])->get();
        $mesasInfo = [];
        
        foreach ($mesas as $mesa) {
            $pedidoActivo = $mesa->pedidos->where('estado_pedido', 'PENDIENTE')->first();
            $ocupada = $pedidoActivo ? true : false;
            $cantidadDetalles = $pedidoActivo ? $pedidoActivo->detalles->count() : 0;
            
            if ($ocupada && $mesa->estado !== 'ocupada') {
                $mesa->update(['estado' => 'ocupada']);
            } elseif (!$ocupada && $mesa->estado !== 'disponible') {
                $mesa->update(['estado' => 'disponible']);
            }
            
            $mesasInfo[$mesa->id_mesa] = [
                'ocupada' => $ocupada,
                'cantidadDetalles' => $cantidadDetalles,
            ];
        }

        return view('view_mozo.mozo_mesa', compact('mesas', 'mesasInfo'));
    }

    public function ver_mozo_pedido_mesa($idMesa)
    {
        $mesa = mesas::findOrFail($idMesa);

        $categoriasMesero = ['Piqueos', 'Cocteles', 'Licores', 'Bebidas', 'Cervezas', 'Jarras','Baldes'];

        $categorias_producto = categorias_producto::whereIn('nombre', $categoriasMesero)
            ->where('estado', 1)
            ->get();

        $productos = productos::whereHas('categoria', function($query) use ($categoriasMesero) {
            $query->whereIn('nombre', $categoriasMesero);
        })
        ->with('categoria')
        ->get();

        // Obtener cervezas pequeñas para calcular stock de baldes
        $cervezasPequenas = productos::whereIn('nombre', [
            'Pilsen pequeña', 
            'Cuzqueña dorada pequeña', 
            'Cuzqueña trigo pequeña', 
            'Cuzqueña negra pequeña', 
            'Corona pequeña'
        ])->get();

        // Crear productos de baldes dinámicamente - CORREGIDO PARA INCLUIR imagen_url
        $baldesProductos = collect();
        foreach ($cervezasPequenas as $cerveza) {
            $stockBaldes = intval($cerveza->stock / 6);
            if ($stockBaldes > 0) {
                $baldeProducto = [
                    'id_producto' => 'balde_' . $cerveza->id_producto,
                    'nombre' => 'Balde ' . str_replace(' pequeña', '', $cerveza->nombre),
                    'descripcion' => 'Balde de 6 cervezas ' . $cerveza->nombre,
                    'precio_unitario' => $cerveza->precio_unitario * 6,
                    'stock' => $stockBaldes,
                    'estado' => 1,
                    'categoria' => ['nombre' => 'Baldes'],
                    'area_destino' => 'bar',
                    'cerveza_base_id' => $cerveza->id_producto,
                    'es_balde' => true,
                    'imagen_url' => $cerveza->imagen_url, // AGREGADO: Usar imagen de la cerveza base
                    'unidad_medida' => 'unidad' // AGREGADO: Para evitar otros errores
                ];
                $baldesProductos->push((object)$baldeProducto);
            }
        }

        // Agregar producto "Balde Personalizado" - CORREGIDO PARA INCLUIR imagen_url
        $baldePersonalizado = [
            'id_producto' => 'balde_personalizado',
            'nombre' => 'Balde Personalizado',
            'descripcion' => 'Elige hasta 6 cervezas pequeñas',
            'precio_unitario' => 0,
            'stock' => $cervezasPequenas->sum('stock') >= 6 ? 999 : 0,
            'estado' => 1,
            'categoria' => ['nombre' => 'Baldes'],
            'area_destino' => 'bar',
            'es_personalizado' => true,
            'es_balde' => true,
            'imagen_url' => null, // AGREGADO: Balde personalizado sin imagen por defecto
            'unidad_medida' => 'unidad' // AGREGADO: Para evitar otros errores
        ];
        $baldesProductos->push((object)$baldePersonalizado);

        // Convertir productos a array antes de merge para evitar conflictos
        $productosArray = $productos->toArray();
        $baldesArray = $baldesProductos->toArray();
        
        // Combinar usando array_merge y luego convertir a colección
        $todosProductos = collect(array_merge($productosArray, $baldesArray));

        // Filtrar productos según su categoría - CORREGIDO
        $productos = $todosProductos->filter(function($producto) {
            $producto = (object)$producto; // Asegurar que sea objeto
            $categoria = is_array($producto->categoria) ? $producto->categoria['nombre'] : $producto->categoria->nombre;
            
            if ($categoria === 'Cocteles') {
                return $producto->estado == 1;
            } else {
                return $producto->estado == 1 && $producto->stock > 0;
            }
        });

        // Procesar promociones - CORREGIDO para manejar ambos tipos de productos
        $promocionesActivas = promociones::with(['productos.producto'])
            ->where('estado_promocion', 'activa')
            ->where('fecha_inicio', '<=', now())
            ->where('fecha_fin', '>=', now())
            ->get();

        $productos = $productos->map(function($producto) use ($promocionesActivas) {
            $producto = (object)$producto; // Asegurar que sea objeto
            
            // Solo aplicar promociones a productos normales (no baldes)
            if (!isset($producto->es_balde)) {
                $producto->en_promocion = false;
                $producto->precio_promocion = null;
                $producto->porcentaje_descuento = 0;
                $producto->precio_original = $producto->precio_unitario;
                
                foreach ($promocionesActivas as $promocion) {
                    foreach ($promocion->productos as $promoProducto) {
                        if ($promoProducto->producto && $promoProducto->producto->id_producto == $producto->id_producto) {
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
            
            return $producto;
        });

        // Crear variable para template
        $productosYaPedidos = [];

        return view('view_mozo.mozo_pedido', compact('mesa', 'categorias_producto', 'productos', 'cervezasPequenas', 'productosYaPedidos'));
    }

    public function ver_mozo_pedido_historial()
    {
        $idUsuario = Auth::id();
        $pedidosTemp = session('pedidos_temp', []); // Obtener pedidos temporales

        return view('view_mozo.mozo_pedido_historial', compact('pedidosTemp'));
    }

    public function procesar_mozo_pedido(Request $request)
    {
        $idMesa = $request->input('id_mesa');
        $productos = $request->input('productos', []);
        $editandoPedido = session('editando_pedido');
        
        $pedidosTemp = [];
        $totalPedido = 0;

        // Validar que haya productos seleccionados
        $productosSeleccionados = array_filter($productos, function($producto) {
            return isset($producto['seleccionado']) && $producto['seleccionado'] == 1;
        });

        if (empty($productosSeleccionados)) {
            return back()->with('error', 'Debe seleccionar al menos un producto.');
        }

        // Obtener promociones activas una sola vez antes del foreach
        $promocionesActivas = promociones::with(['productos.producto'])
            ->where('estado_promocion', 'activa')
            ->where('fecha_inicio', '<=', now())
            ->where('fecha_fin', '>=', now())
            // ->where('stock_promocion', '>', 0)
            ->get();

        // Obtener información detallada de cada producto seleccionado
        foreach ($productos as $idProducto => $datos) {
            if (isset($datos['seleccionado']) && $datos['seleccionado'] == 1) {
                // Verificar si es un balde
                if (strpos($idProducto, 'balde_') === 0) {
                    if ($idProducto === 'balde_personalizado') {
                        // Procesar balde personalizado
                        $configuracionBalde = json_decode($datos['configuracion_balde'] ?? '{}', true);
                        
                        if (empty($configuracionBalde)) {
                            return back()->with('error', 'Configuración del balde personalizado no encontrada.');
                        }
                        
                        $totalCervezas = array_sum(array_column($configuracionBalde, 'cantidad'));
                        if ($totalCervezas > 6) {
                            return back()->with('error', 'Un balde no puede tener más de 6 cervezas.');
                        }
                        
                        // Calcular precio total y generar nombre
                        $precioTotal = 0;
                        $nombresBalde = [];
                        
                        foreach ($configuracionBalde as $cervezaId => $config) {
                            $cerveza = productos::find($cervezaId);
                            if ($cerveza) {
                                $precioTotal += $config['precio'] * $config['cantidad'];
                                $nombresBalde[] = $config['cantidad'] . 'x ' . $cerveza->nombre;
                                
                                // Validar stock
                                if ($cerveza->stock < $config['cantidad']) {
                                    return back()->with('error', "Stock insuficiente para {$cerveza->nombre}. Stock disponible: {$cerveza->stock}");
                                }
                            }
                        }
                        
                        $nombreBalde = 'Balde Personalizado (' . implode(', ', $nombresBalde) . ')';
                        
                        $pedidosTemp[] = [
                            'id_producto' => $idProducto,
                            'nombre' => $nombreBalde,
                            'cantidad' => 1,
                            'precio' => $precioTotal,
                            'subtotal' => $precioTotal,
                            'area_destino' => 'bar',
                            'configuracion_balde' => $configuracionBalde
                        ];
                        
                        $totalPedido += $precioTotal;
                        
                    } else {
                        // Procesar balde normal - CORREGIDO PRECIO
                        $cervezaBaseId = str_replace('balde_', '', $idProducto);
                        $cervezaBase = productos::find($cervezaBaseId);
                        
                        if (!$cervezaBase) {
                            return back()->with('error', 'Cerveza base del balde no encontrada.');
                        }
                        
                        $cantidad = (int)($datos['cantidad'] ?? 1);
                        $cervezasNecesarias = $cantidad * 6;
                        
                        if ($cervezaBase->stock < $cervezasNecesarias) {
                            return back()->with('error', "Stock insuficiente para el balde. Se necesitan {$cervezasNecesarias} cervezas, disponibles: {$cervezaBase->stock}");
                        }
                        
                        $nombreBalde = 'Balde ' . str_replace(' pequeña', '', $cervezaBase->nombre);
                        $precioUnitario = $cervezaBase->precio_unitario * 6; // CAMBIADO: 6 cervezas, no 5
                        $subtotal = $precioUnitario * $cantidad;
                        
                        $pedidosTemp[] = [
                            'id_producto' => $idProducto,
                            'nombre' => $nombreBalde,
                            'cantidad' => $cantidad,
                            'precio' => $precioUnitario,
                            'subtotal' => $subtotal,
                            'area_destino' => 'bar',
                            'cerveza_base_id' => $cervezaBaseId
                        ];
                        
                        $totalPedido += $subtotal;
                    }
                } else {
                    // Procesar producto normal (código existente)
                    $producto = productos::with('categoria')->find($idProducto);
                    
                    if ($producto) {
                        $cantidad = (int)($datos['cantidad'] ?? 1);

                        if ($editandoPedido) {
                            // --- Calcular precio promocional si aplica ---
                            $precioUnitario = $producto->precio_unitario;
                            foreach ($promocionesActivas as $promocion) {
                                foreach ($promocion->productos as $promoProducto) {
                                    if ($promoProducto->producto && $promoProducto->producto->id_producto == $producto->id_producto) {
                                        $precioOriginal = $promoProducto->precio_original_referencia;
                                        $precioUnitario = $this->calcularPrecioPromocional($precioOriginal, $promocion->descripcion_promocion);
                                        break 2;
                                    }
                                }
                            }
                            $subtotal = $precioUnitario * $cantidad;
                            $totalPedido += $subtotal;

                            // Validación diferenciada para cocteles
                            if ($producto->categoria->nombre === 'Cocteles') {
                                if ($producto->estado != 1) {
                                    return back()->with('error', "El coctel {$producto->nombre} no está disponible actualmente.");
                                }
                            } else {
                                if ($producto->stock < $cantidad) {
                                    return back()->with('error', "Stock insuficiente para {$producto->nombre}. Stock disponible: {$producto->stock}");
                                }
                            }

                            $pedidosTemp[] = [
                                'id_producto' => $idProducto,
                                'nombre' => $producto->nombre,
                                'cantidad' => $cantidad,
                                'precio' => $precioUnitario,
                                'subtotal' => $subtotal,
                                'area_destino' => $producto->area_destino
                            ];
                        } else {
                            // Flujo normal para pedidos nuevos
                            // --- Calcular precio promocional si aplica ---
                            $precioUnitario = $producto->precio_unitario;
                            foreach ($promocionesActivas as $promocion) {
                                foreach ($promocion->productos as $promoProducto) {
                                    if ($promoProducto->producto && $promoProducto->producto->id_producto == $producto->id_producto) {
                                        $precioOriginal = $promoProducto->precio_original_referencia;
                                        $precioUnitario = $this->calcularPrecioPromocional($precioOriginal, $promocion->descripcion_promocion);
                                        break 2;
                                    }
                                }
                            }
                            $subtotal = $precioUnitario * $cantidad;
                            $totalPedido += $subtotal;

                            // Validación diferenciada para cocteles
                            if ($producto->categoria->nombre === 'Cocteles') {
                                if ($producto->estado != 1) {
                                    return back()->with('error', "El coctel {$producto->nombre} no está disponible actualmente.");
                                }
                            } else {
                                if ($producto->stock < $cantidad) {
                                    return back()->with('error', "Stock insuficiente para {$producto->nombre}. Stock disponible: {$producto->stock}");
                                }
                            }

                            $pedidosTemp[] = [
                                'id_producto' => $idProducto,
                                'nombre' => $producto->nombre,
                                'cantidad' => $cantidad,
                                'precio' => $precioUnitario,
                                'subtotal' => $subtotal,
                                'area_destino' => $producto->area_destino
                            ];
                        }
                    }
                }
            }
        }

        // Si no hay productos nuevos para agregar, mostrar mensaje
        if (empty($pedidosTemp) && $editandoPedido) {
            return back()->with('error', 'No se han seleccionado productos nuevos para agregar al pedido.');
        }

        // Guardar en sesión
        session([
            'pedidos_temp' => $pedidosTemp,
            'mesa_temp' => $idMesa,
            'total_temp' => $totalPedido
        ]);
        
        if ($editandoPedido) {
            session(['agregando_a_pedido' => $editandoPedido]);
        }
        
        return redirect()->route('vista.mozo_pedido_historial');
    }

    public function confirmar_mozo_pedido(Request $request)
    {
        $pedidosTemp = session('pedidos_temp', []);
        $idMesa = session('mesa_temp');
        $totalPedido = session('total_temp', 0);
        $notasAdicionales = $request->input('notas_adicionales', '');
        $agregandoAPedido = session('agregando_a_pedido');

        if (empty($pedidosTemp) || !$idMesa) {
            return redirect()->route('vista.mozo_mesa')->with('error', 'No hay pedidos para procesar.');
        }

        try {
            if ($agregandoAPedido) {
                // Agregar productos a pedido existente
                $pedido = pedidos::findOrFail($agregandoAPedido);
                
                foreach ($pedidosTemp as $item) {
                    $this->procesarItemPedido($item, $pedido->id_pedido);
                }

                // Actualizar total del pedido
                $nuevoTotal = $pedido->detalles()->sum('subtotal');
                $pedido->update(['total_pedido' => $nuevoTotal]);

                session()->forget(['pedidos_temp', 'mesa_temp', 'total_temp', 'editando_pedido', 'productos_ya_pedidos', 'agregando_a_pedido']);
                
                return redirect()->route('vista.mozo_historial')->with('success', 'Productos agregados al pedido exitosamente.');
                
            } else {
                // Crear nuevo pedido
                $pedido = pedidos::create([
                    'id_mesa' => $idMesa,
                    'id_usuario_mesero' => Auth::id(),
                    'fecha_hora_pedido' => now(),
                    'estado_pedido' => 'PENDIENTE',
                    'total_pedido' => $totalPedido,
                    'notas_adicionales' => $notasAdicionales
                ]);

                foreach ($pedidosTemp as $item) {
                    $this->procesarItemPedido($item, $pedido->id_pedido);
                }

                // Marcar la mesa como ocupada
                $mesa = mesas::find($idMesa);
                if ($mesa) {
                    $mesa->update(['estado' => 'ocupada']);
                }

                session()->forget(['pedidos_temp', 'mesa_temp', 'total_temp']);
                
                return redirect()->route('vista.mozo_historial')->with('success', 'Pedido creado exitosamente.');
            }
            
        } catch (\Exception $e) {
            Log::error('Error al confirmar pedido:', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al procesar el pedido: ' . $e->getMessage());
        }
    }

    // NUEVA FUNCIÓN HELPER PARA PROCESAR ITEMS
    private function procesarItemPedido($item, $idPedido)
    {
        if (strpos($item['id_producto'], 'balde_') === 0) {
            if ($item['id_producto'] === 'balde_personalizado') {
                // BALDE PERSONALIZADO
                $configuracionBalde = $item['configuracion_balde'];
                
                // Decrementar stock de cada cerveza
                foreach ($configuracionBalde as $cervezaId => $config) {
                    $cerveza = productos::find($cervezaId);
                    if ($cerveza && $cerveza->stock >= $config['cantidad']) {
                        $cerveza->decrement('stock', $config['cantidad']);
                    } else {
                        throw new \Exception("Stock insuficiente para {$cerveza->nombre}");
                    }
                }
                
                // CREAR DETALLE CON CAMPOS NUEVOS
                pedido_detalles::create([
                    'id_pedido' => $idPedido,
                    'id_producto' => null, // NULL para baldes
                    'cantidad' => $item['cantidad'],
                    'precio_unitario_momento' => $item['precio'],
                    'subtotal' => $item['subtotal'],
                    'estado_item' => 'SOLICITADO',
                    'id_usuario_preparador' => $this->asignarPreparador('bar'),
                    'fecha_creacion' => now(),
                    'nombre_producto_personalizado' => $item['nombre'],
                    'tipo_producto' => 'balde_personalizado',
                    'configuracion_especial' => $configuracionBalde
                ]);
                
            } else {
                // BALDE NORMAL
                $cervezaBaseId = str_replace('balde_', '', $item['id_producto']);
                $cervezaBase = productos::find($cervezaBaseId);
                
                if ($cervezaBase) {
                    $cervezasNecesarias = $item['cantidad'] * 6;
                    if ($cervezaBase->stock >= $cervezasNecesarias) {
                        $cervezaBase->decrement('stock', $cervezasNecesarias);
                    } else {
                        throw new \Exception("Stock insuficiente para el balde de {$cervezaBase->nombre}");
                    }
                }
                
                // CREAR DETALLE CON CAMPOS NUEVOS
                pedido_detalles::create([
                    'id_pedido' => $idPedido,
                    'id_producto' => null, // NULL para baldes
                    'cantidad' => $item['cantidad'],
                    'precio_unitario_momento' => $item['precio'],
                    'subtotal' => $item['subtotal'],
                    'estado_item' => 'SOLICITADO',
                    'id_usuario_preparador' => $this->asignarPreparador('bar'),
                    'fecha_creacion' => now(),
                    'nombre_producto_personalizado' => $item['nombre'],
                    'tipo_producto' => 'balde_normal',
                    'id_producto_base' => $cervezaBaseId,
                    'configuracion_especial' => ['cerveza_base_id' => $cervezaBaseId, 'cantidad_cervezas' => 6]
                ]);
            }
        } else {
            // PRODUCTO NORMAL (SIN CAMBIOS)
            $producto = productos::with('categoria')->find($item['id_producto']);
            
            if (!$producto) {
                throw new \Exception("Producto no encontrado: {$item['nombre']}");
            }

            // Manejo diferenciado de stock para cocteles
            if ($producto->categoria->nombre !== 'Cocteles') {
                if ($producto->stock < $item['cantidad']) {
                    throw new \Exception("Stock insuficiente para {$producto->nombre}");
                }
                $producto->decrement('stock', $item['cantidad']);
            }

            $idPreparador = $this->asignarPreparador($producto->area_destino);

            // CREAR DETALLE NORMAL
            pedido_detalles::create([
                'id_pedido' => $idPedido,
                'id_producto' => $item['id_producto'],
                'cantidad' => $item['cantidad'],
                'precio_unitario_momento' => $item['precio'],
                'subtotal' => $item['subtotal'],
                'estado_item' => 'SOLICITADO',
                'id_usuario_preparador' => $idPreparador,
                'fecha_creacion' => now(),
                'tipo_producto' => 'normal'
            ]);
        }
    }

    private function asignarPreparador($areaDestino)
    {
        // Asignar preparador según el área de destino del producto
        switch ($areaDestino) {
            case 'cocina':
                // Buscar un cocinero disponible (puedes implementar lógica más compleja)
                $preparador = usuarios::where('rol', 'cocinero')->where('estado', 1)->first();
                break;
            case 'bar':
                // Buscar un bartender disponible
                $preparador = usuarios::where('rol', 'bartender')->where('estado', 1)->first();
                break;
            case 'ambos':
                // Para productos que pueden ser preparados en ambas áreas, asignar a cocina por defecto
                $preparador = usuarios::where('rol', 'cocinero')->where('estado', 1)->first();
                break;
            default:
                $preparador = null;
                break;
        }

        return $preparador ? $preparador->id_usuario : null;
    }

    // GESTIÓN DE PEDIDOS
    public function ver_pedido($idPedido)
    {
        $pedido = pedidos::with(['mesa', 'detalles.producto', 'mesero'])->findOrFail($idPedido);
        
        return view('view_mozo.mozo_ver_pedido', compact('pedido'));
    }

    public function editar_pedido($idPedido)
    {
        $pedido = pedidos::with(['mesa', 'detalles.producto', 'detalles.producto_base'])->findOrFail($idPedido);

        // Obtener solo mesas disponibles (sin pedidos pendientes) + la mesa actual del pedido
        $mesas = mesas::whereDoesntHave('pedidos', function($query) use ($idPedido) {
            $query->where('estado_pedido', 'PENDIENTE')
                  ->where('id_pedido', '!=', $idPedido); // Excluir el pedido actual
        })->get();
        
        // Separar productos editables de no editables
        $detallesEditables = $pedido->detalles->where('estado_item', '!=', 'LISTO_PARA_ENTREGA');
        $detallesNoEditables = $pedido->detalles->where('estado_item', 'LISTO_PARA_ENTREGA');
        
        // Agrupar productos editables por categoría
        $productosPorCategoria = [];
        foreach ($detallesEditables as $detalle) {
            // CORREGIDO: Manejar diferentes tipos de productos
            if ($detalle->tipo_producto === 'balde_personalizado' || $detalle->tipo_producto === 'balde_normal') {
                // Para baldes, usar la categoría "Baldes"
                $categoriaNombre = 'Baldes';
                $categoriaId = 7; // ID de la categoría Baldes
                
                if (!isset($productosPorCategoria[$categoriaId])) {
                    $productosPorCategoria[$categoriaId] = [
                        'categoria' => (object)['id_categoria_producto' => $categoriaId, 'nombre' => $categoriaNombre],
                        'productos' => []
                    ];
                }
            } else {
                // Para productos normales
                $categoria = $detalle->producto->categoria;
                if (!isset($productosPorCategoria[$categoria->id_categoria_producto])) {
                    $productosPorCategoria[$categoria->id_categoria_producto] = [
                        'categoria' => $categoria,
                        'productos' => []
                    ];
                }
            }
            
            $categoriaId = $detalle->tipo_producto === 'balde_personalizado' || $detalle->tipo_producto === 'balde_normal' ? 7 : $categoria->id_categoria_producto;
            $productosPorCategoria[$categoriaId]['productos'][] = $detalle;
        }

        // Agrupar productos no editables por categoría
        $productosNoEditablesPorCategoria = [];
        foreach ($detallesNoEditables as $detalle) {
            // CORREGIDO: Manejar diferentes tipos de productos
            if ($detalle->tipo_producto === 'balde_personalizado' || $detalle->tipo_producto === 'balde_normal') {
                // Para baldes, usar la categoría "Baldes"
                $categoriaNombre = 'Baldes';
                $categoriaId = 7; // ID de la categoría Baldes
                
                if (!isset($productosNoEditablesPorCategoria[$categoriaId])) {
                    $productosNoEditablesPorCategoria[$categoriaId] = [
                        'categoria' => (object)['id_categoria_producto' => $categoriaId, 'nombre' => $categoriaNombre],
                        'productos' => []
                    ];
                }
            } else {
                // Para productos normales
                $categoria = $detalle->producto->categoria;
                if (!isset($productosNoEditablesPorCategoria[$categoria->id_categoria_producto])) {
                    $productosNoEditablesPorCategoria[$categoria->id_categoria_producto] = [
                        'categoria' => $categoria,
                        'productos' => []
                    ];
                }
            }
            
            $categoriaId = $detalle->tipo_producto === 'balde_personalizado' || $detalle->tipo_producto === 'balde_normal' ? 7 : $categoria->id_categoria_producto;
            $productosNoEditablesPorCategoria[$categoriaId]['productos'][] = $detalle;
        }
        
        // Obtener promociones activas
        $promocionesActivas = promociones::with(['productos.producto'])
            ->where('estado_promocion', 'activa')
            ->where('fecha_inicio', '<=', now())
            ->where('fecha_fin', '>=', now())
            ->get();

        // Recalcular atributos de promoción para cada producto editable NORMAL
        foreach ($productosPorCategoria as &$categoriaData) {
            foreach ($categoriaData['productos'] as $detalle) {
                // SOLO aplicar promociones a productos normales
                if ($detalle->tipo_producto === 'normal' && $detalle->producto) {
                    $detalle->producto->en_promocion = false;
                    $detalle->producto->precio_promocion = null;
                    $detalle->producto->porcentaje_descuento = 0;
                    
                    foreach ($promocionesActivas as $promocion) {
                        foreach ($promocion->productos as $promoProducto) {
                            if ($promoProducto->producto && $promoProducto->producto->id_producto == $detalle->producto->id_producto) {
                                $precioOriginal = $promoProducto->precio_original_referencia;
                                $precioPromocional = $this->calcularPrecioPromocional($precioOriginal, $promocion->descripcion_promocion);
                                $porcentajeDescuento = $this->calcularPorcentajeDescuento($precioOriginal, $promocion->descripcion_promocion);

                                $detalle->producto->en_promocion = true;
                                $detalle->producto->precio_promocion = $precioPromocional;
                                $detalle->producto->porcentaje_descuento = $porcentajeDescuento;
                                $detalle->producto->precio_original = $precioOriginal;
                                break 2;
                            }
                        }
                    }
                }
            }
        }
        unset($categoriaData, $detalle); // Limpia referencias

        return view('view_mozo.mozo_editar_pedido', compact(
            'pedido', 
            'mesas', 
            'productosPorCategoria', 
            'productosNoEditablesPorCategoria'
        ));
    }

    public function actualizar_pedido(Request $request, $idPedido)
    {
        $pedido = pedidos::with('detalles')->findOrFail($idPedido);
        $nuevaMesa = $request->input('id_mesa');
        $productosModificados = $request->input('productos', []);

        try {
            // VALIDACIÓN ADICIONAL: Verificar si el pedido tiene comprobante
            if ($pedido->comprobante !== null) {
                return back()->with('error', 'No se puede editar un pedido que ya ha sido facturado.');
            }

            // 1. Actualizar mesa si cambió
            if ($nuevaMesa != $pedido->id_mesa) {
                $pedido->update(['id_mesa' => $nuevaMesa]);
            }

            // 2. Procesar modificaciones de productos
            foreach ($productosModificados as $idDetalle => $datos) {
                $detalle = pedido_detalles::find($idDetalle);
                if (!$detalle) continue;

                // VALIDACIÓN: No permitir editar productos que ya están listos para entrega
                if ($detalle->estado_item === 'LISTO_PARA_ENTREGA') {
                    return back()->with('error', "No se puede modificar el producto '{$detalle->producto->nombre}' porque ya está listo para entrega.");
                }

                $accion = $datos['accion'] ?? '';
                $producto = $detalle->producto;

                if ($accion === 'eliminar') {
                    // Devolver stock al producto
                    $producto->increment('stock', $detalle->cantidad);
                    $detalle->delete();
                } elseif ($accion === 'modificar') {
                    $nuevaCantidad = (int)($datos['cantidad'] ?? $detalle->cantidad);
                    $diferencia = $nuevaCantidad - $detalle->cantidad;

                    // Validar stock disponible
                    if ($diferencia > 0 && $producto->stock < $diferencia) {
                        return back()->with('error', "Stock insuficiente para {$producto->nombre}");
                    }

                    // Actualizar stock
                    $producto->decrement('stock', $diferencia);
                    
                    // Actualizar detalle
                    $detalle->update([
                        'cantidad' => $nuevaCantidad,
                        'subtotal' => $nuevaCantidad * $detalle->precio_unitario_momento
                    ]);
                }
            }

            // 3. Recalcular total del pedido
            $nuevoTotal = $pedido->detalles()->sum('subtotal');
            $pedido->update(['total_pedido' => $nuevoTotal]);

            return redirect()->route('vista.mozo_historial')->with('success', 'Pedido actualizado exitosamente.');

        } catch (\Exception $e) {
            return back()->with('error', 'Error al actualizar el pedido: ' . $e->getMessage());
        }
    }

    // MODIFICADO: También aplicar lógica diferenciada en agregar productos
    public function agregar_productos_pedido($idPedido)
    {
        $pedido = pedidos::with('mesa')->findOrFail($idPedido);
        
        $categoriasMesero = ['Piqueos', 'Cocteles', 'Licores', 'Bebidas', 'Cervezas', 'Jarras', 'Baldes'];
        $categorias_producto = categorias_producto::whereIn('nombre', $categoriasMesero)
            ->where('estado', 1)
            ->get();
        
        $productos = productos::whereHas('categoria', function($query) use ($categoriasMesero) {
            $query->whereIn('nombre', $categoriasMesero);
        })
        ->with('categoria')
        ->get();

        // Obtener cervezas pequeñas para calcular stock de baldes
        $cervezasPequenas = productos::whereIn('nombre', [
            'Pilsen pequeña', 
            'Cuzqueña dorada pequeña', 
            'Cuzqueña trigo pequeña', 
            'Cuzqueña negra pequeña', 
            'Corona pequeña'
        ])->get();

        // Crear productos de baldes dinámicamente - MISMO PATRÓN CORREGIDO
        $baldesProductos = collect();
        foreach ($cervezasPequenas as $cerveza) {
            $stockBaldes = intval($cerveza->stock / 6);
            if ($stockBaldes > 0) {
                $baldeProducto = [
                    'id_producto' => 'balde_' . $cerveza->id_producto,
                    'nombre' => 'Balde ' . str_replace(' pequeña', '', $cerveza->nombre),
                    'descripcion' => 'Balde de 6 cervezas ' . $cerveza->nombre,
                    'precio_unitario' => $cerveza->precio_unitario * 6,
                    'stock' => $stockBaldes,
                    'estado' => 1,
                    'categoria' => ['nombre' => 'Baldes'],
                    'area_destino' => 'bar',
                    'cerveza_base_id' => $cerveza->id_producto,
                    'es_balde' => true,
                    'imagen_url' => $cerveza->imagen_url, // AGREGADO: Usar imagen de la cerveza base
                    'unidad_medida' => 'unidad' // AGREGADO: Para evitar otros errores
                ];
                $baldesProductos->push((object)$baldeProducto);
            }
        }

        // Agregar producto "Balde Personalizado"
        $baldePersonalizado = [
            'id_producto' => 'balde_personalizado',
            'nombre' => 'Balde Personalizado',
            'descripcion' => 'Elige hasta 6 cervezas pequeñas',
            'precio_unitario' => 0,
            'stock' => $cervezasPequenas->sum('stock') >= 6 ? 999 : 0,
            'estado' => 1,
            'categoria' => ['nombre' => 'Baldes'],
            'area_destino' => 'bar',
            'es_personalizado' => true,
            'es_balde' => true,
            'imagen_url' => null, // AGREGADO: Balde personalizado sin imagen por defecto
            'unidad_medida' => 'unidad' // AGREGADO: Para evitar otros errores
        ];
        $baldesProductos->push((object)$baldePersonalizado);

        // Convertir y combinar correctamente
        $productosArray = $productos->toArray();
        $baldesArray = $baldesProductos->toArray();
        $todosProductos = collect(array_merge($productosArray, $baldesArray));
        
        // Filtrar productos según su categoría
        $productos = $todosProductos->filter(function($producto) {
            $producto = (object)$producto;
            $categoria = is_array($producto->categoria) ? $producto->categoria['nombre'] : $producto->categoria->nombre;
            
            if ($categoria === 'Cocteles') {
                return $producto->estado == 1;
            } else {
                return $producto->estado == 1 && $producto->stock > 0;
            }
        });

        // Aplicar promociones
        $promocionesActivas = promociones::with(['productos.producto'])
            ->where('estado_promocion', 'activa')
            ->where('fecha_inicio', '<=', now())
            ->where('fecha_fin', '>=', now())
            ->get();

        $productos = $productos->map(function($producto) use ($promocionesActivas) {
            $producto = (object)$producto;
            
            if (!isset($producto->es_balde)) {
                $producto->en_promocion = false;
                $producto->precio_promocion = null;
                $producto->porcentaje_descuento = 0;
                $producto->precio_original = $producto->precio_unitario;
                
                foreach ($promocionesActivas as $promocion) {
                    foreach ($promocion->productos as $promoProducto) {
                        if ($promoProducto->producto && $promoProducto->producto->id_producto == $producto->id_producto) {
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
            
            return $producto;
        });

        session([
            'editando_pedido' => $idPedido,
            'productos_ya_pedidos' => []
        ]);

        $productosYaPedidos = [];

        return view('view_mozo.mozo_pedido', compact('pedido', 'categorias_producto', 'productos','cervezasPequenas', 'productosYaPedidos'))
            ->with('mesa', $pedido->mesa)
            ->with('editando', true);
    }


    public function eliminar_pedido($idPedido)
    {
        $pedido = pedidos::with(['detalles.producto', 'detalles.producto_base'])->findOrFail($idPedido);
        
        try {
            // VALIDACIÓN: No permitir eliminar pedidos con productos listos para entrega
            $productosListos = $pedido->detalles->where('estado_item', 'LISTO_PARA_ENTREGA');
            if ($productosListos->count() > 0) {
                return back()->with('error', 'No se puede eliminar un pedido que tiene productos listos para entrega.');
            }
            
            // VALIDACIÓN: No permitir eliminar pedidos que ya tienen comprobante
            if ($pedido->comprobante) {
                return back()->with('error', 'No se puede eliminar un pedido que ya ha sido facturado.');
            }
            
            // Devolver stock de todos los productos según su tipo
            foreach ($pedido->detalles as $detalle) {
                if ($detalle->tipo_producto === 'balde_personalizado') {
                    // Para baldes personalizados, devolver stock de cada cerveza
                    if ($detalle->configuracion_especial) {
                        foreach ($detalle->configuracion_especial as $cervezaId => $config) {
                            $cerveza = productos::find($cervezaId);
                            if ($cerveza) {
                                $cerveza->increment('stock', $config['cantidad'] * $detalle->cantidad);
                            }
                        }
                    }
                } elseif ($detalle->tipo_producto === 'balde_normal') {
                    // Para baldes normales, devolver stock a la cerveza base
                    if ($detalle->producto_base) {
                        $cervezasDevolver = $detalle->cantidad * 6; // 6 cervezas por balde
                        $detalle->producto_base->increment('stock', $cervezasDevolver);
                    }
                } else {
                    // Para productos normales (excluyendo cocteles)
                    if ($detalle->producto && $detalle->producto->categoria->nombre !== 'Cocteles') {
                        $detalle->producto->increment('stock', $detalle->cantidad);
                    }
                }
            }
            
            // Liberar la mesa (marcarla como disponible con valor ENUM: 'disponible')
            $mesa = $pedido->mesa;
            if ($mesa) {
                $mesa->update(['estado' => 'disponible']);
            }
            
            // Eliminar detalles primero
            $pedido->detalles()->delete();
            
            // Eliminar el pedido
            $pedido->delete();
            
            return redirect()->route('vista.mozo_historial')
                ->with('success', 'Pedido eliminado correctamente.');
                
        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar el pedido: ' . $e->getMessage());
        }
    }

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

    private function calcularPorcentajeDescuento($precioOriginal, $descripcionPromocion)
    {
        $precioPromocional = $this->calcularPrecioPromocional($precioOriginal, $descripcionPromocion);
        if ($precioOriginal > 0 && $precioPromocional < $precioOriginal) {
            return round((($precioOriginal - $precioPromocional) / $precioOriginal) * 100);
        }
        return 0;
    }
}