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

class controller_barra extends Controller
{
    public function ver_barra_inventario() 
    {
        // Categorías específicas para el inventario de bar
        $categoriasBar = ['Frutas', 'Ingredientes', 'Bebidas de Barra', 'Licores de Barra'];
        
        // Obtener solo las categorías específicas del bar
        $categorias_producto = categorias_producto::whereIn('nombre', $categoriasBar)
            ->where('estado', 1)
            ->get();
        
        // Obtener productos que pertenecen a estas categorías específicas
        $productos = productos::whereIn('id_categoria_producto', 
            $categorias_producto->pluck('id_categoria_producto'))
            ->get();

        return view('view_barra.barra_inventario', compact('categorias_producto', 'productos'));
    }

    public function ver_barra_historial() 
    {
        $idUsuario = Auth::id();
        $pedidos = pedido_detalles::with(['pedido.mesa', 'producto'])
            ->where('id_usuario_preparador', $idUsuario)
            ->where('estado_item', 'SOLICITADO')
            ->orderBy('fecha_creacion', 'asc')
            ->get();

        return view('view_barra.barra_historial', compact('pedidos'));
    }   

    public function marcarProductosPedido(Request $request)
    {
        $ids = $request->input('productos', []);
        if (!empty($ids)) {
            productos::whereIn('id_producto', $ids)->update(['estado' => 0]);
        }
        return back()->with('success', 'Productos marcados como PEDIDO.');
    }
    
    public function marcarPedidoListo($idDetalle)
    {
        try {
            $detalle = pedido_detalles::with(['pedido.mesa', 'producto'])->findOrFail($idDetalle);
            
            // Solo marcar como listo si el producto es de cocina o ambos
            if (in_array($detalle->producto->area_destino, ['bar', 'ambos'])) {
                $detalle->update(['estado_item' => 'LISTO_PARA_ENTREGA']);
                
                // Retornar información de la mesa para el mensaje de éxito
                $mesa = $detalle->pedido->mesa->numero_mesa ?? 'N/A';
                return response()->json(['success' => true, 'mesa' => $mesa]);
            } else {
                return response()->json(['success' => false, 'message' => 'Este producto no corresponde al área de barra.']);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al marcar el pedido como listo: ' . $e->getMessage()]);
        }
    }

    public function pedido_barra_inventario(Request $request)
    {
        $ids = $request->input('productos', []);
        
        if (empty($ids)) {
            return back()->with('error', 'No se seleccionaron productos.');
        }

        $hoy = now()->format('Y-m-d');
        $accion = $request->input('accion', 'nueva');
        
        // Solo procesar acciones específicas, NO la acción 'nueva'
        if ($accion === 'confirmar_primera') {
            productos::whereIn('id_producto', $ids)->update([
                'estado' => 0,
                'fecha_actualizacion' => now()
            ]);
            return back()->with('success', 'Los insumos se han añadido correctamente al pedido de hoy.');
        }
        
        if ($accion === 'reemplazar') {
            // Obtener IDs de categorías de bar para hacer la consulta más específica
            $categoriasBar = ['Frutas', 'Ingredientes', 'Bebidas de Barra', 'Licores de Barra'];
            $categoriasBarIds = categorias_producto::whereIn('nombre', $categoriasBar)->pluck('id_categoria_producto')->toArray();

            // Debug: Log para verificar qué productos van a ser restaurados
            $productosARestaurar = productos::whereIn('id_categoria_producto', $categoriasBarIds)
                ->where('estado', 0)
                ->whereRaw('DATE(fecha_actualizacion) = ?', [$hoy])
                ->get();

            Log::info('Productos a restaurar en reemplazar:', [
                'cantidad' => $productosARestaurar->count(),
                'productos' => $productosARestaurar->pluck('id_producto', 'nombre')->toArray(),
                'fecha' => $hoy
            ]);

            // 1. Restaurar TODOS los productos de bar que fueron pedidos hoy a disponible
            $productosRestaurados = productos::whereIn('id_categoria_producto', $categoriasBarIds)
                ->where('estado', 0)
                ->whereRaw('DATE(fecha_actualizacion) = ?', [$hoy])
                ->update([
                    'estado' => 1,
                    'fecha_actualizacion' => now()
                ]);

            Log::info('Productos restaurados:', ['cantidad' => $productosRestaurados]);
            
            // 2. Marcar SOLO los nuevos productos como pedido
            $productosNuevos = productos::whereIn('id_producto', $ids)->update([
                'estado' => 0,
                'fecha_actualizacion' => now()
            ]);

            Log::info('Productos nuevos marcados:', [
                'cantidad' => $productosNuevos,
                'ids' => $ids
            ]);
            
            return back()->with('success', 'El pedido de hoy se ha actualizado con los nuevos insumos.');
            
        } elseif ($accion === 'agregar') {
            // Obtener IDs de categorías de bar
            $categoriasBar = ['Frutas', 'Ingredientes', 'Bebidas de Barra', 'Licores de Barra'];
            $categoriasBarIds = categorias_producto::whereIn('nombre', $categoriasBar)->pluck('id_categoria_producto')->toArray();

            // Verificar productos existentes usando IDs de categorías
            $productosBarExistentes = productos::whereIn('id_categoria_producto', $categoriasBarIds)
                ->where('estado', 0)
                ->whereRaw('DATE(fecha_actualizacion) = ?', [$hoy])
                ->get();

            // Solo marcar los productos que NO están ya pedidos hoy
            $productosExistentesIds = $productosBarExistentes->pluck('id_producto')->toArray();
            $productosNuevosParaAgregar = array_diff($ids, $productosExistentesIds);
            
            if (!empty($productosNuevosParaAgregar)) {
                productos::whereIn('id_producto', $productosNuevosParaAgregar)->update([
                    'estado' => 0,
                    'fecha_actualizacion' => now()
                ]);
                
                $cantidadAgregados = count($productosNuevosParaAgregar);
                $cantidadDuplicados = count($ids) - $cantidadAgregados;
                
                $mensaje = "Los insumos se han añadido correctamente al pedido de hoy.";
                if ($cantidadDuplicados > 0) {
                    $mensaje .= " ({$cantidadDuplicados} productos ya estaban en la lista)";
                }
                
                return back()->with('success', $mensaje);
            } else {
                return back()->with('info', 'Todos los productos seleccionados ya están en la lista de hoy.');
            }
        }
        
        // Si llega aquí con acción 'nueva', es un error del flujo
        return back()->with('error', 'Acción no válida. Use el modal para confirmar.');
    }

    // Nuevo endpoint para verificar estado vía AJAX
    public function verificar_estado_inventario(Request $request)
    {
        $ids = $request->input('productos', []);
        
        if (empty($ids)) {
            return response()->json(['error' => 'No se seleccionaron productos.']);
        }

        $hoy = now()->format('Y-m-d');
        
        // Obtener IDs de categorías de bar
        $categoriasBar = ['Frutas', 'Ingredientes', 'Bebidas de Barra', 'Licores de Barra'];
        $categoriasBarIds = categorias_producto::whereIn('nombre', $categoriasBar)->pluck('id_categoria_producto')->toArray();
        
        // Verificar si ya hay productos de bar marcados como pedido hoy usando IDs de categorías
        $productosBarExistentes = productos::whereIn('id_categoria_producto', $categoriasBarIds)
            ->where('estado', 0)
            ->whereRaw('DATE(fecha_actualizacion) = ?', [$hoy])
            ->with('categoria')
            ->get();

        // Obtener información de los productos nuevos
        $productosNuevos = productos::whereIn('id_producto', $ids)->with('categoria')->get();
        
        $response = [
            'tiene_pedido_previo' => $productosBarExistentes->count() > 0,
            'productos_nuevos' => $productosNuevos->map(function($producto) {
                return [
                    'id' => $producto->id_producto,
                    'nombre' => $producto->nombre,
                    'categoria' => $producto->categoria->nombre
                ];
            })->groupBy('categoria')
        ];
        
        if ($productosBarExistentes->count() > 0) {
            $response['productos_existentes'] = $productosBarExistentes->map(function($producto) {
                return [
                    'id' => $producto->id_producto,
                    'nombre' => $producto->nombre,
                    'categoria' => $producto->categoria->nombre
                ];
            })->groupBy('categoria');
        }
        
        return response()->json($response);
    }

    // Método para obtener detalles de un pedido específico
    public function obtenerDetallesPedido($idPedido)
    {
        try {
            $idUsuario = Auth::id();
            $detalles = pedido_detalles::with(['pedido.mesa', 'producto'])
                ->where('id_usuario_preparador', $idUsuario)
                ->where('estado_item', 'SOLICITADO')
                ->whereHas('pedido', function($query) use ($idPedido) {
                    $query->where('id_pedido', $idPedido);
                })
                ->get();
            
            if ($detalles->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No se encontraron detalles del pedido.']);
            }

            $pedidoInfo = [
                'id_pedido' => $idPedido,
                'mesa' => $detalles->first()->pedido->mesa->numero_mesa ?? 'N/A',
                'detalles' => $detalles->map(function($detalle) {
                    return [
                        'id_detalle' => $detalle->id_pedido_detalle,
                        'producto' => $detalle->producto->nombre ?? 'Producto no encontrado',
                        'cantidad' => $detalle->cantidad,
                        'area_destino' => $detalle->producto->area_destino ?? 'bar'
                    ];
                })
            ];

            return response()->json(['success' => true, 'data' => $pedidoInfo]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al obtener detalles: ' . $e->getMessage()]);
        }
    }

    // Método para marcar productos seleccionados como listos
    public function marcarProductosSeleccionados(Request $request)
    {
        try {
            $detallesIds = $request->input('detalles_ids', []);
            
            if (empty($detallesIds)) {
                return response()->json(['success' => false, 'message' => 'No se seleccionaron productos.']);
            }

            $idUsuario = Auth::id();
            
            // Validar que los detalles pertenecen al usuario actual y están en estado SOLICITADO
            $detalles = pedido_detalles::with(['pedido.mesa', 'producto'])
                ->whereIn('id_pedido_detalle', $detallesIds)
                ->where('id_usuario_preparador', $idUsuario)
                ->where('estado_item', 'SOLICITADO')
                ->get();

            if ($detalles->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No se encontraron productos válidos para marcar.']);
            }

            // Filtrar solo productos de bar o ambos
            $detallesValidos = $detalles->filter(function($detalle) {
                return in_array($detalle->producto->area_destino, ['bar', 'ambos']);
            });

            if ($detallesValidos->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'Ningún producto corresponde al área de barra.']);
            }

            // Marcar como listos
            $detallesValidos->each(function($detalle) {
                $detalle->update(['estado_item' => 'LISTO_PARA_ENTREGA']);
            });

            $mesa = $detallesValidos->first()->pedido->mesa->numero_mesa ?? 'N/A';
            $cantidadMarcados = $detallesValidos->count();
            
            return response()->json([
                'success' => true, 
                'mesa' => $mesa,
                'cantidad_marcados' => $cantidadMarcados,
                'message' => "Se marcaron {$cantidadMarcados} producto(s) como listos para la Mesa {$mesa}."
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al marcar productos: ' . $e->getMessage()]);
        }
    }
    


}
