@extends('view_layout.app')

@push('styles')
<link href="{{ asset('css/admin_facturacion_vista_previa.css') }}" rel="stylesheet" media="all">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
@endpush

@section('content')

@php
$haySlide = isset($comprobantesDivision) && $comprobantesDivision->count() > 1;
@endphp

<div class="admin-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1>Comprobante de Pago</h1>
                <small>{{ $comprobante->tipo_comprobante }} {{ $comprobante->serie_comprobante }}-{{ $comprobante->numero_correlativo_comprobante }}</small>
            </div>
            
        </div>
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
                                <div class="empresa-header">
                                    <h6 class="mb-1 text-black">Restobar Salón Rojo</h6>
                                    <small class="text-black">Karaoke</small>
                                </div>
                                <div class="mt-2">
                                    <small>RUC: 10255667781</small><br>
                                    <small>Dirección: Gral Deustua 160, Tacna 23001</small>
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

                        <!-- Información del mesero -->
                        @if($comprobante->pedido->mesero)
                        <div class="mb-3">
                            <strong>MESERO:</strong> {{ $comprobante->pedido->mesero->nombres }} 
                            ({{ $comprobante->pedido->mesero->codigo_usuario }})
                        </div>
                        @endif

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
                                {{-- Si hay división de cuenta, se muestran SOLO los productos pagados por este comprobante --}}
                                @if($comprobante->pagosDetalle && $comprobante->pagosDetalle->count() > 0)
                                    @foreach($comprobante->pagosDetalle as $pago)
                                        @if($pago->detalle && $pago->detalle->producto)
                                        <tr>
                                            <td>{{ $pago->cantidad_item_pagada }}</td>
                                            <td>{{ $pago->detalle->producto->nombre }}</td>
                                            <td>S/ {{ number_format($pago->detalle->precio_unitario_momento, 2) }}</td>
                                            <td>S/ {{ number_format($pago->monto_pagado, 2) }}</td>
                                        </tr>
                                        @endif
                                    @endforeach
                                {{-- Si NO hay división de cuenta, se muestran todos los productos del pedido --}}
                                @else
                                    @foreach($comprobante->pedido->detalles as $detalle)
                                    <tr>
                                        <td>{{ $detalle->cantidad }}</td>
                                        <td>{{ $detalle->producto->nombre }}</td>
                                        <td>S/ {{ number_format($detalle->precio_unitario_momento, 2) }}</td>
                                        <td>S/ {{ number_format($detalle->subtotal, 2) }}</td>
                                    </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>

                        <!-- Totales -->
                        <div class="row">
                            <div class="col-6">
                                
                                
                                <!-- Información de pago -->
                                <div class="mt-3">
                                    <strong>MÉTODO DE PAGO:</strong><br>
                                    {{ strtoupper($comprobante->metodo_pago) }}
                                    @if($comprobante->referencia_pago)
                                        <br><small>Ref: {{ $comprobante->referencia_pago }}</small>
                                    @endif
                                </div>
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

                        <!-- Footer con información adicional -->
                        <div class="row mt-4">
                            <div class="col-6">
                                <small>
                                    Estado: {{ strtoupper($comprobante->estado_comprobante) }}<br>
                                    @if($comprobante->fecha_anulacion)
                                        Fecha Anulación: {{ \Carbon\Carbon::parse($comprobante->fecha_anulacion)->format('d/m/Y H:i') }}
                                    @endif
                                </small>
                            </div>
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Botones de acción -->
    <div class="admin-actions">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="d-flex justify-content-between">
                    <a href="{{ route('admin.detalle_pedido', $comprobante->pedido->fecha_hora_pedido->format('Y-m-d')) }}" 
                    class="btn btn-light">
                        <i class="fas fa-arrow-left"></i> Volver al Detalle
                    </a>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary" onclick="window.print()">
                            <i class="fas fa-print"></i> Imprimir
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($haySlide)
    <style>
        .slide-division {
            position: fixed;
            top: 0; right: 0;
            width: 340px;
            height: 100vh;
            background: #fff;
            border-left: 2px solid #eee;
            box-shadow: -2px 0 8px rgba(0,0,0,0.07);
            z-index: 1050;
            padding: 24px 18px 18px 18px;
            overflow-y: auto;
            transition: transform 0.3s;
        }
        .slide-division.hide {
            transform: translateX(100%);
        }
        @media (max-width: 900px) {
            .slide-division { position: fixed; width: 100%; height: 60vh; bottom: 0; top: auto; border-left: none; border-top: 2px solid #eee; box-shadow: 0 -2px 8px rgba(0,0,0,0.07); }
        }
    </style>
    <div id="slideDivision" class="slide-division hide">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Comprobantes</h5>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('slideDivision').classList.add('hide')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="d-grid gap-3">
            @foreach($comprobantesDivision as $c)
                @php
                    $isActive = $c->id_comprobante == $comprobante->id_comprobante;
                    $vistaPreviaUrl = route('admin.ver_comprobante', $c->id_comprobante);
                @endphp
                <a href="{{ $vistaPreviaUrl }}" class="btn btn-block {{ $isActive ? 'btn-primary' : 'btn-outline-primary' }}">
                    <div class="fw-bold">{{ strtoupper($c->tipo_comprobante) }}</div>
                    <div class="small">{{ $c->nombre_razon_social_cliente ?? $c->numero_documento_cliente }}</div>
                </a>
            @endforeach
        </div>
    </div>
    <button id="btnToggleSlide" class="btn btn-primary rounded-circle shadow" style="position:fixed;bottom:32px;right:32px;z-index:1100;width:60px;height:60px;display:flex;align-items:center;justify-content:center;">
        <i class="fas fa-receipt fa-lg"></i>
    </button>
@endif

<script>
// Mejorar la impresión
window.addEventListener('beforeprint', function() {
    document.title = 'Comprobante {{ $comprobante->serie_comprobante }}-{{ $comprobante->numero_correlativo_comprobante }}';
});
</script>
@endsection

@if($haySlide)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnToggleSlide = document.getElementById('btnToggleSlide');
    const slideDivision = document.getElementById('slideDivision');
    if(btnToggleSlide && slideDivision) {
        btnToggleSlide.addEventListener('click', function() {
            slideDivision.classList.toggle('hide');
        });
    }
});
</script>
@endpush
@endif