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

class controller_mesero extends Controller
{
    public function ver_mozo_historial() 
    {
        $idUsuario = Auth::id();
        $pedidos = pedidos::with(['mesa', 'comprobante', 'detalles'])
            ->where('id_usuario_mesero', $idUsuario)
            ->orderBy('fecha_hora_pedido', 'desc')
            ->get();

        foreach ($pedidos as $pedido) {
            $tieneComprobante = $pedido->comprobante !== null;
            
            if ($tieneComprobante && $pedido->estado_pedido === 'PENDIENTE') {
                $pedido->update(['estado_pedido' => 'PAGADO']);
                $pedido->refresh();
            }
            elseif (!$tieneComprobante && $pedido->estado_pedido === 'PAGADO') {
                $pedido->update(['estado_pedido' => 'PENDIENTE']);
                $pedido->refresh();
            }
        }

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
        
        // Traer todos los productos (cocina y barra)
        $productos = productos::all();
        
        // Traer todas las categorías que tengan productos
        $categorias_producto = categorias_producto::whereIn('id_categoria_producto', 
            $productos->pluck('id_categoria_producto')->unique()
        )->get();

        return view('view_mozo.mozo_pedido', compact('mesa', 'categorias_producto', 'productos'));
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
        $productosYaPedidos = session('productos_ya_pedidos', []);
        
        $pedidosTemp = [];
        $totalPedido = 0;

        // Validar que haya productos seleccionados
        $productosSeleccionados = array_filter($productos, function($producto) {
            return isset($producto['seleccionado']) && $producto['seleccionado'] == 1;
        });

        if (empty($productosSeleccionados)) {
            return back()->with('error', 'Debe seleccionar al menos un producto.');
        }

        // Obtener información detallada de cada producto seleccionado
        foreach ($productos as $idProducto => $datos) {
            if (isset($datos['seleccionado']) && $datos['seleccionado'] == 1) {
                $producto = productos::find($idProducto);
                
                if ($producto) {
                    $cantidad = (int)($datos['cantidad'] ?? 1);
                    
                    // Si estamos agregando a un pedido existente, solo procesar productos nuevos o con cantidad adicional
                    if ($editandoPedido) {
                        $cantidadYaPedida = $productosYaPedidos[$idProducto] ?? 0;
                        // Solo agregar la diferencia si es mayor que lo ya pedido
                        if ($cantidad <= $cantidadYaPedida) {
                            continue; // Saltar este producto, no hay cantidad adicional
                        }
                        $cantidadAdicional = $cantidad - $cantidadYaPedida;
                        $subtotal = $producto->precio_unitario * $cantidadAdicional;
                        $totalPedido += $subtotal;
                        
                        // Validar stock disponible solo para la cantidad adicional
                        if ($producto->stock < $cantidadAdicional) {
                            return back()->with('error', "Stock insuficiente para {$producto->nombre}. Stock disponible: {$producto->stock}");
                        }
                        
                        $pedidosTemp[] = [
                            'id_producto' => $idProducto,
                            'nombre' => $producto->nombre,
                            'cantidad' => $cantidadAdicional,
                            'precio' => $producto->precio_unitario,
                            'subtotal' => $subtotal,
                            'area_destino' => $producto->area_destino
                        ];
                    } else {
                        // Flujo normal para pedidos nuevos
                        $subtotal = $producto->precio_unitario * $cantidad;
                        $totalPedido += $subtotal;
                        
                        // Validar stock disponible
                        if ($producto->stock < $cantidad) {
                            return back()->with('error', "Stock insuficiente para {$producto->nombre}. Stock disponible: {$producto->stock}");
                        }
                        
                        $pedidosTemp[] = [
                            'id_producto' => $idProducto,
                            'nombre' => $producto->nombre,
                            'cantidad' => $cantidad,
                            'precio' => $producto->precio_unitario,
                            'subtotal' => $subtotal,
                            'area_destino' => $producto->area_destino
                        ];
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
                    $producto = productos::find($item['id_producto']);
                    
                    if ($producto->stock < $item['cantidad']) {
                        throw new \Exception("Stock insuficiente para {$producto->nombre}");
                    }

                    $idPreparador = $this->asignarPreparador($producto->area_destino);

                    // Verificar si ya existe un detalle para este producto en el pedido
                    $detalleExistente = pedido_detalles::where('id_pedido', $pedido->id_pedido)
                        ->where('id_producto', $item['id_producto'])
                        ->first();

                    if ($detalleExistente) {
                        // Actualizar el detalle existente
                        $nuevaCantidad = $detalleExistente->cantidad + $item['cantidad'];
                        $nuevoSubtotal = $detalleExistente->subtotal + $item['subtotal'];
                        
                        $detalleExistente->update([
                            'cantidad' => $nuevaCantidad,
                            'subtotal' => $nuevoSubtotal
                        ]);
                    } else {
                        // Crear nuevo detalle
                        pedido_detalles::create([
                            'id_pedido' => $pedido->id_pedido,
                            'id_producto' => $item['id_producto'],
                            'cantidad' => $item['cantidad'],
                            'precio_unitario_momento' => $item['precio'],
                            'subtotal' => $item['subtotal'],
                            'estado_item' => 'SOLICITADO',
                            'id_usuario_preparador' => $idPreparador,
                            'fecha_creacion' => now()
                        ]);
                    }

                    $producto->decrement('stock', $item['cantidad']);
                }

                // Actualizar total del pedido
                $nuevoTotal = $pedido->detalles()->sum('subtotal');
                $pedido->update(['total_pedido' => $nuevoTotal]);

                // Limpiar sesión
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
                    $producto = productos::find($item['id_producto']);
                    
                    if ($producto->stock < $item['cantidad']) {
                        throw new \Exception("Stock insuficiente para {$producto->nombre}");
                    }

                    $idPreparador = $this->asignarPreparador($producto->area_destino);

                    pedido_detalles::create([
                        'id_pedido' => $pedido->id_pedido,
                        'id_producto' => $item['id_producto'],
                        'cantidad' => $item['cantidad'],
                        'precio_unitario_momento' => $item['precio'],
                        'subtotal' => $item['subtotal'],
                        'estado_item' => 'SOLICITADO',
                        'id_usuario_preparador' => $idPreparador,
                        'fecha_creacion' => now()
                    ]);

                    $producto->decrement('stock', $item['cantidad']);
                }

                // Marcar la mesa como ocupada (valor ENUM: 'ocupada')
                $mesa = mesas::find($idMesa);
                if ($mesa) {
                    $mesa->update(['estado' => 'ocupada']);
                }

                session()->forget(['pedidos_temp', 'mesa_temp', 'total_temp']);

                return redirect()->route('vista.mozo_mesa')->with('success', 'Pedido registrado exitosamente.');
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Error al procesar el pedido: ' . $e->getMessage());
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
        $pedido = pedidos::with(['mesa', 'detalles.producto'])->findOrFail($idPedido);
        
        // Obtener solo mesas disponibles (sin pedidos pendientes) + la mesa actual del pedido
        $mesas = mesas::whereDoesntHave('pedidos', function($query) use ($idPedido) {
            $query->where('estado_pedido', 'PENDIENTE')
                  ->where('id_pedido', '!=', $idPedido); // Excluir el pedido actual
        })->get();
        
        // Agrupar productos por categoría
        $productosPorCategoria = [];
        foreach ($pedido->detalles as $detalle) {
            $categoria = $detalle->producto->categoria;
            if (!isset($productosPorCategoria[$categoria->id_categoria_producto])) {
                $productosPorCategoria[$categoria->id_categoria_producto] = [
                    'categoria' => $categoria,
                    'productos' => []
                ];
            }
            $productosPorCategoria[$categoria->id_categoria_producto]['productos'][] = $detalle;
        }
        
        return view('view_mozo.mozo_editar_pedido', compact('pedido', 'mesas', 'productosPorCategoria'));
    }

    public function actualizar_pedido(Request $request, $idPedido)
    {
        $pedido = pedidos::with('detalles')->findOrFail($idPedido);
        $nuevaMesa = $request->input('id_mesa');
        $productosModificados = $request->input('productos', []);

        try {
            // 1. Actualizar mesa si cambió
            if ($nuevaMesa != $pedido->id_mesa) {
                $pedido->update(['id_mesa' => $nuevaMesa]);
            }

            // 2. Procesar modificaciones de productos
            foreach ($productosModificados as $idDetalle => $datos) {
                $detalle = pedido_detalles::find($idDetalle);
                if (!$detalle) continue;

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

    public function agregar_productos_pedido($idPedido)
    {
        $pedido = pedidos::with('mesa')->findOrFail($idPedido);
        
        // Obtener productos ya pedidos para pre-seleccionar
        $productosYaPedidos = $pedido->detalles()->with('producto')->get()
            ->groupBy('id_producto')
            ->map(function($detalles) {
                return $detalles->sum('cantidad');
            });

        // Traer todos los productos
        $productos = productos::all();
        $categorias_producto = categorias_producto::whereIn('id_categoria_producto', 
            $productos->pluck('id_categoria_producto')->unique()
        )->get();

        // Guardar datos del pedido en sesión para mantener el contexto
        session([
            'editando_pedido' => $idPedido,
            'productos_ya_pedidos' => $productosYaPedidos->toArray()
        ]);

        return view('view_mozo.mozo_pedido', compact('pedido', 'categorias_producto', 'productos', 'productosYaPedidos'))
            ->with('mesa', $pedido->mesa)
            ->with('editando', true);
    }


    public function eliminar_pedido($idPedido)
    {
        $pedido = pedidos::with('detalles')->findOrFail($idPedido);
        
        try {
            // Devolver stock de todos los productos
            foreach ($pedido->detalles as $detalle) {
                $detalle->producto->increment('stock', $detalle->cantidad);
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
}
