<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
//agregar los modelos
use App\Models\categorias_producto;
use App\Models\productos;
use App\Models\pedidos;
use App\Models\mesas;
use App\Models\usuarios;
use App\Models\pedido_detalles;
use App\Models\comprobantes;
use App\Models\pagos_pedido_detalle;


class controller_karaoke extends Controller
{
    //REDIRECCIONES DE VISTAS MENU
    public function ver_user_menu() 
    {
        
        return view('user_menu');

    }
    //ADMINISTRADOR - redirecciones de vistas
    //MODIFICAR PRECIOS Y STOCK
    public function ver_admin_modificar_categoria()
    {
        $categorias = categorias_producto::all();
        return view('view_admin.admin_modificar_categoria', compact('categorias'));
    }
    public function ver_admin_modificar_producto(categorias_producto $categoria)
    {
        // cargo la relación productos
        $productos = $categoria->productos;
        return view('view_admin.admin_modificar_producto',compact('categoria', 'productos')
        );
    }
    //Actualizar un solo producto de historial 
    public function actualizarProducto(Request $request, productos $producto)
    {
        $rules = ['precio_unitario' => 'required|numeric|min:0'];
        if ($producto->categoria->nombre === 'Cocteles') {
            // checkbox: si no viene, lo tomamos como false
            $request->merge(['estado' => $request->has('estado')]);
            $rules['estado'] = 'boolean';
        } else {
            $rules['stock'] = 'required|integer|min:0';
        }
        $data = $request->validate($rules);

        $producto->update($data);
        return back()->with('success', "«{$producto->nombre}» actualizado");
    } 
    //VER HISTORIAL DE PEDIDOS
    public function ver_admin_historial() 
    {
        
        return view('view_admin.admin_historial');

    }
    //VER LISTA DE COMPRAS PENDIENTES
    public function ver_admin_compras() 
    {
        
        return view('view_admin.admin_compras');

    }

    //COCINERO - redirecciones de vistas
    
    public function ver_cocina_inventario() 
    {
        // Solo productos de cocina o ambos
        $productos = productos::whereIn('area_destino', ['cocina', 'ambos'])->get();

        // Solo categorías que tengan productos de cocina o ambos
        $categorias_producto = categorias_producto::whereIn('id_categoria_producto', 
            $productos->pluck('id_categoria_producto')->unique()
        )->get();

        return view('view_cocina.cocina_inventario', compact('categorias_producto', 'productos'));

    }

    //COCINERO - redirecciones de vistas con acciones
    public function ver_cocina_historial()
    {
        $idUsuario = Auth::id();

        // Filtra los detalles de pedidos asignados al usuario autenticado
        $pedidos = pedido_detalles::with(['pedido.mesa', 'producto'])
            ->where('id_usuario_preparador', $idUsuario)
            ->where('estado_item', 'SOLICITADO') // Opcional: Filtrar solo los pendientes
            ->orderBy('fecha_creacion', 'asc')
            ->get();

        return view('view_cocina.cocina_historial', compact('pedidos'));
    }

    public function marcarProductosPedido(Request $request)
    {
        $ids = $request->input('productos', []);
        if (!empty($ids)) {
            productos::whereIn('id_producto', $ids)->update(['estado' => 0]);
        }
        return back()->with('success', 'Productos marcados como PEDIDO.');
    }

    //BARRA - redirecciones de vistas
    
    public function ver_barra_inventario() 
    {
        // Solo productos de bar o ambos
        $productos = productos::whereIn('area_destino', ['bar', 'ambos'])->get();

        // Solo categorías que tengan productos de bar o ambos
        $categorias_producto = categorias_producto::whereIn('id_categoria_producto', 
            $productos->pluck('id_categoria_producto')->unique()
        )->get();

        return view('view_barra.barra_inventario', compact('categorias_producto', 'productos'));

    }

    public function ver_barra_historial() 
    {

        // Obtén el ID del usuario autenticado
        $idUsuario = Auth::id();

        // Filtra los detalles de pedidos asignados al usuario autenticado
        $pedidos = pedido_detalles::with(['pedido.mesa', 'producto'])
            ->where('id_usuario_preparador', $idUsuario)
            ->where('estado_item', 'SOLICITADO') // Opcional: Filtrar solo los pendientes
            ->orderBy('fecha_creacion', 'asc')
            ->get();

        return view('view_barra.barra_historial', compact('pedidos'));
    }   
    
    public function marcarPedidoListo($idDetalle)
    {
        // Busca el detalle del pedido
        $detalle = pedido_detalles::findOrFail($idDetalle);

        // Cambia el estado del pedido a "listo"
        $detalle->update(['estado_item' => 'LISTO_PARA_ENTREGA']);

        return back()->with('success', 'El pedido ha sido marcado como listo.');
    }

    //MESERO - redirecciones de vistas
    public function ver_mozo_historial() 
    {
        // Obtener el ID del usuario autenticado
        $idUsuario = Auth::id();

        // Filtra los pedidos por el ID del usuario autenticado e incluye el comprobante
        $pedidos = pedidos::with(['mesa', 'comprobante', 'detalles'])
            ->where('id_usuario_mesero', $idUsuario)
            ->orderBy('fecha_hora_pedido', 'desc')
            ->get();

        // Verificar el estado real de cada pedido basado en los comprobantes
        foreach ($pedidos as $pedido) {
            $tieneComprobante = $pedido->comprobante !== null;
            
            // Si tiene comprobante pero el estado no está actualizado, corregirlo
            if ($tieneComprobante && $pedido->estado_pedido === 'PENDIENTE') {
                $pedido->update(['estado_pedido' => 'PAGADO']);
                $pedido->refresh();
            }
            // Si no tiene comprobante pero está marcado como pagado, corregirlo
            elseif (!$tieneComprobante && $pedido->estado_pedido === 'PAGADO') {
                $pedido->update(['estado_pedido' => 'PENDIENTE']);
                $pedido->refresh();
            }
        }

        return view('view_mozo.mozo_historial', compact('pedidos'));
    }

    public function ver_mozo_mesa() 
    {
        // Trae todas las mesas y sus pedidos activos con detalles
        $mesas = mesas::with(['pedidos.detalles'])->get();

        // Prepara un array para saber si la mesa está ocupada y cuántos detalles tiene
        $mesasInfo = [];
        foreach ($mesas as $mesa) {
            // Busca el pedido activo (ajusta el estado si tienes uno específico)
            $pedidoActivo = $mesa->pedidos->where('estado_pedido', 'PENDIENTE')->first();
            $ocupada = $pedidoActivo ? true : false;
            $cantidadDetalles = $pedidoActivo ? $pedidoActivo->detalles->count() : 0;
            
            // Sincronizar el estado de la mesa con la existencia de pedidos pendientes
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

    // NUEVAS FUNCIONALIDADES PARA EDICIÓN DE PEDIDOS
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

    public function finalizar_pedido(Request $request, $idPedido)
    {
        $pedido = pedidos::with(['detalles.producto'])->findOrFail($idPedido);
        
        // VALIDAR QUE TODOS LOS PRODUCTOS ESTÉN LISTOS PARA ENTREGA
        $productosNoListos = $pedido->detalles->where('estado_item', '!=', 'LISTO_PARA_ENTREGA');
        
        if ($productosNoListos->count() > 0) {
            $nombresProductos = $productosNoListos->pluck('producto.nombre')->join(', ');
            return redirect()->route('vista.mozo_historial')
                ->with('error', "No se puede finalizar el pedido. Los siguientes productos aún no están listos: {$nombresProductos}");
        }
        
        // Si todos están listos, redirigir a facturación
        return redirect()->route('pedidos.facturar', $idPedido);
    }

    public function mostrar_facturacion($idPedido)
    {
        $pedido = pedidos::with(['mesa', 'detalles.producto'])->findOrFail($idPedido);
        
        // VERIFICAR QUE TODOS LOS PRODUCTOS ESTÉN LISTOS PARA ENTREGA
        $productosNoListos = $pedido->detalles->where('estado_item', '!=', 'LISTO_PARA_ENTREGA');
        
        if ($productosNoListos->count() > 0) {
            $nombresProductos = $productosNoListos->pluck('producto.nombre')->join(', ');
            return redirect()->route('vista.mozo_historial')
                ->with('error', "No se puede facturar el pedido. Los siguientes productos aún no están listos: {$nombresProductos}");
        }
        
        // VERIFICAR SI YA TIENE COMPROBANTE (verificación directa)
        $comprobanteExistente = comprobantes::where('id_pedido', $idPedido)->first();
        if ($comprobanteExistente) {
            return redirect()->route('factura.vista_previa', $comprobanteExistente->id_comprobante)
                ->with('info', 'Este pedido ya tiene un comprobante emitido.');
        }
        
        // VERIFICAR ESTADO DEL PEDIDO
        if ($pedido->estado_pedido !== 'PENDIENTE') {
            return redirect()->route('vista.mozo_historial')
                ->with('error', 'Solo se pueden facturar pedidos en estado PENDIENTE.');
        }
        
        return view('view_mozo.mozo_pedido_facturacion', compact('pedido'));
    }

    public function procesar_facturacion(Request $request, $idPedido)
    {
        $pedido = pedidos::with('detalles')->findOrFail($idPedido);
        
        // VALIDAR QUE TODOS LOS PRODUCTOS ESTÉN LISTOS PARA ENTREGA
        $productosNoListos = $pedido->detalles->where('estado_item', '!=', 'LISTO_PARA_ENTREGA');
        
        if ($productosNoListos->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede procesar el pago. Algunos productos aún no están listos para entrega.'
            ], 400);
        }
        
        // VALIDAR QUE NO TENGA YA UN COMPROBANTE (verificación directa más confiable)
        $comprobanteExistente = comprobantes::where('id_pedido', $idPedido)->first();
        if ($comprobanteExistente) {
            return response()->json([
                'success' => false,
                'message' => 'Este pedido ya tiene un comprobante emitido.',
                'comprobante_id' => $comprobanteExistente->id_comprobante
            ], 400);
        }
        
        // VALIDAR QUE EL PEDIDO ESTE EN ESTADO PENDIENTE
        if ($pedido->estado_pedido !== 'PENDIENTE') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden facturar pedidos en estado PENDIENTE.'
            ], 400);
        }
        
        $request->validate([
            'tipo_comprobante' => 'required|in:factura,boleta',
            'documento' => 'required|string',
            'metodo_pago' => 'required|array',
            'monto_pago' => 'required|array',
        ]);

        // VALIDAR QUE LOS MONTOS DE PAGO COINCIDAN CON EL TOTAL
        $totalMontoPago = array_sum($request->monto_pago);
        if (abs($totalMontoPago - $pedido->total_pedido) > 0.01) {
            return response()->json([
                'success' => false,
                'message' => 'El monto total de los métodos de pago no coincide con el total del pedido.'
            ], 400);
        }

        try {
            // DOBLE VERIFICACIÓN ANTES DE CREAR (para evitar condiciones de carrera)
            $verificacionFinal = comprobantes::where('id_pedido', $idPedido)->first();
            if ($verificacionFinal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este pedido ya fue procesado por otro usuario.',
                    'comprobante_id' => $verificacionFinal->id_comprobante
                ], 400);
            }

            // Calcular IGV correctamente
            $subtotalSinIgv = round($pedido->total_pedido / 1.18, 2);
            $igvMonto = round($pedido->total_pedido - $subtotalSinIgv, 2);

            // Crear el comprobante con TODOS los campos requeridos
            $comprobante = comprobantes::create([
                'id_pedido' => $pedido->id_pedido,
                'id_usuario_cajero' => Auth::id(),
                'tipo_documento_cliente' => $request->tipo_comprobante === 'factura' ? 'RUC' : 'DNI',
                'numero_documento_cliente' => $request->documento,
                'nombre_razon_social_cliente' => 'Cliente',
                'direccion_cliente' => null,
                'serie_comprobante' => $request->tipo_comprobante === 'factura' ? 'F001' : 'B001',
                'numero_correlativo_comprobante' => $this->generarCorrelativo($request->tipo_comprobante),
                'fecha_emision' => now(),
                'moneda' => 'PEN',
                'subtotal_comprobante' => $subtotalSinIgv,
                'igv_aplicado_tasa' => 18.00,
                'monto_igv' => $igvMonto,
                'monto_total_comprobante' => $pedido->total_pedido,
                'tipo_comprobante' => $request->tipo_comprobante,
                'metodo_pago' => implode(',', array_filter($request->metodo_pago)),
                'referencia_pago' => null,
                'estado_comprobante' => 'EMITIDO',
                'qr_code_data' => 'QR_DATA_PLACEHOLDER',
                'hash_sunat' => 'HASH_PLACEHOLDER',
                'notas_comprobante' => null,
                'fecha_anulacion' => null,
            ]);

            // Verificar que el comprobante se creó correctamente
            if (!$comprobante || !$comprobante->id_comprobante) {
                throw new \Exception('Error al crear el comprobante');
            }

            // CREAR REGISTROS DE PAGO PARA CADA DETALLE DEL PEDIDO
            $metodosP = array_filter($request->metodo_pago);
            $montosP = array_filter($request->monto_pago);
            
            // Calcular el monto proporcional por cada detalle
            foreach ($pedido->detalles as $detalle) {
                $proporcionDetalle = $detalle->subtotal / $pedido->total_pedido;
                
                // Crear un pago por cada método de pago usado
                for ($i = 0; $i < count($metodosP); $i++) {
                    if (!empty($metodosP[$i]) && $montosP[$i] > 0) {
                        $montoProporcional = round($montosP[$i] * $proporcionDetalle, 2);
                        
                        $pagoDetalle = pagos_pedido_detalle::create([
                            'id_comprobante' => $comprobante->id_comprobante,
                            'id_pedido_detalle' => $detalle->id_pedido_detalle,
                            'cantidad_item_pagada' => $detalle->cantidad,
                            'monto_pagado' => $montoProporcional,
                            'metodo_pago' => $metodosP[$i],
                            'referencia_pago' => 'REF-' . $comprobante->numero_correlativo_comprobante . '-' . $detalle->id_pedido_detalle
                        ]);

                        // Verificar que se creó el pago
                        if (!$pagoDetalle) {
                            throw new \Exception('Error al crear el registro de pago para el detalle ' . $detalle->id_pedido_detalle);
                        }
                    }
                }
            }

            // Actualizar estado del pedido
            $pedido->update(['estado_pedido' => 'PAGADO']);

            // Liberar la mesa (marcarla como disponible con valor ENUM: 'disponible')
            $mesa = $pedido->mesa;
            if ($mesa) {
                $mesa->update(['estado' => 'disponible']);
            }

            // Retornar respuesta JSON para manejo con JavaScript
            return response()->json([
                'success' => true,
                'comprobante_id' => $comprobante->id_comprobante,
                'message' => 'Pago procesado exitosamente'
            ]);

        } catch (\Illuminate\Database\QueryException $e) {
            // Log del error para debug
            Log::error('Error de base de datos en facturación', [
                'error' => $e->getMessage(),
                'pedido_id' => $idPedido,
                'code' => $e->getCode()
            ]);

            // Manejar específicamente errores de duplicado
            if ($e->getCode() == 23000) { // Integrity constraint violation
                // Buscar el comprobante que se creó
                $comprobanteExistente = comprobantes::where('id_pedido', $idPedido)->first();
                if ($comprobanteExistente) {
                    return response()->json([
                        'success' => true,
                        'comprobante_id' => $comprobanteExistente->id_comprobante,
                        'message' => 'El comprobante ya existe, redirigiendo...'
                    ]);
                }
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Error en la base de datos: ' . $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            // Log del error para debug
            Log::error('Error general en facturación', [
                'error' => $e->getMessage(),
                'pedido_id' => $idPedido
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la facturación: ' . $e->getMessage()
            ], 500);
        }
    }

    public function enviar_correo_form(Request $request, $idComprobante)
    {
        $comprobante = comprobantes::findOrFail($idComprobante);
        
        return response()->json([
            'success' => true,
            'html' => view('modals.enviar_correo', compact('comprobante'))->render()
        ]);
    }

    public function enviar_correo(Request $request, $idComprobante)
    {
        $comprobante = comprobantes::findOrFail($idComprobante);
        
        $request->validate([
            'dni_correo' => 'required|string',
            'email' => 'required|email',
        ]);

        // Aquí implementarías el envío real del correo
        // Por ahora simularemos el proceso
        
        return response()->json([
            'success' => true,
            'message' => 'Correo enviado exitosamente',
            'comprobante_id' => $comprobante->id_comprobante
        ]);
    }

    public function vista_previa_pdf($idComprobante)
    {
        $comprobante = comprobantes::with(['pedido.detalles.producto', 'pedido.mesa'])->findOrFail($idComprobante);
        
        return view('view_mozo.mozo_pedido_vista_previa', compact('comprobante'));
    }

    private function generarCorrelativo($tipo)
    {
        $ultimoComprobante = comprobantes::where('tipo_comprobante', $tipo)
            ->orderBy('numero_correlativo_comprobante', 'desc')
            ->first();
        
        $ultimoNumero = $ultimoComprobante ? (int)$ultimoComprobante->numero_correlativo_comprobante : 0;
        
        return str_pad($ultimoNumero + 1, 8, '0', STR_PAD_LEFT);
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

    // NUEVAS FUNCIONALIDADES PARA GESTIÓN DE USUARIOS
    public function ver_admin_gestion_usuarios()
    {
        $usuarios = usuarios::orderBy('rol')->orderBy('nombres')->get();
        return view('view_admin.admin_gestion_usuarios', compact('usuarios'));
    }

    public function agregar_usuario(Request $request)
    {
        try {
            $messages = [
                'nombres.required' => 'El nombre es obligatorio.',
                'nombres.string' => 'El nombre debe ser texto válido.',
                'nombres.max' => 'El nombre no puede tener más de 255 caracteres.',
                'nombres.regex' => 'El nombre solo puede contener letras y espacios.',
                'codigo_usuario.required' => 'El código de usuario es obligatorio.',
                'codigo_usuario.string' => 'El código de usuario debe ser texto válido.',
                'codigo_usuario.max' => 'El código de usuario no puede tener más de 50 caracteres.',
                'codigo_usuario.unique' => 'Este código de usuario ya existe en el sistema.',
                'codigo_usuario.regex' => 'El código de usuario solo puede contener letras, números y guiones.',
                'contrasena.required' => 'La contraseña es obligatoria.',
                'contrasena.string' => 'La contraseña debe ser texto válido.',
                'contrasena.min' => 'La contraseña debe tener al menos 6 caracteres.',
                'contrasena.confirmed' => 'La confirmación de contraseña no coincide.',
                'contrasena.regex' => 'La contraseña no puede contener espacios.',
                'rol.required' => 'Debe seleccionar un rol.',
                'rol.in' => 'El rol seleccionado no es válido.'
            ];

            $validatedData = $request->validate([
                'nombres' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/'],
                'codigo_usuario' => ['required', 'string', 'max:50', 'unique:usuarios,codigo_usuario', 'regex:/^[a-zA-Z0-9_-]+$/'],
                'contrasena' => ['required', 'string', 'min:6', 'confirmed', 'regex:/^\S+$/'],
                'rol' => ['required', 'in:administrador,mesero,cocinero,bartender']
            ], $messages);

            // Generar usuario automáticamente basado en el nombre
            $nombreLimpio = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $validatedData['nombres']));
            $usuario = substr($nombreLimpio, 0, 8) . rand(100, 999);

            usuarios::create([
                'codigo_usuario' => trim($validatedData['codigo_usuario']),
                'usuario' => $usuario,
                'contrasena' => Hash::make($validatedData['contrasena']),
                'nombres' => trim($validatedData['nombres']),
                'rol' => $validatedData['rol'],
                'estado' => 1,
                'fecha_creacion' => now(),
                'fecha_actualizacion' => now()
            ]);

            return redirect()->route('vista.admin_gestion_usuarios')
                ->with('success', 'Usuario "' . $validatedData['nombres'] . '" creado exitosamente.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('vista.admin_gestion_usuarios')
                ->withErrors($e->errors())
                ->withInput()
                ->with('show_modal_add', true)
                ->with('modal_type', 'add'); // AGREGAR IDENTIFICADOR
        } catch (\Exception $e) {
            return redirect()->route('vista.admin_gestion_usuarios')
                ->with('error', 'Error inesperado: No se pudo crear el usuario.')
                ->with('show_modal_add', true)
                ->with('modal_type', 'add'); // AGREGAR IDENTIFICADOR
        }
    }

    public function modificar_usuario(Request $request, $usuario)
    {
        try {
            // CAMBIAR LA BÚSQUEDA PARA USAR EL PARÁMETRO CORRECTO
            $usuarioModel = usuarios::where('id_usuario', $usuario)->firstOrFail();
            
            $messages = [
                'nombres.required' => 'El nombre es obligatorio.',
                'nombres.string' => 'El nombre debe ser texto válido.',
                'nombres.max' => 'El nombre no puede tener más de 255 caracteres.',
                'nombres.regex' => 'El nombre solo puede contener letras y espacios.',
                'codigo_usuario.required' => 'El código de usuario es obligatorio.',
                'codigo_usuario.string' => 'El código de usuario debe ser texto válido.',
                'codigo_usuario.max' => 'El código de usuario no puede tener más de 50 caracteres.',
                'codigo_usuario.unique' => 'Este código de usuario ya existe en el sistema.',
                'codigo_usuario.regex' => 'El código de usuario solo puede contener letras, números y guiones.',
                'contrasena.string' => 'La contraseña debe ser texto válido.',
                'contrasena.min' => 'La contraseña debe tener al menos 6 caracteres.',
                'contrasena.confirmed' => 'La confirmación de contraseña no coincide.',
                'contrasena.regex' => 'La contraseña no puede contener espacios.',
                'rol.required' => 'Debe seleccionar un rol.',
                'rol.in' => 'El rol seleccionado no es válido.',
                'estado.required' => 'Debe seleccionar un estado.',
                'estado.in' => 'El estado seleccionado no es válido.'
            ];

            $rules = [
                'nombres' => ['required', 'string', 'max:255', 'regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/'],
                'codigo_usuario' => ['required', 'string', 'max:50', 'unique:usuarios,codigo_usuario,' . $usuario . ',id_usuario', 'regex:/^[a-zA-Z0-9_-]+$/'],
                'rol' => ['required', 'in:administrador,mesero,cocinero,bartender'],
                'estado' => ['required', 'in:0,1']
            ];

            if ($request->filled('contrasena')) {
                $rules['contrasena'] = ['required', 'string', 'min:6', 'confirmed', 'regex:/^\S+$/'];
            }

            $validatedData = $request->validate($rules, $messages);

            // Verificaciones especiales para roles críticos
            if (($usuarioModel->rol === 'cocinero' || $usuarioModel->rol === 'bartender') && $validatedData['estado'] == 0) {
                $otrosDelMismoRol = usuarios::where('rol', $usuarioModel->rol)
                    ->where('estado', 1)
                    ->where('id_usuario', '!=', $usuario)
                    ->count();

                if ($otrosDelMismoRol === 0) {
                    return redirect()->route('vista.admin_gestion_usuarios')
                        ->with('error', 'No se puede desactivar al único ' . $usuarioModel->rol . ' activo del sistema.')
                        ->with('show_modal_edit', $usuario);
                }
            }

            $datosActualizar = [
                'nombres' => trim($validatedData['nombres']),
                'codigo_usuario' => trim($validatedData['codigo_usuario']),
                'rol' => $validatedData['rol'],
                'estado' => $validatedData['estado'],
                'fecha_actualizacion' => now()
            ];

            if ($request->filled('contrasena')) {
                $datosActualizar['contrasena'] = Hash::make($validatedData['contrasena']);
            }

            $usuarioModel->update($datosActualizar);

            return redirect()->route('vista.admin_gestion_usuarios')
                ->with('success', 'Usuario "' . $validatedData['nombres'] . '" actualizado exitosamente.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->route('vista.admin_gestion_usuarios')
                ->withErrors($e->errors())
                ->withInput()
                ->with('show_modal_edit', $usuario)
                ->with('modal_type', 'edit') // AGREGAR IDENTIFICADOR
                ->with('edit_data', [ // ENVIAR DATOS DEL USUARIO
                    'id' => $usuario,
                    'nombres' => $request->input('nombres'),
                    'codigo_usuario' => $request->input('codigo_usuario'),
                    'rol' => $request->input('rol'),
                    'estado' => $request->input('estado')
                ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return redirect()->route('vista.admin_gestion_usuarios')
                ->with('error', 'Usuario no encontrado en el sistema.');
        } catch (\Exception $e) {
            return redirect()->route('vista.admin_gestion_usuarios')
                ->with('error', 'Error inesperado: No se pudo actualizar el usuario.')
                ->with('show_modal_edit', $usuario)
                ->with('modal_type', 'edit'); // AGREGAR IDENTIFICADOR
        }
    }

    public function eliminar_usuario($usuario)
    {
        try {
            // CAMBIAR LA BÚSQUEDA PARA USAR EL PARÁMETRO CORRECTO
            $usuarioModel = usuarios::where('id_usuario', $usuario)->firstOrFail();
            
            // VALIDACIÓN 1: Los administradores no pueden eliminarse
            if ($usuarioModel->rol === 'administrador') {
                return back()->with('error', 'Los usuarios administradores no pueden ser eliminados por seguridad del sistema.');
            }

            // VALIDACIÓN 2: Verificar si es el único cocinero o bartender activo
            if ($usuarioModel->rol === 'cocinero' || $usuarioModel->rol === 'bartender') {
                $otrosDelMismoRol = usuarios::where('rol', $usuarioModel->rol)
                    ->where('estado', 1)
                    ->where('id_usuario', '!=', $usuario)
                    ->count();

                if ($otrosDelMismoRol === 0) {
                    return back()->with('error', 'No se puede eliminar al único ' . $usuarioModel->rol . ' activo. Primero agregue otro ' . $usuarioModel->rol . ' al sistema.');
                }
            }

            // VALIDACIÓN 3: Verificar si tiene pedidos asignados
            $pedidosComoMesero = pedidos::where('id_usuario_mesero', $usuario)->count();
            $pedidosComoPreparador = pedido_detalles::where('id_usuario_preparador', $usuario)->count();
            
            if ($pedidosComoMesero > 0 || $pedidosComoPreparador > 0) {
                return back()->with('error', 'No se puede eliminar este usuario porque tiene ' . ($pedidosComoMesero + $pedidosComoPreparador) . ' pedido(s) asignado(s) en el sistema.');
            }

            // VALIDACIÓN 4: Verificar si ha emitido comprobantes
            $comprobantesEmitidos = comprobantes::where('id_usuario_cajero', $usuario)->count();
            
            if ($comprobantesEmitidos > 0) {
                return back()->with('error', 'No se puede eliminar este usuario porque ha emitido ' . $comprobantesEmitidos . ' comprobante(s). Por integridad de datos, solo se puede desactivar.');
            }

            $nombreUsuario = $usuarioModel->nombres;
            $usuarioModel->delete();

            return redirect()->route('vista.admin_gestion_usuarios')
                ->with('success', 'Usuario "' . $nombreUsuario . '" eliminado exitosamente.');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return back()->with('error', 'Usuario no encontrado en el sistema.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error inesperado: No se pudo eliminar el usuario.');
        }
    }

    // NUEVAS FUNCIONALIDADES PARA AGREGAR PRODUCTOS
    public function ver_admin_agregar_producto()
    {
        $categorias = categorias_producto::where('estado', 1)->get();
        return view('view_admin.admin_agregar_producto', compact('categorias'));
    }

    public function store_producto(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'id_categoria_producto' => 'required|exists:categorias_producto,id_categoria_producto',
            'precio_unitario' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'imagen_url' => 'nullable|url'
        ]);

        try {
            // Generar código interno automáticamente
            $ultimoProducto = productos::orderBy('id_producto', 'desc')->first();
            $numeroConsecutivo = $ultimoProducto ? $ultimoProducto->id_producto + 1 : 1;
            $codigoInterno = 'PROD' . str_pad($numeroConsecutivo, 4, '0', STR_PAD_LEFT);

            // Determinar área de destino basada en la categoría
            $categoria = categorias_producto::find($request->id_categoria_producto);
            $areaDestino = 'cocina'; // Por defecto
            
            if (stripos($categoria->nombre, 'bebida') !== false || 
                stripos($categoria->nombre, 'coctel') !== false ||
                stripos($categoria->nombre, 'licor') !== false) {
                $areaDestino = 'bar';
            }

            productos::create([
                'id_categoria_producto' => $request->id_categoria_producto,
                'area_destino' => $areaDestino,
                'codigo_interno' => $codigoInterno,
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion ?? '',
                'precio_unitario' => $request->precio_unitario,
                'stock' => $request->stock,
                'unidad_medida' => 'unidad',
                'imagen_url' => $request->imagen_url,
                'estado' => $request->has('estado') ? 1 : 0,
                'fecha_creacion' => now(),
                'fecha_actualizacion' => now()
            ]);

            return redirect()->route('vista.admin_agregar_producto')
                ->with('success', 'Producto agregado exitosamente');

        } catch (\Exception $e) {
            return back()->with('error', 'Error al agregar producto: ' . $e->getMessage());
        }
    }
}
