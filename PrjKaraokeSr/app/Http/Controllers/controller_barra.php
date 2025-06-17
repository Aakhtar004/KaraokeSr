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
        $productos = productos::whereIn('area_destino', ['bar', 'ambos'])->get();
        $categorias_producto = categorias_producto::whereIn('id_categoria_producto', 
            $productos->pluck('id_categoria_producto')->unique()
        )->get();

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
    
    public function marcarPedidoListo($idPedido)
    {
        pedido_detalles::where('id_pedido', $idPedido)
            ->whereHas('producto', function ($query) {
                $query->whereIn('area_destino', ['bar', 'ambos']);
            })
            ->update(['estado_item' => 'LISTO_PARA_ENTREGA']);
            
        return response()->json(['success' => true, 'message' => 'El pedido ha sido marcado como listo.']);
    }
}
