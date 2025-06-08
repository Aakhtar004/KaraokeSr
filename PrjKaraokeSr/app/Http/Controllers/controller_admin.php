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
    public function ver_admin_historial() 
    {
        return view('view_admin.admin_historial');
    }

    // VER LISTA DE COMPRAS PENDIENTES
    public function ver_admin_compras() 
    {
        return view('view_admin.admin_compras');
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
}
