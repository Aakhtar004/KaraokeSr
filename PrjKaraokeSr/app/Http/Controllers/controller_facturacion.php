<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

// Importar modelos
use App\Models\categorias_producto;
use App\Models\productos;
use App\Models\pedidos;
use App\Models\mesas;
use App\Models\usuarios;
use App\Models\pedido_detalles;
use App\Models\comprobantes;
use App\Models\pagos_pedido_detalle;

class controller_facturacion extends Controller
{
    public function finalizar_pedido(Request $request, $idPedido)
    {
        // CORREGIDO: Incluir relaciones necesarias para baldes
        $pedido = pedidos::with(['detalles.producto', 'detalles.producto_base'])->findOrFail($idPedido);
        
        // VALIDAR QUE TODOS LOS PRODUCTOS ESTÉN LISTOS PARA ENTREGA
        $productosNoListos = $pedido->detalles->where('estado_item', '!=', 'LISTO_PARA_ENTREGA');
        
        if ($productosNoListos->count() > 0) {
            // CORREGIDO: Obtener nombres según el tipo de producto
            $nombresProductos = $productosNoListos->map(function($detalle) {
                return $this->obtenerNombreDetalle($detalle);
            })->join(', ');
            
            return redirect()->route('vista.mozo_historial')
                ->with('error', "No se puede finalizar el pedido. Los siguientes productos aún no están listos: {$nombresProductos}");
        }
        
        // Si todos están listos, redirigir a facturación
        return redirect()->route('pedidos.facturar', $idPedido);
    }

    public function mostrar_facturacion($idPedido)
    {
        // CORREGIDO: Incluir relaciones necesarias para baldes
        $pedido = pedidos::with(['mesa', 'detalles.producto', 'detalles.producto_base'])->findOrFail($idPedido);
        
        // VERIFICAR QUE TODOS LOS PRODUCTOS ESTÉN LISTOS PARA ENTREGA
        $productosNoListos = $pedido->detalles->where('estado_item', '!=', 'LISTO_PARA_ENTREGA');
        
        if ($productosNoListos->count() > 0) {
            // CORREGIDO: Obtener nombres según el tipo de producto
            $nombresProductos = $productosNoListos->map(function($detalle) {
                return $this->obtenerNombreDetalle($detalle);
            })->join(', ');
            
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
        
        // CORREGIDO: Prepara los productos para JS incluyendo baldes
        $productosPedido = [];
        foreach ($pedido->detalles as $detalle) {
            $productosPedido[] = [
                'id' => $detalle->id_pedido_detalle,
                'nombre' => $this->obtenerNombreDetalle($detalle),
                'cantidad' => $detalle->cantidad,
                'precio' => $detalle->precio_unitario_momento
            ];
        }

        return view('view_mozo.mozo_pedido_facturacion', compact('pedido', 'productosPedido'));
    }

    public function procesar_facturacion(Request $request, $idPedido)
    {
        Log::info('Payload recibido en facturación', $request->all());
        
        // CORREGIDO: Incluir relaciones necesarias para baldes
        $pedido = pedidos::with(['detalles.producto', 'detalles.producto_base', 'mesa'])->findOrFail($idPedido);

        if ($request->input('division') == '1') {
            $divisiones = $request->input('divisiones');
            $modo = $request->input('modoDivision');
            DB::beginTransaction();
            try {
                $comprobantes = [];
                $total = 0;
                
                // AGREGADO: Variable para capturar el primer nombre de cliente válido
                $nombreClientePedido = null;

                // --- NUEVO: Procesar división por ítem correctamente ---
                if ($modo === 'item') {
                    // Mapear: [cuenta][id_pedido_detalle] => cantidad
                    $asignacionPorCuenta = [];
                    // Recorrer todos los detalles del pedido
                    foreach ($pedido->detalles as $detalle) {
                        $idDetalle = $detalle->id_pedido_detalle;
                        $cantidad = $detalle->cantidad;
                        // Para cada unidad de este producto, buscar a qué cuenta fue asignada
                        for ($q = 1; $q <= $cantidad; $q++) {
                            // Buscar en el request qué cuenta tiene el checkbox marcado para este producto/unidad
                            for ($c = 0; $c < count($divisiones); $c++) {
                                $chkName = "chk_{$idDetalle}_{$q}_{$c}";
                                // El frontend debe enviar los checkboxes como chk_{idDetalle}_{q}_{c}=on
                                if ($request->has($chkName)) {
                                    if (!isset($asignacionPorCuenta[$c])) $asignacionPorCuenta[$c] = [];
                                    if (!isset($asignacionPorCuenta[$c][$idDetalle])) $asignacionPorCuenta[$c][$idDetalle] = 0;
                                    $asignacionPorCuenta[$c][$idDetalle]++;
                                }
                            }
                        }
                    }

                    // Validar que cada producto/unidad esté asignado a una sola cuenta
                    $totalAsignado = 0;
                    foreach ($asignacionPorCuenta as $cuenta => $detalles) {
                        foreach ($detalles as $idDetalle => $cant) {
                            $totalAsignado += $cant;
                        }
                    }
                    $totalPedidoUnidades = $pedido->detalles->sum('cantidad');
                    if ($totalAsignado != $totalPedidoUnidades) {
                        throw new \Exception('La asignación de productos a cuentas es incorrecta.');
                    }

                    // Para cada cuenta, crear comprobante solo con los productos asignados
                    foreach ($divisiones as $idx => $div) {
                        $productosCuenta = $asignacionPorCuenta[$idx] ?? [];
                        if (empty($productosCuenta)) continue;

                        $montoCuenta = 0;
                        $subtotalCuenta = 0;
                        $pagosDetalle = [];
                        foreach ($productosCuenta as $idDetalle => $cant) {
                            $detalle = $pedido->detalles->firstWhere('id_pedido_detalle', $idDetalle);
                            if (!$detalle) continue;
                            $precioUnit = $detalle->precio_unitario_momento;
                            $subtotal = round($precioUnit * $cant, 2);
                            $subtotalCuenta += $subtotal;
                            $pagosDetalle[] = [
                                'detalle' => $detalle,
                                'cantidad' => $cant,
                                'subtotal' => $subtotal
                            ];
                        }
                        $subtotalSinIgv = round($subtotalCuenta / 1.18, 2);
                        $igv = round($subtotalCuenta - $subtotalSinIgv, 2);
                        $montoCuenta = $subtotalCuenta;

                        // Datos de cliente
                        $tipo = $div['tipo_comprobante'];

                        // CAMBIO: Obtener nombre desde el campo correcto según el tipo
                        if ($tipo === 'boleta') {
                            $nombre = $div['nombre_boleta'] ?? null;
                            $dni = $div['dni'] ?? null;
                        } else {
                            $nombre = $div['nombre_notaventa'] ?? null;
                            $dni = null;
                        }
                        
                        // FIX: Capturar primer nombre válido para actualizar el pedido
                        if (!$nombreClientePedido && !empty($nombre) && $nombre !== 'Cliente') {
                            $nombreClientePedido = $nombre;
                        }

                        $comprobante = comprobantes::create([
                            'id_pedido' => $pedido->id_pedido,
                            'id_usuario_cajero' => Auth::id(),
                            'tipo_documento_cliente' => $tipo === 'boleta' ? 'DNI' : 'SIN_DOCUMENTO',
                            'numero_documento_cliente' => $dni,
                            'nombre_razon_social_cliente' => $nombre ?: 'Cliente',
                            'direccion_cliente' => null,
                            'serie_comprobante' => $tipo === 'boleta' ? 'B001' : 'NV01',
                            'numero_correlativo_comprobante' => $this->generarCorrelativo($tipo, $tipo === 'boleta' ? 'B001' : 'NV01'),
                            'fecha_emision' => now(),
                            'moneda' => 'PEN',
                            'subtotal_comprobante' => $subtotalSinIgv,
                            'igv_aplicado_tasa' => 18.00,
                            'monto_igv' => $igv,
                            'monto_total_comprobante' => $montoCuenta,
                            'tipo_comprobante' => $tipo,
                            'metodo_pago' => $div['metodo_pago'],
                            'referencia_pago' => null,
                            'estado_comprobante' => 'EMITIDO',
                            'qr_code_data' => null,
                            'hash_sunat' => null,
                            'notas_comprobante' => null,
                            'fecha_anulacion' => null,
                        ]);
                        $comprobantes[] = $comprobante;
                        $total += $montoCuenta;

                        // Crear pagos_pedido_detalle para cada producto de la cuenta
                        foreach ($pagosDetalle as $pd) {
                            pagos_pedido_detalle::create([
                                'id_comprobante' => $comprobante->id_comprobante,
                                'id_pedido_detalle' => $pd['detalle']->id_pedido_detalle,
                                'cantidad_item_pagada' => $pd['cantidad'],
                                'monto_pagado' => $pd['subtotal'],
                                'metodo_pago' => $div['metodo_pago'],
                                'referencia_pago' => 'REF-' . $comprobante->numero_correlativo_comprobante . '-' . $pd['detalle']->id_pedido_detalle
                            ]);
                        }
                    }
                    // Validar suma total
                    $totalPedido = $pedido->detalles->sum(function($d) { return $d->cantidad * $d->precio_unitario_momento; });
                    if (abs($total - $totalPedido) > 0.01) {
                        throw new \Exception('La suma de los montos no coincide con el total del pedido.');
                    }

                } else {
                    // ...existing code for division by monto...
                    // (sin cambios)
                    foreach ($divisiones as $div) {
                        $tipo = $div['tipo_comprobante'];
                        $monto = $modo === 'monto' ? floatval($div['monto']) : round($pedido->total_pedido / count($divisiones), 2);

                        $subtotal = round($monto / 1.18, 2);
                        $igv = round($monto - $subtotal, 2);

                        $nombre = $tipo === 'nota_venta' ? ($div['nombre'] ?? null) : ($div['nombre'] ?? null);
                        $dni = $tipo === 'boleta' ? ($div['dni'] ?? null) : null;
                        
                        // FIX: Capturar primer nombre válido para actualizar el pedido
                        if (!$nombreClientePedido && !empty($nombre) && $nombre !== 'Cliente') {
                            $nombreClientePedido = $nombre;
                        }

                        $comprobante = comprobantes::create([
                            'id_pedido' => $pedido->id_pedido,
                            'id_usuario_cajero' => Auth::id(),
                            'tipo_documento_cliente' => $tipo === 'boleta' ? 'DNI' : 'SIN_DOCUMENTO',
                            'numero_documento_cliente' => $dni,
                            'nombre_razon_social_cliente' => $nombre ?: 'Cliente',
                            'direccion_cliente' => null,
                            'serie_comprobante' => $tipo === 'boleta' ? 'B001' : 'NV01',
                            'numero_correlativo_comprobante' => $this->generarCorrelativo($tipo, $tipo === 'boleta' ? 'B001' : 'NV01'),
                            'fecha_emision' => now(),
                            'moneda' => 'PEN',
                            'subtotal_comprobante' => $subtotal,
                            'igv_aplicado_tasa' => 18.00,
                            'monto_igv' => $igv,
                            'monto_total_comprobante' => $monto,
                            'tipo_comprobante' => $tipo,
                            'metodo_pago' => $div['metodo_pago'],
                            'referencia_pago' => null,
                            'estado_comprobante' => 'EMITIDO',
                            'qr_code_data' => null,
                            'hash_sunat' => null,
                            'notas_comprobante' => null,
                            'fecha_anulacion' => null,
                        ]);
                        $comprobantes[] = $comprobante;
                        $total += $monto;
                    }
                    if (abs($total - $pedido->total_pedido) > 0.01) {
                        throw new \Exception('La suma de los montos no coincide con el total del pedido.');
                    }
                }
                
                // FIX: Actualizar el nombre del cliente en el pedido
                if ($nombreClientePedido) {
                    $pedido->update(['nombre_cliente' => $nombreClientePedido]);
                }
                
                // ACTUALIZAR ESTADO DEL PEDIDO Y LIBERAR LA MESA (DIVISIÓN)
                $pedido->update(['estado_pedido' => 'PAGADO']);
                $mesa = $pedido->mesa;
                if ($mesa) {
                    $mesa->update(['estado' => 'disponible']);
                }

                DB::commit();
                return response()->json([
                    'success' => true,
                    'comprobante_id' => $comprobantes[0]->id_comprobante
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Lo sentimos, no se pudo procesar la división de la cuenta. Por favor, inténtalo de nuevo. ' . $e->getMessage()
                ], 500);
            }
        } else {
            // Verificar que el pedido no haya sido ya procesado
            $comprobanteExistente = comprobantes::where('id_pedido', $idPedido)->first();
            if ($comprobanteExistente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este pedido ya fue procesado.',
                    'comprobante_id' => $comprobanteExistente->id_comprobante
                ], 400);
            }

            // Validar métodos de pago
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

                // MODIFICADO: Obtener el nombre del cliente desde el formulario
                $nombreCliente = $request->input('nombre_cliente', 'Cliente');
                
                // Si el nombre sigue siendo "Cliente", intentar construirlo desde el documento
                if ($nombreCliente === 'Cliente' || empty(trim($nombreCliente))) {
                    $tipoDoc = $request->tipo_comprobante === 'factura' ? 'RUC' : 'DNI';
                    $numeroDoc = $request->documento ?? 'Sin documento';
                    $nombreCliente = "Cliente - {$tipoDoc}: {$numeroDoc}";
                }

                // Determinar serie y tipo de documento según el tipo de comprobante
                if ($request->tipo_comprobante === 'factura') {
                    $serie = 'F001';
                    $tipoDocumento = 'RUC';
                } elseif ($request->tipo_comprobante === 'boleta') {
                    $serie = 'B001';
                    $tipoDocumento = 'DNI';
                } elseif ($request->tipo_comprobante === 'nota_venta') {
                    $serie = 'NV01';
                    $tipoDocumento = 'SIN_DOCUMENTO';
                } else {
                    // Si llega un tipo no válido, lanzar error
                    return response()->json([
                        'success' => false,
                        'message' => 'Tipo de comprobante no válido.'
                    ], 400);
                }

                // Crear el comprobante con el nombre obtenido de la API
                $comprobante = comprobantes::create([
                    'id_pedido' => $pedido->id_pedido,
                    'id_usuario_cajero' => Auth::id(),
                    'tipo_documento_cliente' => $tipoDocumento,
                    'numero_documento_cliente' => $request->documento,
                    'nombre_razon_social_cliente' => $nombreCliente,
                    'direccion_cliente' => null,
                    'serie_comprobante' => $serie,
                    'numero_correlativo_comprobante' => $this->generarCorrelativo($request->tipo_comprobante, $serie),
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
    }

    public function enviar_correo_form(Request $request, $idComprobante)
    {
        $comprobante = comprobantes::findOrFail($idComprobante);
        
        return response()->json([
            'success' => true,
            'html' => view('modals.enviar_correo', compact('comprobante'))->render()
        ]);
    }

    public function generar_pdf_comprobante($idComprobante)
    {
        require_once base_path('vendor/setasign/fpdf/fpdf.php');
    
        try {
            // CORREGIDO: Incluir relaciones específicas para evitar conflictos
            $comprobante = comprobantes::with([
                'pedido.detalles' => function($query) {
                    $query->with(['producto', 'producto_base']);
                },
                'pedido.mesa',
                'pagosDetalle' => function($query) {
                    $query->with([
                        'detalle' => function($subQuery) {
                            $subQuery->with(['producto', 'producto_base']);
                        }
                    ]);
                }
            ])->findOrFail($idComprobante);
        
            // Crear PDF
            $pdf = new \FPDF();
            $pdf->AddPage();
        
            // Encabezado
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->Cell(0, 10, utf8_decode('RESTOBAR SALÓN ROJO'), 0, 1, 'C');
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 5, utf8_decode('KARAOKE'), 0, 1, 'C');
            $pdf->Cell(0, 5, utf8_decode('RUC: 10255667781'), 0, 1, 'C');
            $pdf->Cell(0, 5, utf8_decode('Gral Deustua 160, Tacna 23001'), 0, 1, 'C');
            $pdf->Ln(5);
        
            // Tipo de comprobante
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 8, utf8_decode(strtoupper($comprobante->tipo_comprobante) . ' ELECTRÓNICA'), 0, 1, 'C');
            $pdf->Cell(0, 8, utf8_decode($comprobante->serie_comprobante . '-' . $comprobante->numero_correlativo_comprobante), 0, 1, 'C');
            $pdf->Ln(5);
        
            // Datos del cliente
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(30, 6, 'CLIENTE:', 0);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 6, utf8_decode($comprobante->nombre_razon_social_cliente), 0, 1);
        
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(30, 6, utf8_decode($comprobante->tipo_documento_cliente . ':'), 0);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 6, $comprobante->numero_documento_cliente ?: '', 0, 1);
        
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(30, 6, 'FECHA:', 0);
            $pdf->SetFont('Arial', '', 10);
        
            if($comprobante->fecha_emision instanceof \Carbon\Carbon) {
                $pdf->Cell(0, 6, $comprobante->fecha_emision->format('d/m/Y H:i'), 0, 1);
            } else {
                $pdf->Cell(0, 6, \Carbon\Carbon::parse($comprobante->fecha_emision)->format('d/m/Y H:i'), 0, 1);
            }
        
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(30, 6, 'MESA:', 0);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 6, $comprobante->pedido->mesa->numero_mesa ?? '-', 0, 1);
        
            $pdf->Ln(5);
        
            // Tabla de productos
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(15, 7, 'CANT.', 1, 0, 'C');
            $pdf->Cell(100, 7, utf8_decode('DESCRIPCIÓN'), 1, 0, 'C');
            $pdf->Cell(35, 7, 'P. UNIT.', 1, 0, 'C');
            $pdf->Cell(35, 7, 'IMPORTE', 1, 1, 'C');
        
            $pdf->SetFont('Arial', '', 9);
        
            // CORREGIDO: Usar la misma lógica que la vista previa para pedidos divididos
            if ($comprobante->pagosDetalle && $comprobante->pagosDetalle->count() > 0) {
                // Si hay división de cuenta, mostrar solo productos pagados por este comprobante
                // Agrupar pagos por detalle para manejar cantidades correctamente
                $pagosPorDetalle = [];
                
                foreach ($comprobante->pagosDetalle as $pago) {
                    if ($pago->detalle) {
                        $idDetalle = $pago->detalle->id_pedido_detalle;
                        
                        if (!isset($pagosPorDetalle[$idDetalle])) {
                            $pagosPorDetalle[$idDetalle] = [
                                'detalle' => $pago->detalle,
                                'cantidad_total' => 0,
                                'monto_total' => 0
                            ];
                        }
                        
                        $pagosPorDetalle[$idDetalle]['cantidad_total'] += $pago->cantidad_item_pagada;
                        $pagosPorDetalle[$idDetalle]['monto_total'] += $pago->monto_pagado;
                    }
                }
                
                // Mostrar productos agrupados con cantidades correctas
                foreach ($pagosPorDetalle as $pagoData) {
                    $pdf->Cell(15, 6, $pagoData['cantidad_total'], 1, 0, 'C');
                    
                    // Obtener nombre según tipo de producto
                    $nombreProducto = $this->obtenerNombreDetalle($pagoData['detalle']);
                    $pdf->Cell(100, 6, utf8_decode($nombreProducto), 1, 0, 'L');
                    
                    $pdf->Cell(35, 6, 'S/ ' . number_format($pagoData['detalle']->precio_unitario_momento, 2), 1, 0, 'R');
                    $pdf->Cell(35, 6, 'S/ ' . number_format($pagoData['monto_total'], 2), 1, 1, 'R');
                }
            } else {
                // Si NO hay división, mostrar todos los productos del pedido (lógica original)
                foreach($comprobante->pedido->detalles as $detalle) {
                    $pdf->Cell(15, 6, $detalle->cantidad, 1, 0, 'C');
                    
                    // Obtener nombre según tipo de producto
                    $nombreProducto = $this->obtenerNombreDetalle($detalle);
                    $pdf->Cell(100, 6, utf8_decode($nombreProducto), 1, 0, 'L');
                    
                    $pdf->Cell(35, 6, 'S/ ' . number_format($detalle->precio_unitario_momento, 2), 1, 0, 'R');
                    $pdf->Cell(35, 6, 'S/ ' . number_format($detalle->subtotal, 2), 1, 1, 'R');
                }
            }
        
            // Totales
            $pdf->Ln(5);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(150, 6, 'SUBTOTAL:', 0, 0, 'R');
            $pdf->Cell(35, 6, 'S/ ' . number_format($comprobante->subtotal_comprobante, 2), 0, 1, 'R');
        
            $pdf->Cell(150, 6, 'IGV (18%):', 0, 0, 'R');
            $pdf->Cell(35, 6, 'S/ ' . number_format($comprobante->monto_igv, 2), 0, 1, 'R');
        
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(150, 8, 'TOTAL:', 0, 0, 'R');
            $pdf->Cell(35, 8, 'S/ ' . number_format($comprobante->monto_total_comprobante, 2), 0, 1, 'R');
        
            // Pie de página
            $pdf->Ln(10);
            $pdf->SetFont('Arial', '', 8);
            $pdf->Cell(0, 5, utf8_decode('Gracias por su preferencia'), 0, 1, 'C');
            $pdf->Cell(0, 5, utf8_decode('Este documento es una representación del comprobante electrónico'), 0, 1, 'C');
            $pdf->Cell(0, 5, utf8_decode('Generado automáticamente por el sistema de Karaoke Senior'), 0, 1, 'C');
        
            // Generar el PDF y devolverlo como respuesta
            return $pdf->Output('S', 'comprobante_' . $comprobante->serie_comprobante . '-' . $comprobante->numero_correlativo_comprobante . '.pdf');
        } catch (\Exception $e) {
            throw new \Exception('Error al generar PDF: ' . $e->getMessage());
        }
    }

    public function enviar_correo(Request $request, $idComprobante)
    {
        try {
            // CORREGIDO: Cargar relaciones de forma más específica para evitar conflictos
            $comprobante = comprobantes::with([
                'pedido.mesa',
                'pedido.detalles' => function($query) {
                    $query->with(['producto', 'producto_base']);
                },
                'pagosDetalle' => function($query) {
                    $query->with([
                        'detalle' => function($subQuery) {
                            $subQuery->with(['producto', 'producto_base']);
                        }
                    ]);
                }
            ])->findOrFail($idComprobante);
            
            $request->validate([
                'dni_correo' => 'required|string',
                'email' => 'required|email',
            ]);

            // CORREGIDO: Generar el contenido HTML del correo con manejo de errores
            try {
                $htmlContent = view('emails.comprobante', compact('comprobante'))->render();
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al generar el contenido del correo: ' . $e->getMessage()
                ], 500);
            }
            
            // CORREGIDO: Generar el PDF del comprobante con manejo de errores
            try {
                $pdfContent = $this->generar_pdf_comprobante($idComprobante);
                $pdfFileName = 'comprobante_' . $comprobante->serie_comprobante . '-' . $comprobante->numero_correlativo_comprobante . '.pdf';
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al generar el PDF: ' . $e->getMessage()
                ], 500);
            }
            
            // CORREGIDO: Usar el servicio de Resend con manejo de errores mejorado
            try {
                $resendService = new \App\Services\ApiResendService();
                $result = $resendService->sendEmailWithAttachment(
                    $request->email,
                    'Comprobante de Pago - ' . $comprobante->serie_comprobante . '-' . $comprobante->numero_correlativo_comprobante,
                    $htmlContent,
                    $pdfContent,
                    $pdfFileName
                );
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error en el servicio de envío: ' . $e->getMessage()
                ], 500);
            }
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Correo enviado exitosamente',
                    'comprobante_id' => $comprobante->id_comprobante
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al enviar el correo: ' . ($result['message'] ?? 'Error desconocido'),
                    'error' => $result['error'] ?? null
                ], 500);
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Comprobante no encontrado'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    public function vista_previa_pdf($idComprobante)
    {
        $comprobante = comprobantes::with(['pedido.detalles.producto', 'pedido.mesa'])->findOrFail($idComprobante);
        
        return view('view_mozo.mozo_pedido_vista_previa', compact('comprobante'));
    }

    public function slidePreview($idPedido)
    {
        $comprobantes = comprobantes::where('id_pedido', $idPedido)->get();
        return view('view_mozo.slide_preview_division', compact('comprobantes'));
    }

    // public function vistaPrevia($idComprobante)
    // {
    //     $comprobante = comprobantes::with(['pedido.mesa', 'pedido.detalles.producto'])->findOrFail($idComprobante);
    //     $comprobantesDivision = comprobantes::where('id_pedido', $comprobante->id_pedido)->get();
    //     return view('view_mozo.mozo_pedido_vista_previa', compact('comprobante', 'comprobantesDivision'));
    // }

    public function vistaPrevia($idComprobante)
    {
        $comprobante = comprobantes::with([
            'pedido.mesa',
            'pedido.detalles.producto',
            'pedido.detalles.producto_base', // AGREGADO: Para baldes normales
            'pagosDetalle.detalle.producto',
            'pagosDetalle.detalle.producto_base' // AGREGADO: Para baldes en división
        ])->findOrFail($idComprobante);

        $comprobantesDivision = comprobantes::where('id_pedido', $comprobante->id_pedido)->get();

        // Si hay registros de pagosDetalle, es división de cuenta
        $detallesPagados = [];
        $importeProductosPagados = 0;

        if ($comprobante->pagosDetalle && $comprobante->pagosDetalle->count() > 0) {
            foreach ($comprobante->pagosDetalle as $pago) {
                if ($pago->detalle) {
                    // CORREGIDO: Obtener nombre según tipo de producto
                    $nombreProducto = $this->obtenerNombreDetalle($pago->detalle);
                    
                    $detallesPagados[] = [
                        'cantidad' => $pago->cantidad_item_pagada,
                        'producto' => $nombreProducto,
                        'precio_unitario' => $pago->detalle->precio_unitario_momento,
                        'subtotal' => $pago->monto_pagado
                    ];
                }
            }
            $importeProductosPagados = array_sum(array_column($detallesPagados, 'subtotal'));
        }

        return view('view_mozo.mozo_pedido_vista_previa', compact(
            'comprobante',
            'comprobantesDivision',
            'detallesPagados',
            'importeProductosPagados'
        ));
    }


    // NUEVA FUNCIÓN HELPER PARA OBTENER NOMBRE DEL DETALLE
    private function obtenerNombreDetalle($detalle)
    {
        if ($detalle->tipo_producto === 'balde_personalizado') {
            return $detalle->nombre_producto_personalizado ?: 'Balde Personalizado';
        } elseif ($detalle->tipo_producto === 'balde_normal') {
            return $detalle->nombre_producto_personalizado ?: 'Balde Normal';
        } else {
            return $detalle->producto ? $detalle->producto->nombre : 'Producto no encontrado';
        }
    }

    private function generarCorrelativo($tipo, $serie)
    {
        $ultimoComprobante = comprobantes::where('tipo_comprobante', $tipo)
            ->where('serie_comprobante', $serie)
            ->orderBy('numero_correlativo_comprobante', 'desc')
            ->first();

        $ultimoNumero = $ultimoComprobante ? (int)$ultimoComprobante->numero_correlativo_comprobante : 0;

        return str_pad($ultimoNumero + 1, 8, '0', STR_PAD_LEFT);
    }
}
