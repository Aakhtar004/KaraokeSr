<?php

use Illuminate\Support\Facades\Route;
//importar el middleware para autenticación
use App\Http\Middleware\CheckTypeUser;
use App\Http\Middleware\Authenticate;

//importar el controlador
use App\Http\Controllers\controller_karaoke;
use App\Http\Controllers\Auth\controller_login;

Route::get('/', function () {
    return view('auth.login');
});

// para la diferenciacion de rutas por tipo de usuario

Route::middleware(['auth:gusers', 'prevent-back-history'])->group(function () {
    //ruta para todos los usuarios
    Route::get('/user_menu', [controller_karaoke::class, 'ver_user_menu'])->name('vista.user_menu');       

    //ruta para los usuarios de tipo administrador
    Route::middleware(['midctu:administrador'])->group(function () {
        //modificar precios en productos 2 apartados - producto categoria y producto producto        
        Route::get('/view_admin/admin_modificar_categoria', [controller_karaoke::class, 'ver_admin_modificar_categoria'])->name('vista.admin_modificar_categoria');
        Route::get('/view_admin/admin_modificar_producto/{categoria}', [controller_karaoke::class, 'ver_admin_modificar_producto'])->name('vista.admin_modificar_producto');
        // PATCH: actualiza un producto, no muestra una vista
        Route::patch('/view_admin/admin_producto/{producto}', [controller_karaoke::class, 'actualizarProducto'])->name('admin.producto.actualizar');
        
        //modificar precios en productos 2 apartados - producto categoria y producto productos
        Route::get('/view_admin/admin_historial', [controller_karaoke::class, 'ver_admin_historial'])->name('vista.admin_historial');
        //modificar precios en productos 2 apartados - producto categoria y producto producto       
        Route::get('/view_admin/admin_compras', [controller_karaoke::class, 'ver_admin_compras'])->name('vista.admin_compras');

        // NUEVAS RUTAS PARA GESTIÓN DE USUARIOS
        Route::get('/view_admin/admin_gestion_usuarios', [controller_karaoke::class, 'ver_admin_gestion_usuarios'])->name('vista.admin_gestion_usuarios');
        Route::post('/admin/usuarios', [controller_karaoke::class, 'store_usuario'])->name('admin.usuarios.store');
        Route::delete('/admin/usuarios/{usuario}', [controller_karaoke::class, 'delete_usuario'])->name('admin.usuarios.delete');
        
        // NUEVAS RUTAS PARA AGREGAR PRODUCTOS
        Route::get('/view_admin/admin_agregar_producto', [controller_karaoke::class, 'ver_admin_agregar_producto'])->name('vista.admin_agregar_producto');
        Route::post('/admin/productos', [controller_karaoke::class, 'store_producto'])->name('admin.productos.store');
    });
    //ruta para los usuarios de tipo cocinero
    Route::middleware(['midctu:cocinero'])->group(function () {
        Route::get('/view_cocina/cocina_historial', [controller_karaoke::class, 'ver_cocina_historial'])->name('vista.cocina_historial');
        Route::get('/view_cocina/cocina_inventario', [controller_karaoke::class, 'ver_cocina_inventario'])->name('vista.cocina_inventario');
        Route::post('/cocina/pedido/{detalle}/listo', [controller_karaoke::class, 'marcarPedidoListo'])->name('cocina.pedido.listo');
        Route::post('/cocina/inventario/pedido', [controller_karaoke::class, 'marcarProductosPedido'])->name('cocina.inventario.pedido');
    });

    //ruta para los usuarios de tipo bartender
    Route::middleware(['midctu:bartender'])->group(function () {
        Route::get('/view_barra/barra_historial', [controller_karaoke::class, 'ver_barra_historial'])->name('vista.barra_historial');
        Route::get('/view_barra/barra_inventario', [controller_karaoke::class, 'ver_barra_inventario'])->name('vista.barra_inventario');
        Route::post('/barra/pedido/{detalle}/listo', [controller_karaoke::class, 'marcarPedidoListo'])->name('barra.pedido.listo');
    
    });

    //ruta para los usuarios de tipo mesero
    Route::middleware(['midctu:mesero'])->group(function () {
        Route::get('/view_mozo/mozo_historial', [controller_karaoke::class, 'ver_mozo_historial'])->name('vista.mozo_historial');
        Route::get('/view_mozo/mozo_mesa', [controller_karaoke::class, 'ver_mozo_mesa'])->name('vista.mozo_mesa');

        Route::get('/view_mozo/mozo_pedido/mesa/{mesa}', [controller_karaoke::class, 'ver_mozo_pedido_mesa'])->name('vista.mozo_pedido_mesa');
        Route::get('/view_mozo/mozo_pedido/historial', [controller_karaoke::class, 'ver_mozo_pedido_historial'])->name('vista.mozo_pedido_historial');
        Route::post('/view_mozo/mozo_pedido/procesar', [controller_karaoke::class, 'procesar_mozo_pedido'])->name('vista.procesar_mozo_pedido');
        Route::post('/view_mozo/mozo_pedido/confirmar', [controller_karaoke::class, 'confirmar_mozo_pedido'])->name('vista.confirmar_mozo_pedido');

        // Nuevas rutas funcionales
        Route::get('/pedidos/{pedido}', [controller_karaoke::class, 'ver_pedido'])->name('pedidos.ver');
        Route::get('/pedidos/{pedido}/editar', [controller_karaoke::class, 'editar_pedido'])->name('pedidos.editar');
        Route::put('/pedidos/{pedido}', [controller_karaoke::class, 'actualizar_pedido'])->name('pedidos.actualizar');
        Route::get('/pedidos/{pedido}/agregar', [controller_karaoke::class, 'agregar_productos_pedido'])->name('pedidos.agregar');
        Route::post('/pedidos/{pedido}/finalizar', [controller_karaoke::class, 'finalizar_pedido'])->name('pedidos.finalizar');
        Route::delete('/pedidos/{pedido}', [controller_karaoke::class, 'eliminar_pedido'])->name('pedidos.eliminar');

        // Nuevas rutas de facturación
        Route::get('/pedidos/{pedido}/facturar', [controller_karaoke::class, 'mostrar_facturacion'])->name('pedidos.facturar');
        Route::post('/pedidos/{pedido}/facturar', [controller_karaoke::class, 'procesar_facturacion'])->name('pedidos.procesar_facturacion');
        Route::post('/factura/{comprobante}/enviar-correo', [controller_karaoke::class, 'enviar_correo_form'])->name('factura.enviar_correo_form');
        Route::post('/factura/{comprobante}/enviar', [controller_karaoke::class, 'enviar_correo'])->name('factura.enviar_correo');
        Route::get('/factura/{comprobante}/vista-previa', [controller_karaoke::class, 'vista_previa_pdf'])->name('factura.vista_previa');

        // Rutas en desuso - mantener por compatibilidad
        Route::get('/view_mozo/mozo_pedidos', [controller_karaoke::class, 'ver_mozo_pedidos'])->name('vista.mozo_pedidos');
        Route::get('/view_mozo/mozo_pedidos/{pedido}', [controller_karaoke::class, 'ver_mozo_pedido'])->name('vista.mozo_pedido');
        Route::get('/pedidos/crear', [controller_karaoke::class, 'crear_pedido'])->name('pedidos.crear');
    });

});

// Rutas de Logueo - Autenticación - Registro - Logout
Route::get('/login', [controller_login::class, 'showLoginForm'])->name('login');
Route::post('/verificarlogin', [controller_login::class, 'login'])->name('verificar.login');
// RECOMENDADO: Cambiar la ruta de logout a POST para mayor seguridad
Route::post('/logout', [controller_login::class, 'logout'])->name('logout');
