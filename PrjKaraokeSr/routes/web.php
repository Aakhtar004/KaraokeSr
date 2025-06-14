<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\CheckTypeUser;
use App\Http\Middleware\Authenticate;

// Import all controllers
use App\Http\Controllers\controller_karaoke;
use App\Http\Controllers\controller_admin;
use App\Http\Controllers\controller_mesero;
use App\Http\Controllers\controller_facturacion;
use App\Http\Controllers\controller_cocina;
use App\Http\Controllers\controller_barra;
use App\Http\Controllers\Auth\controller_login;

Route::get('/', function () {
    return view('auth.login');
});

Route::middleware(['auth:gusers', 'prevent-back-history'])->group(function () {
    // Ruta para todos los usuarios
    Route::get('/user_menu', [controller_karaoke::class, 'ver_user_menu'])->name('vista.user_menu');       

    // Rutas para administradores
    Route::middleware(['midctu:administrador'])->group(function () {
        // Modificar precios y stock
        Route::get('/view_admin/admin_modificar_categoria', [controller_admin::class, 'ver_admin_modificar_categoria'])->name('vista.admin_modificar_categoria');
        Route::get('/view_admin/admin_modificar_producto/{categoria}', [controller_admin::class, 'ver_admin_modificar_producto'])->name('vista.admin_modificar_producto');
        Route::patch('/view_admin/admin_producto/{producto}', [controller_admin::class, 'actualizarProducto'])->name('admin.producto.actualizar');
        
        // Historial y compras
        Route::get('/view_admin/admin_historial', [controller_admin::class, 'ver_admin_historial'])->name('vista.admin_historial');
        Route::get('/view_admin/admin_compras', [controller_admin::class, 'ver_admin_compras'])->name('vista.admin_compras');

        // Gestión de usuarios
        Route::get('/view_admin/admin_gestion_usuarios', [controller_admin::class, 'ver_admin_gestion_usuarios'])->name('vista.admin_gestion_usuarios');
        Route::post('/view_admin/admin_usuarios', [controller_admin::class, 'agregar_usuario'])->name('admin.usuarios.store');
        Route::put('/view_admin/admin_usuarios/{usuario}', [controller_admin::class, 'modificar_usuario'])->name('admin.usuarios.update');
        Route::delete('/view_admin/admin_usuarios/{usuario}', [controller_admin::class, 'eliminar_usuario'])->name('admin.usuarios.delete');
        
        // Agregar productos
        Route::get('/view_admin/admin_agregar_producto', [controller_admin::class, 'ver_admin_agregar_producto'])->name('vista.admin_agregar_producto');
        Route::post('/admin/productos', [controller_admin::class, 'store_producto'])->name('admin.productos.store');
    });
    
    // Rutas para cocineros
    Route::middleware(['midctu:cocinero'])->group(function () {
        Route::get('/view_cocina/cocina_historial', [controller_cocina::class, 'ver_cocina_historial'])->name('vista.cocina_historial');
        Route::get('/view_cocina/cocina_inventario', [controller_cocina::class, 'ver_cocina_inventario'])->name('vista.cocina_inventario');
        Route::post('/cocina/pedido/{detalle}/listo', [controller_cocina::class, 'marcarPedidoListo'])->name('cocina.pedido.listo');
        Route::post('/cocina/inventario/pedido', [controller_cocina::class, 'marcarProductosPedido'])->name('cocina.inventario.pedido');
    });

    // Rutas para bartenders
    Route::middleware(['midctu:bartender'])->group(function () {
        Route::get('/view_barra/barra_historial', [controller_barra::class, 'ver_barra_historial'])->name('vista.barra_historial');
        Route::get('/view_barra/barra_inventario', [controller_barra::class, 'ver_barra_inventario'])->name('vista.barra_inventario');
        Route::post('/barra/pedido/{detalle}/listo', [controller_barra::class, 'marcarPedidoListo'])->name('barra.pedido.listo');
    });

    // Rutas para meseros
    Route::middleware(['midctu:mesero'])->group(function () {
        Route::get('/view_mozo/mozo_historial', [controller_mesero::class, 'ver_mozo_historial'])->name('vista.mozo_historial');
        Route::get('/view_mozo/mozo_mesa', [controller_mesero::class, 'ver_mozo_mesa'])->name('vista.mozo_mesa');

        Route::get('/view_mozo/mozo_pedido/mesa/{mesa}', [controller_mesero::class, 'ver_mozo_pedido_mesa'])->name('vista.mozo_pedido_mesa');
        Route::get('/view_mozo/mozo_pedido/historial', [controller_mesero::class, 'ver_mozo_pedido_historial'])->name('vista.mozo_pedido_historial');
        Route::post('/view_mozo/mozo_pedido/procesar', [controller_mesero::class, 'procesar_mozo_pedido'])->name('vista.procesar_mozo_pedido');
        Route::post('/view_mozo/mozo_pedido/confirmar', [controller_mesero::class, 'confirmar_mozo_pedido'])->name('vista.confirmar_mozo_pedido');

        // Gestión de pedidos
        Route::get('/pedidos/{pedido}', [controller_mesero::class, 'ver_pedido'])->name('pedidos.ver');
        Route::get('/pedidos/{pedido}/editar', [controller_mesero::class, 'editar_pedido'])->name('pedidos.editar');
        Route::put('/pedidos/{pedido}', [controller_mesero::class, 'actualizar_pedido'])->name('pedidos.actualizar');
        Route::get('/pedidos/{pedido}/agregar', [controller_mesero::class, 'agregar_productos_pedido'])->name('pedidos.agregar');
        Route::delete('/pedidos/{pedido}', [controller_mesero::class, 'eliminar_pedido'])->name('pedidos.eliminar');

        // Rutas de facturación
        Route::post('/pedidos/{pedido}/finalizar', [controller_facturacion::class, 'finalizar_pedido'])->name('pedidos.finalizar');
        Route::get('/pedidos/{pedido}/facturar', [controller_facturacion::class, 'mostrar_facturacion'])->name('pedidos.facturar');
        Route::post('/pedidos/{pedido}/facturar', [controller_facturacion::class, 'procesar_facturacion'])->name('pedidos.procesar_facturacion');
        Route::post('/factura/{comprobante}/enviar-correo', [controller_facturacion::class, 'enviar_correo_form'])->name('factura.enviar_correo_form');
        Route::post('/factura/{comprobante}/enviar', [controller_facturacion::class, 'enviar_correo'])->name('factura.enviar_correo');
        Route::get('/factura/{comprobante}/vista-previa', [controller_facturacion::class, 'vista_previa_pdf'])->name('factura.vista_previa');
    });
});

// Rutas de autenticación
Route::get('/login', [controller_login::class, 'showLoginForm'])->name('login');
Route::post('/verificarlogin', [controller_login::class, 'login'])->name('verificar.login');
Route::post('/logout', [controller_login::class, 'logout'])->name('logout');
