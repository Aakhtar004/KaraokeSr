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
class controller_cocina extends Controller
{
    public function ver_cocina_inventario() 
    {
        $productos = productos::whereIn('area_destino', ['cocina', 'ambos'])->get();
        $categorias_producto = categorias_producto::whereIn('id_categoria_producto', 
            $productos->pluck('id_categoria_producto')->unique()
        )->get();

        return view('view_cocina.cocina_inventario', compact('categorias_producto', 'productos'));
    }

    public function ver_cocina_historial()
    {
        $idUsuario = Auth::id();
        $pedidos = pedido_detalles::with(['pedido.mesa', 'producto'])
            ->where('id_usuario_preparador', $idUsuario)
            ->where('estado_item', 'SOLICITADO')
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

    public function marcarPedidoListo($idDetalle)
    {
        $detalle = pedido_detalles::with(['pedido.mesa', 'producto'])->findOrFail($idDetalle);
        // Solo marcar como listo si el producto es de cocina o ambos
        if (in_array($detalle->producto->area_destino, ['cocina', 'ambos'])) {
            $detalle->update(['estado_item' => 'LISTO_PARA_ENTREGA']);
            // Si es AJAX, devolver JSON
            if (request()->expectsJson()) {
                $mesa = $detalle->pedido->mesa->numero_mesa ?? 'N/A';
                return response()->json(['success' => true, 'mesa' => $mesa]);
            }
            return back()->with('success', 'El pedido ha sido marcado como listo.');
        }
        if (request()->expectsJson()) {
            return response()->json(['success' => false, 'msg' => 'No es producto de cocina'], 400);
        }
        return back()->with('error', 'No es producto de cocina.');
    }
}
