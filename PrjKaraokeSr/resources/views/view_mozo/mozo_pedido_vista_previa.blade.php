@extends('view_layout.app')

@section('content')
<style>
/* Estilos para impresión */
@media print {
    /* Ocultar todo excepto el comprobante */
    body * {
        visibility: hidden;
    }
    
    /* Mostrar solo el contenido del comprobante */
    #comprobante-contenido, #comprobante-contenido * {
        visibility: visible;
    }
    
    /* Posicionar el comprobante para impresión */
    #comprobante-contenido {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        background: white !important;
        box-shadow: none !important;
        border: none !important;
    }
    
    /* Ocultar elementos de navegación y botones */
    .navbar, .btn, .card-header, .container > .row:last-child {
        display: none !important;
    }
    
    /* Ajustar márgenes para impresión */
    @page {
        margin: 0.5in;
    }
    
    /* Asegurar que el texto sea negro */
    * {
        color: black !important;
        background: white !important;
    }
    
    /* Mantener bordes de tabla */
    .table, .table th, .table td {
        border: 1px solid black !important;
    }
}
</style>

<div class="container-fluid p-4">
    <!-- Vista previa del documento -->
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Vista previa - {{ strtoupper($comprobante->tipo_comprobante) }} {{ $comprobante->serie_comprobante }}-{{ $comprobante->numero_correlativo_comprobante }}</h5>
                </div>
                <div class="card-body p-4" style="background-color: #f8f9fa;">
                    <!-- Simulacion del PDF -->
                    <div id="comprobante-contenido" class="bg-white p-4 border" style="min-height: 600px;">
                        <!-- Encabezado -->
                        <div class="row mb-4">
                            <div class="col-6">
                                <div style="background: #333; color: white; padding: 10px; border-radius: 5px;">
                                    <h6 class="mb-1">CITYBAR PERU S.A.C</h6>
                                    <small>Karaoke</small>
                                </div>
                                <div class="mt-2">
                                    <small>RUC: 20123456789</small><br>
                                    <small>Dirección: Av. Principal 123, Lima</small>
                                </div>
                            </div>
                            <div class="col-6 text-end">
                                <div class="border p-2">
                                    <strong>{{ strtoupper($comprobante->tipo_comprobante) }} ELECTRÓNICA</strong><br>
                                    <strong>{{ $comprobante->serie_comprobante }}-{{ $comprobante->numero_correlativo_comprobante }}</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Datos del cliente -->
                        <div class="mb-3">
                            <div class="row">
                                <div class="col-6">
                                    <strong>Fecha:</strong> 
                                    @if($comprobante->fecha_emision instanceof \Carbon\Carbon)
                                        {{ $comprobante->fecha_emision->format('d/m/Y H:i') }}
                                    @else
                                        {{ \Carbon\Carbon::parse($comprobante->fecha_emision)->format('d/m/Y H:i') }}
                                    @endif
                                </div>
                                <div class="col-6 text-end">
                                    <strong>Mesa:</strong> {{ $comprobante->pedido->mesa->numero_mesa }}
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <strong>CLIENTE:</strong> {{ $comprobante->nombre_razon_social_cliente }}<br>
                            <strong>{{ $comprobante->tipo_documento_cliente }}:</strong> {{ $comprobante->numero_documento_cliente }}<br>
                            <strong>LIMA</strong>
                        </div>

                        <!-- Tabla de productos -->
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>CANT.</th>
                                    <th>DESCRIPCIÓN</th>
                                    <th>P. UNIT.</th>
                                    <th>IMPORTE</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($comprobante->pedido->detalles as $detalle)
                                <tr>
                                    <td>{{ $detalle->cantidad }}</td>
                                    <td>{{ $detalle->producto->nombre }}</td>
                                    <td>S/ {{ number_format($detalle->precio_unitario_momento, 2) }}</td>
                                    <td>S/ {{ number_format($detalle->subtotal, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <!-- Totales -->
                        <div class="row">
                            <div class="col-6">
                                @php
                                    // Función para convertir número a letras (alternativa a NumberFormatter)
                                    function numeroALetras($numero) {
                                        $entero = floor($numero);
                                        $decimales = round(($numero - $entero) * 100);
                                        
                                        $unidades = ['', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve'];
                                        $decenas = ['', '', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];
                                        $especiales = ['diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve'];
                                        $centenas = ['', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];
                                        
                                        if ($entero == 0) {
                                            return 'cero';
                                        }
                                        
                                        $resultado = '';
                                        
                                        // Simplificación para números menores a 1000
                                        if ($entero < 1000) {
                                            $c = floor($entero / 100);
                                            $d = floor(($entero % 100) / 10);
                                            $u = $entero % 10;
                                            
                                            if ($c > 0) {
                                                if ($c == 1 && ($d > 0 || $u > 0)) {
                                                    $resultado .= 'ciento ';
                                                } else {
                                                    $resultado .= $centenas[$c] . ' ';
                                                }
                                            }
                                            
                                            if ($d >= 2) {
                                                $resultado .= $decenas[$d];
                                                if ($u > 0) {
                                                    $resultado .= ' y ' . $unidades[$u];
                                                }
                                            } elseif ($d == 1) {
                                                $resultado .= $especiales[$u];
                                            } elseif ($u > 0) {
                                                $resultado .= $unidades[$u];
                                            }
                                        } else {
                                            // Para números mayores, usar una representación básica
                                            $resultado = number_format($entero, 0, '', ' ');
                                        }
                                        
                                        return trim($resultado);
                                    }
                                    
                                    $montoEnLetras = numeroALetras($comprobante->monto_total_comprobante);
                                    $decimales = sprintf('%02d', ($comprobante->monto_total_comprobante - floor($comprobante->monto_total_comprobante)) * 100);
                                @endphp
                                <p class="small">Son soles: {{ strtoupper($montoEnLetras) }} CON {{ $decimales }}/100 SOLES</p>
                            </div>
                            <div class="col-6">
                                <table class="table table-sm">
                                    <tr>
                                        <td>SUBTOTAL:</td>
                                        <td class="text-end">S/ {{ number_format($comprobante->subtotal_comprobante, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>IGV (18%):</td>
                                        <td class="text-end">S/ {{ number_format($comprobante->monto_igv, 2) }}</td>
                                    </tr>
                                    <tr class="fw-bold">
                                        <td>TOTAL:</td>
                                        <td class="text-end">S/ {{ number_format($comprobante->monto_total_comprobante, 2) }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Pie del documento -->
                        <div class="text-center mt-4">
                            <div style="width: 150px; height: 150px; border: 1px solid #000; margin: 0 auto;">
                                <!-- QR Code placeholder -->
                                <div class="d-flex align-items-center justify-content-center h-100">
                                    <span>QR CODE</span>
                                </div>
                            </div>
                            <small class="d-block mt-2">
                                Para verificar la validez del comprobante ingrese a<br>
                                http://comprobantepdf.perudata.pe/verificacionelectronica/formulario.aspx
                            </small>
                            <small class="d-block mt-2">
                                Emitido por Zona Codigo WEB
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Botones de accion -->
    <div class="row justify-content-center mt-4">
        <div class="col-md-8">
            <div class="d-flex justify-content-between">
                <button type="button" class="btn btn-dark" id="btnEditar">
                    Volver al Historial
                </button>
                <button type="button" class="btn btn-secondary" id="btnImprimir">
                    Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Manejar el boton de editar
document.getElementById('btnEditar').addEventListener('click', function() {
    window.location.href = '{{ route("vista.mozo_historial") }}';
});

// Manejar el boton de imprimir
document.getElementById('btnImprimir').addEventListener('click', function() {
    window.print();
});
</script>
@endsection
