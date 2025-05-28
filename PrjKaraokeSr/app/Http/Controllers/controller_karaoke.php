<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
    //ADMINISTRADOR - redirecciones de vistas con acciones
    



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
        // Obtén el ID del usuario autenticado
        $idUsuario = Auth::id();

        // Filtra los pedidos por el ID del usuario autenticado
        $pedidos = pedidos::with('mesa')
            ->where('id_usuario_mesero', $idUsuario)
            ->orderBy('fecha_hora_pedido', 'desc')
            ->get();

        return view('view_mozo.mozo_historial', compact('pedidos'));
    }

    
}
