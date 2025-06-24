@extends('view_layout.app')

@push('styles')
<link href="{{ asset('css/mozo_pedido_vista_previa.css') }}" rel="stylesheet" media="all">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
@endpush

@section('content')

<style>
/* Estilos para impresión */
@media print {
    /* Ocultar elementos no necesarios */
    .mozo-header,
    .btn,
    .card,
    .container-fluid > .row:last-child {
        display: none !important;
    }
    
    /* Resetear márgenes y padding del body */
    body {
        margin: 0 !important;
        padding: 0 !important;
        background: white !important;
    }
    
    /* Configurar el comprobante para impresión */
    #comprobante-contenido {
        position: static !important;
        margin: 0 !important;
        padding: 20px !important;
        width: 100% !important;
        max-width: none !important;
        background: white !important;
        border: none !important;
        box-shadow: none !important;
        font-size: 12px !important;
    }
    
    /* Asegurar que las tablas se impriman correctamente */
    table {
        width: 100% !important;
        border-collapse: collapse !important;
    }
    
    th, td {
        border: 1px solid #000 !important;
        padding: 5px !important;
        font-size: 11px !important;
    }
    
    /* Evitar saltos de página dentro de elementos importantes */
    .row, table, thead, tbody, tr {
        page-break-inside: avoid !important;
    }
    
    /* Forzar impresión en una sola página si es posible */
    html, body {
        height: auto !important;
    }
    
    /* Remover colores de fondo que podrían no imprimirse */
    * {
        background: transparent !important;
        color: black !important;
    }
    
    /* Mantener el encabezado de la empresa visible */
    .bg-white {
        background: white !important;
    }
    
    /* Estilo específico para el encabezado de la empresa */
    #comprobante-contenido .row:first-child .col-6:first-child div:first-child {
        background: #000 !important;
        color: white !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}

/* Estilos adicionales para mejorar la vista previa */
@media screen {
    #comprobante-contenido {
        font-family: 'Courier New', monospace;
    }
}
</style>

<div class="mozo-header">
    <a href="{{ route('vista.mozo_historial') }}" class="mozo-header-back">
        <span class="mozo-header-back-icon">&#8592;</span>
    </a>
    <div class="mozo-header-content">
        <div class="mozo-header-title">Vista Previa</div>
        <div class="mozo-header-subtitle">Comprobante</div>
    </div>
</div>

<div class="container-fluid p-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body p-4" style="background-color: #f8f9fa;">
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
                            <strong>{{ $comprobante->tipo_documento_cliente }}:</strong> {{ $comprobante->numero_documento_cliente }}
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
                                <p class="small">Son soles: {{ strtoupper('total en letras') }}</p>
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
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Botones de accion -->
    <div class="row justify-content-center mt-4">
        <div class="col-md-8">
            <div class="d-flex justify-content-between">
                <a href="{{ route('vista.mozo_historial') }}" class="btn btn-dark">
                    Volver al Historial
                </a>
                <button type="button" class="btn btn-secondary" onclick="window.print()">
                    Imprimir
                </button>
            </div>
        </div>
    </div>
</div>
@endsection