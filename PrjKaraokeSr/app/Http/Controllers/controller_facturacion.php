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

class controller_facturacion extends Controller
{
    public function finalizar_pedido(Request $request, $idPedido)
    {
        $pedido = pedidos::with(['detalles.producto'])->findOrFail($idPedido);
        
        // VALIDAR QUE TODOS LOS PRODUCTOS ESTÉN LISTOS PARA ENTREGA
        $productosNoListos = $pedido->detalles->where('estado_item', '!=', 'LISTO_PARA_ENTREGA');
        
        if ($productosNoListos->count() > 0) {
            $nombresProductos = $productosNoListos->pluck('producto.nombre')->join(', ');
            return redirect()->route('vista.mozo_historial')
                ->with('error', "No se puede finalizar el pedido. Los siguientes productos aún no están listos: {$nombresProductos}");
        }
        
        // Si todos están listos, redirigir a facturación
        return redirect()->route('pedidos.facturar', $idPedido);
    }

    public function mostrar_facturacion($idPedido)
    {
        $pedido = pedidos::with(['mesa', 'detalles.producto'])->findOrFail($idPedido);
        
        // VERIFICAR QUE TODOS LOS PRODUCTOS ESTÉN LISTOS PARA ENTREGA
        $productosNoListos = $pedido->detalles->where('estado_item', '!=', 'LISTO_PARA_ENTREGA');
        
        if ($productosNoListos->count() > 0) {
            $nombresProductos = $productosNoListos->pluck('producto.nombre')->join(', ');
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
        
        return view('view_mozo.mozo_pedido_facturacion', compact('pedido'));
    }

    public function procesar_facturacion(Request $request, $idPedido)
    {
        $pedido = pedidos::with('detalles')->findOrFail($idPedido);
        
        // VALIDAR QUE TODOS LOS PRODUCTOS ESTÉN LISTOS PARA ENTREGA
        $productosNoListos = $pedido->detalles->where('estado_item', '!=', 'LISTO_PARA_ENTREGA');
        
        if ($productosNoListos->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede procesar el pago. Algunos productos aún no están listos para entrega.'
            ], 400);
        }
        
        // VALIDAR QUE NO TENGA YA UN COMPROBANTE (verificación directa más confiable)
        $comprobanteExistente = comprobantes::where('id_pedido', $idPedido)->first();
        if ($comprobanteExistente) {
            return response()->json([
                'success' => false,
                'message' => 'Este pedido ya tiene un comprobante emitido.',
                'comprobante_id' => $comprobanteExistente->id_comprobante
            ], 400);
        }
        
        // VALIDAR QUE EL PEDIDO ESTE EN ESTADO PENDIENTE
        if ($pedido->estado_pedido !== 'PENDIENTE') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden facturar pedidos en estado PENDIENTE.'
            ], 400);
        }
        
        $request->validate([
            'tipo_comprobante' => 'required|in:factura,boleta',
            'documento' => 'required|string',
            'metodo_pago' => 'required|array',
            'monto_pago' => 'required|array',
        ]);

        // VALIDAR QUE LOS MONTOS DE PAGO COINCIDAN CON EL TOTAL
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

            // Crear el comprobante con TODOS los campos requeridos
            $comprobante = comprobantes::create([
                'id_pedido' => $pedido->id_pedido,
                'id_usuario_cajero' => Auth::id(),
                'tipo_documento_cliente' => $request->tipo_comprobante === 'factura' ? 'RUC' : 'DNI',
                'numero_documento_cliente' => $request->documento,
                'nombre_razon_social_cliente' => 'Cliente',
                'direccion_cliente' => null,
                'serie_comprobante' => $request->tipo_comprobante === 'factura' ? 'F001' : 'B001',
                'numero_correlativo_comprobante' => $this->generarCorrelativo($request->tipo_comprobante),
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

    public function enviar_correo_form(Request $request, $idComprobante)
    {
        $comprobante = comprobantes::findOrFail($idComprobante);
        
        return response()->json([
            'success' => true,
            'html' => view('modals.enviar_correo', compact('comprobante'))->render()
        ]);
    }

    public function enviar_correo(Request $request, $idComprobante)
    {
        $comprobante = comprobantes::findOrFail($idComprobante);
        
        $request->validate([
            'dni_correo' => 'required|string',
            'email' => 'required|email',
        ]);

        // Aquí implementarías el envío real del correo
        // Por ahora simularemos el proceso
        
        return response()->json([
            'success' => true,
            'message' => 'Correo enviado exitosamente',
            'comprobante_id' => $comprobante->id_comprobante
        ]);
    }

    public function vista_previa_pdf($idComprobante)
    {
        $comprobante = comprobantes::with(['pedido.detalles.producto', 'pedido.mesa'])->findOrFail($idComprobante);
        
        return view('view_mozo.mozo_pedido_vista_previa', compact('comprobante'));
    }

    private function generarCorrelativo($tipo)
    {
        $ultimoComprobante = comprobantes::where('tipo_comprobante', $tipo)
            ->orderBy('numero_correlativo_comprobante', 'desc')
            ->first();
        
        $ultimoNumero = $ultimoComprobante ? (int)$ultimoComprobante->numero_correlativo_comprobante : 0;
        
        return str_pad($ultimoNumero + 1, 8, '0', STR_PAD_LEFT);
    }
}
