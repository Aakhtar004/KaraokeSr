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
            
            // Solo marcar como listo si el producto es de bar o ambos
            if (in_array($detalle->producto->area_destino, ['bar', 'ambos'])) {
                $detalle->update(['estado_item' => 'LISTO_PARA_ENTREGA']);
                
                // Retornar información de la mesa para el mensaje de éxito
                $mesa = $detalle->pedido->mesa->numero_mesa ?? 'N/A';
                return response()->json(['success' => true, 'mesa' => $mesa]);
            } else {
                return response()->json(['success' => false, 'message' => 'Este producto no corresponde al área de bar.']);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al marcar el pedido como listo: ' . $e->getMessage()]);
        }
    }
}
