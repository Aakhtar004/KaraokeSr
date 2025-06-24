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

class controller_admin extends Controller
{
  
    // MODIFICAR PRECIOS Y STOCK
    public function ver_admin_modificar_categoria()
    {
        $categorias = categorias_producto::all();
        return view('view_admin.admin_modificar_categoria', compact('categorias'));
    }

    public function ver_admin_modificar_producto(categorias_producto $categoria)
    {
        $productos = $categoria->productos;
        return view('view_admin.admin_modificar_producto',compact('categoria', 'productos'));
    }

    public function actualizarProducto(Request $request, productos $producto)
    {
        $rules = ['precio_unitario' => 'required|numeric|min:0'];
        if ($producto->categoria->nombre === 'Cocteles') {
            $request->merge(['estado' => $request->has('estado')]);
            $rules['estado'] = 'boolean';
        } else {
            $rules['stock'] = 'required|integer|min:0';
        }
        $data = $request->validate($rules);

        $producto->update($data);
        return back()->with('success', "«{$producto->nombre}» actualizado");
    }

    // VER HISTORIAL DE PEDIDOS
    public function ver_admin_historial_ventas() 
    {
        $hoy = now()->format('Y-m-d');
        $pedidos = pedidos::with(['mesa', 'detalles.producto', 'mesero', 'comprobante'])
            ->whereDate('fecha_hora_pedido', $hoy)
            ->orderBy('fecha_hora_pedido', 'desc')
            ->get();
        
        return view('view_admin.admin_historial_ventas', compact('pedidos', 'hoy'));
    }

    public function filtrar_historial_pedidos(Request $request)
    {
        $tipo = $request->input('tipo', 'dia');
        $fecha = $request->input('fecha', now()->format('Y-m-d'));
        $hoy = now()->format('Y-m-d');
        
        $query = pedidos::with(['mesa', 'detalles.producto', 'mesero', 'comprobante']);
        
        switch ($tipo) {
            case 'dia':
                $query->whereDate('fecha_hora_pedido', $fecha);
                break;
            case 'semana':
                $inicioSemana = now()->parse($fecha)->startOfWeek();
                $finSemana = now()->parse($fecha)->endOfWeek();
                $query->whereBetween('fecha_hora_pedido', [$inicioSemana, $finSemana]);
                break;
            case 'mes':
                $query->whereYear('fecha_hora_pedido', now()->parse($fecha)->year)
                      ->whereMonth('fecha_hora_pedido', now()->parse($fecha)->month);
                break;
        }
        
        $pedidos = $query->orderBy('fecha_hora_pedido', 'desc')->get();
        
        return view('view_admin.admin_historial_ventas', compact('pedidos', 'fecha', 'tipo', 'hoy'));
    }

    public function ver_detalle_pedido($fecha)
    {
        $pedidos = pedidos::with(['mesa', 'detalles.producto', 'comprobante'])
            ->whereDate('fecha_hora_pedido', $fecha)
            ->orderBy('fecha_hora_pedido', 'desc')
            ->get();
        
        return view('view_admin.admin_detalle_pedido', compact('pedidos', 'fecha'));
    }

    // VER LISTA DE COMPRAS PENDIENTES
    public function ver_admin_compras() 
    {
        // Categorías para cocina
        $categoriasCocina = ['Condimentos y Especias', 'Materias Primas', 'Salsas Y Aderezos', 'No comestibles'];
        $categoriasBar = ['Frutas', 'Ingredientes', 'Bebidas de Barra', 'Licores de Barra'];
        
        $productosCocina = productos::whereHas('categoria', function($query) use ($categoriasCocina) {
            $query->whereIn('nombre', $categoriasCocina);
        })->where('estado', 0)->with('categoria')->get();
        
        $productosBar = productos::whereHas('categoria', function($query) use ($categoriasBar) {
            $query->whereIn('nombre', $categoriasBar);
        })->where('estado', 0)->with('categoria')->get();
        
        return view('view_admin.admin_compras', compact('productosCocina', 'productosBar'));
    }

    // GENERAR LISTA DE COMPRAS
    public function ver_admin_generar_lista_compras()
    {
        // Categorías para cocina
        $categoriasCocina = ['Condimentos y Especias', 'Materias Primas', 'Salsas Y Aderezos', 'No comestibles'];
        $categoriasBar = ['Frutas', 'Ingredientes', 'Bebidas de Barra', 'Licores de Barra'];
        
        // Productos de cocina que necesitan ser pedidos (estado = 0)
        $productosCocina = productos::whereHas('categoria', function($query) use ($categoriasCocina) {
            $query->whereIn('nombre', $categoriasCocina);
        })
        ->where('estado', 0) // Cambio: filtrar por estado 0 (necesita pedirse)
        ->with('categoria')
        ->orderBy('nombre', 'asc')
        ->get();
        
        // Productos de bar que necesitan ser pedidos (estado = 0)
        $productosBar = productos::whereHas('categoria', function($query) use ($categoriasBar) {
            $query->whereIn('nombre', $categoriasBar);
        })
        ->where('estado', 0) // Cambio: filtrar por estado 0 (necesita pedirse)
        ->with('categoria')
        ->orderBy('nombre', 'asc')
        ->get();
        
        return view('view_admin.admin_generar_lista_compras', compact('productosCocina', 'productosBar'));
    }

    // NUEVO MÉTODO PARA MARCAR PRODUCTOS COMO REABASTECIDOS
    public function marcar_productos_reabastecidos(Request $request)
    {
        try {
            $request->validate([
                'productos' => 'required|array|min:1',
                'productos.*' => 'exists:productos,id_producto'
            ], [
                'productos.required' => 'Debe seleccionar al menos un producto',
                'productos.min' => 'Debe seleccionar al menos un producto',
                'productos.*.exists' => 'Uno o más productos seleccionados no son válidos'
            ]);

            $productosIds = $request->input('productos');
            
            // Verificar que los productos pertenezcan a las categorías correctas
            $categoriasCocinaBar = [
                'Condimentos y Especias', 'Materias Primas', 'Salsas Y Aderezos', 
                'No comestibles', 'Frutas', 'Ingredientes', 'Bebidas de Barra', 'Licores de Barra'
            ];
            
            $productosValidos = productos::whereIn('id_producto', $productosIds)
                ->whereHas('categoria', function($query) use ($categoriasCocinaBar) {
                    $query->whereIn('nombre', $categoriasCocinaBar);
                })
                ->where('estado', 0) // Solo productos que están sin stock
                ->get();

            if ($productosValidos->isEmpty()) {
                return back()->with('error', 'No se encontraron productos válidos para reabastecer.');
            }

            // Actualizar el estado de los productos a 1 (disponible)
            $productosActualizados = productos::whereIn('id_producto', $productosValidos->pluck('id_producto'))
                ->update([
                    'estado' => 1,
                    'fecha_actualizacion' => now()
                ]);

            $cantidadActualizada = $productosValidos->count();
            $nombresProductos = $productosValidos->pluck('nombre')->take(3)->join(', ');
            
            if ($cantidadActualizada > 3) {
                $nombresProductos .= ' y ' . ($cantidadActualizada - 3) . ' más';
            }

            return redirect()->route('vista.admin_generar_lista_compras')
                ->with('success', "Se reabastecieron {$cantidadActualizada} producto(s): {$nombresProductos}");

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->with('error', 'Error de validación: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error al reabastecer productos', [
                'productos' => $request->input('productos', []),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Error inesperado al reabastecer productos. Intente nuevamente.');
        }
    }

    // GESTIÓN DE USUARIOS
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
                'usuario.required' => 'El usuario de acceso es obligatorio.',
                'usuario.string' => 'El usuario debe ser texto válido.',
                'usuario.max' => 'El usuario no puede tener más de 50 caracteres.',
                'usuario.unique' => 'Este usuario ya existe en el sistema.',
                'usuario.regex' => 'El usuario solo puede contener letras, números y guiones.',
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
                'usuario' => ['required', 'string', 'max:50', 'unique:usuarios,usuario', 'regex:/^[a-zA-Z0-9_-]+$/'],
                'contrasena' => ['required', 'string', 'min:6', 'confirmed', 'regex:/^\S+$/'],
                'rol' => ['required', 'in:administrador,mesero,cocinero,bartender']
            ], $messages);

            usuarios::create([
                'codigo_usuario' => trim($validatedData['codigo_usuario']),
                'usuario' => trim($validatedData['usuario']),
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
                ->with('modal_type', 'add');
        } catch (\Exception $e) {
            return redirect()->route('vista.admin_gestion_usuarios')
                ->with('error', 'Error inesperado: No se pudo crear el usuario.')
                ->with('show_modal_add', true)
                ->with('modal_type', 'add');
        }
    }

    public function modificar_usuario(Request $request, $usuario)
    {
        try {
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
                'usuario.required' => 'El usuario de acceso es obligatorio.',
                'usuario.string' => 'El usuario debe ser texto válido.',
                'usuario.max' => 'El usuario no puede tener más de 50 caracteres.',
                'usuario.unique' => 'Este usuario ya existe en el sistema.',
                'usuario.regex' => 'El usuario solo puede contener letras, números y guiones.',
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
                'usuario' => ['required', 'string', 'max:50', 'unique:usuarios,usuario,' . $usuario . ',id_usuario', 'regex:/^[a-zA-Z0-9_-]+$/'],
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
                'usuario' => trim($validatedData['usuario']),
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
                ->with('modal_type', 'edit')
                ->with('edit_data', [
                    'id' => $usuario,
                    'nombres' => $request->input('nombres'),
                    'codigo_usuario' => $request->input('codigo_usuario'),
                    'usuario' => $request->input('usuario'),
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
                ->with('modal_type', 'edit');
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

    // AGREGAR PRODUCTOS
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

    // GESTIÓN DE PROMOCIONES
    public function ver_admin_promociones()
    {
        $promociones = promociones::with(['productos.producto'])->orderBy('fecha_creacion', 'desc')->get();
        
        // Obtener productos para el modal
        $categoriasMesero = ['Piqueos', 'Cocteles', 'Licores', 'Bebidas', 'Cervezas', 'Jarras', 'Baldes'];
        $productos = productos::whereHas('categoria', function($query) use ($categoriasMesero) {
            $query->whereIn('nombre', $categoriasMesero);
        })->where('estado', 1)->with('categoria')->get();
        
        return view('view_admin.admin_promociones', compact('promociones', 'productos'));
    }

    // FIX: Nuevo método para obtener datos de promoción específica
    public function obtener_promocion($id)
    {
        try {
            $promocion = promociones::with(['productos.producto'])->find($id);
            
            if (!$promocion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Promoción no encontrada'
                ], 404);
            }
            
            $productosIds = $promocion->productos->pluck('id_producto')->toArray();
            
            return response()->json([
                'success' => true,
                'promocion' => [
                    'id_promocion' => $promocion->id_promocion,
                    'nombre_promocion' => $promocion->nombre_promocion,
                    'descripcion_promocion' => $promocion->descripcion_promocion,
                    'precio_promocion' => $promocion->precio_promocion,
                    'fecha_inicio' => $promocion->fecha_inicio->format('Y-m-d'),
                    'fecha_fin' => $promocion->fecha_fin->format('Y-m-d'),
                    'stock_promocion' => $promocion->stock_promocion ?? 0,
                    'productos' => $productosIds
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener promoción', [
                'promocion_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    public function store_promocion(Request $request)
    {
        try {
            $request->validate([
                'nombre_promocion' => 'required|string|max:150|unique:promociones,nombre_promocion',
                'tipo_promocion' => 'required|in:2x1,10%descuento,50%descuento',
                'productos' => 'required|array|min:1',
                'productos.*' => 'exists:productos,id_producto',
                'fecha_inicio' => 'required|date|after_or_equal:today',
                'fecha_fin' => 'required|date|after:fecha_inicio',
                'stock_promocion' => 'required|integer|min:1|max:999'
            ], [
                'nombre_promocion.required' => 'El nombre de la promoción es obligatorio',
                'nombre_promocion.unique' => 'Ya existe una promoción con este nombre',
                'tipo_promocion.required' => 'Debe seleccionar un tipo de promoción',
                'productos.required' => 'Debe seleccionar al menos un producto',
                'productos.min' => 'Debe seleccionar al menos un producto',
                'fecha_inicio.after_or_equal' => 'La fecha de inicio no puede ser anterior a hoy',
                'fecha_fin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio',
                'stock_promocion.required' => 'El stock de promoción es obligatorio',
                'stock_promocion.min' => 'El stock debe ser mínimo 1',
                'stock_promocion.max' => 'El stock no puede ser mayor a 999'
            ]);

            // Validar que el stock no exceda el stock mínimo de los productos
            $productosIds = $request->input('productos');
            $productos = productos::whereIn('id_producto', $productosIds)->get();
            $stockMinimo = $productos->min('stock');
            
            if ($request->stock_promocion > $stockMinimo) {
                return response()->json([
                    'success' => false,
                    'message' => "El stock de promoción no puede ser mayor al stock mínimo de los productos seleccionados ({$stockMinimo})"
                ], 422);
            }

            // Calcular precio de promoción basado en tipo
            $precioTotal = $productos->sum('precio_unitario');
            
            switch ($request->tipo_promocion) {
                case '2x1':
                    $precioPromocion = $precioTotal / 2;
                    $descripcion = '2x1 - Paga 1 y lleva 2';
                    break;
                case '10%descuento':
                    $precioPromocion = $precioTotal * 0.9;
                    $descripcion = '10% de descuento';
                    break;
                case '50%descuento':
                    $precioPromocion = $precioTotal * 0.5;
                    $descripcion = '50% de descuento';
                    break;
                default:
                    $precioPromocion = $precioTotal;
                    $descripcion = $request->tipo_promocion;
            }

            $promocion = promociones::create([
                'nombre_promocion' => $request->nombre_promocion,
                'descripcion_promocion' => $descripcion,
                'precio_promocion' => round($precioPromocion, 2),
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
                'estado_promocion' => 'activa',
                'stock_promocion' => $request->stock_promocion,
                'fecha_creacion' => now(),
                'fecha_actualizacion' => now()
            ]);

            // Agregar productos a la promoción
            foreach ($productosIds as $productoId) {
                $producto = productos::find($productoId);
                promocion_productos::create([
                    'id_promocion' => $promocion->id_promocion,
                    'id_producto' => $productoId,
                    'cantidad_producto_en_promo' => 1,
                    'precio_original_referencia' => $producto->precio_unitario
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Promoción agregada exitosamente'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al crear promoción', [
                'request' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    public function actualizar_promocion(Request $request, $id)
    {
        try {
            $promocion = promociones::find($id);
            
            if (!$promocion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Promoción no encontrada'
                ], 404);
            }
            
            // Diferentes reglas de validación para fechas dependiendo si es edición
            $fechaInicioRule = 'required|date';
            // Solo validar fecha futura si es una nueva fecha de inicio
            if ($request->fecha_inicio != $promocion->fecha_inicio->format('Y-m-d')) {
                $fechaInicioRule = 'required|date|after_or_equal:today';
            }
            
            $request->validate([
                'nombre_promocion' => 'required|string|max:150|unique:promociones,nombre_promocion,' . $id . ',id_promocion',
                'tipo_promocion' => 'required|in:2x1,10%descuento,50%descuento',
                'productos' => 'required|array|min:1',
                'productos.*' => 'exists:productos,id_producto',
                'fecha_inicio' => $fechaInicioRule,
                'fecha_fin' => 'required|date|after:fecha_inicio',
                'stock_promocion' => 'required|integer|min:1|max:999'
            ], [
                'nombre_promocion.required' => 'El nombre de la promoción es obligatorio',
                'nombre_promocion.unique' => 'Ya existe una promoción con este nombre',
                'tipo_promocion.required' => 'Debe seleccionar un tipo de promoción',
                'productos.required' => 'Debe seleccionar al menos un producto',
                'productos.min' => 'Debe seleccionar al menos un producto',
                'fecha_inicio.after_or_equal' => 'La fecha de inicio no puede ser anterior a hoy',
                'fecha_fin.after' => 'La fecha de fin debe ser posterior a la fecha de inicio',
                'stock_promocion.required' => 'El stock de promoción es obligatorio',
                'stock_promocion.min' => 'El stock debe ser mínimo 1',
                'stock_promocion.max' => 'El stock no puede ser mayor a 999'
            ]);

            // Validar stock
            $productosIds = $request->input('productos');
            $productos = productos::whereIn('id_producto', $productosIds)->get();
            
            if ($productos->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron productos válidos'
                ], 422);
            }
            
            $stockMinimo = $productos->min('stock');
            
            if ($request->stock_promocion > $stockMinimo) {
                return response()->json([
                    'success' => false,
                    'message' => "El stock de promoción no puede ser mayor al stock mínimo de los productos seleccionados ({$stockMinimo})"
                ], 422);
            }

            // Calcular nuevo precio
            $precioTotal = $productos->sum('precio_unitario');
            
            switch ($request->tipo_promocion) {
                case '2x1':
                    $precioPromocion = $precioTotal / 2;
                    $descripcion = '2x1 - Paga 1 y lleva 2';
                    break;
                case '10%descuento':
                    $precioPromocion = $precioTotal * 0.9;
                    $descripcion = '10% de descuento';
                    break;
                case '50%descuento':
                    $precioPromocion = $precioTotal * 0.5;
                    $descripcion = '50% de descuento';
                    break;
                default:
                    $precioPromocion = $precioTotal;
                    $descripcion = $request->tipo_promocion;
            }

            $promocion->update([
                'nombre_promocion' => $request->nombre_promocion,
                'descripcion_promocion' => $descripcion,
                'precio_promocion' => round($precioPromocion, 2),
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
                'stock_promocion' => $request->stock_promocion,
                'fecha_actualizacion' => now()
            ]);

            // Actualizar productos de la promoción
            promocion_productos::where('id_promocion', $promocion->id_promocion)->delete();
            
            foreach ($productosIds as $productoId) {
                $producto = productos::find($productoId);
                if ($producto) {
                    promocion_productos::create([
                        'id_promocion' => $promocion->id_promocion,
                        'id_producto' => $productoId,
                        'cantidad_producto_en_promo' => 1,
                        'precio_original_referencia' => $producto->precio_unitario
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Promoción actualizada correctamente'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al actualizar promoción', [
                'promocion_id' => $id,
                'request' => $request->all(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    public function eliminar_promocion($id)
    {
        try {
            $promocion = promociones::findOrFail($id);
            $promocion->delete();
            
            return redirect()->route('vista.admin_promociones')
                ->with('success', 'Promoción eliminada exitosamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar promoción: ' . $e->getMessage());
        }
    }

    public function toggle_promocion($id)
    {
        try {
            $promocion = promociones::findOrFail($id);
            $nuevoEstado = $promocion->estado_promocion === 'activa' ? 'inactiva' : 'activa';
            
            $promocion->update([
                'estado_promocion' => $nuevoEstado,
                'fecha_actualizacion' => now()
            ]);
            
            return response()->json(['success' => true, 'estado' => $nuevoEstado]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function obtener_productos_promocion()
    {
        try {
            $categoriasMesero = ['Piqueos', 'Cocteles', 'Licores', 'Bebidas', 'Cervezas', 'Jarras', 'Baldes'];
            $productos = productos::whereHas('categoria', function($query) use ($categoriasMesero) {
                $query->whereIn('nombre', $categoriasMesero);
            })->where('estado', 1)->with('categoria')->get();
            
            return response()->json([
                'success' => true,
                'productos' => $productos
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener productos para promociones', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos: ' . $e->getMessage()
            ], 500);
        }
    }

    
}

