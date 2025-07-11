@extends('view_layout.app')

@push('styles')
<link href="{{ asset('css/mozo_ver_pedido.css') }}" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
@endpush

@section('content')
<div class="mozo-header">
    <a href="{{ route('vista.mozo_historial') }}" class="mozo-header-back">
        <span class="mozo-header-back-icon">&#8592;</span>
    </a>
    <div class="mozo-header-content">
        <div class="mozo-header-title">Ver Pedido</div>
        <div class="mozo-header-subtitle">Mesa {{ $pedido->mesa->numero_mesa }}</div>
    </div>
</div>

<div class="container mt-4 mb-5 pb-5">
    <div class="card mb-4">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Detalles del Pedido</h5>
            @php
              $tienePagos = $pedido->comprobante !== null;
              $estadoMostrar = $tienePagos ? 'PAGADO' : $pedido->estado_pedido;
            @endphp
            <span class="badge bg-{{ $estadoMostrar == 'PENDIENTE' ? 'warning' : ($estadoMostrar == 'PAGADO' ? 'success' : 'secondary') }}">
                {{ $estadoMostrar }}
            </span>
        </div>
        <div class="card-body">
            <!-- Informacion del pedido -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <p><strong>Mesa:</strong> {{ $pedido->mesa->numero_mesa }}</p>
                    <p><strong>Mesero:</strong> {{ $pedido->mesero->nombres ?? 'N/A' }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Fecha:</strong> 
                        @if($pedido->fecha_hora_pedido instanceof \Carbon\Carbon)
                            {{ $pedido->fecha_hora_pedido->format('d/m/Y H:i') }}
                        @else
                            {{ \Carbon\Carbon::parse($pedido->fecha_hora_pedido)->format('d/m/Y H:i') }}
                        @endif
                    </p>
                    <p><strong>Total:</strong> S/ {{ number_format($pedido->total_pedido, 2) }}</p>
                </div>
            </div>

            @if($pedido->notas_adicionales)
                <div class="alert alert-info">
                    <strong>Notas:</strong> {{ $pedido->notas_adicionales }}
                </div>
            @endif

            <!-- Productos del pedido -->
            <h6 class="mb-3">Productos Pedidos:</h6>
            @foreach($pedido->detalles as $detalle)
                <div class="d-flex justify-content-between align-items-center mb-3 p-2 border-bottom">
                    <div class="flex-grow-1">
                        {{-- NOMBRE DEL PRODUCTO - CORREGIDO PARA BALDES --}}
                        <h6 class="mb-1">
                            @if($detalle->tipo_producto === 'balde_personalizado')
                                {{ $detalle->nombre_producto_personalizado }}
                                @if($detalle->configuracion_especial)
                                    <br><small class="text-muted">
                                        @php
                                            $config_detalles = [];
                                            foreach($detalle->configuracion_especial as $cerveza_id => $config) {
                                                $cerveza = \App\Models\productos::find($cerveza_id);
                                                if($cerveza) {
                                                    $config_detalles[] = $config['cantidad'] . 'x ' . $cerveza->nombre;
                                                }
                                            }
                                        @endphp
                                        ({{ implode(', ', $config_detalles) }})
                                    </small>
                                @endif
                            @elseif($detalle->tipo_producto === 'balde_normal')
                                {{ $detalle->nombre_producto_personalizado }}
                                @if($detalle->producto_base)
                                    <br><small class="text-muted">(6x {{ $detalle->producto_base->nombre }})</small>
                                @endif
                            @else
                                {{ $detalle->producto->nombre ?? 'Producto no encontrado' }}
                            @endif
                        </h6>
                        <div class="d-flex gap-3">
                            <small class="text-muted">Cantidad: {{ $detalle->cantidad }}</small>
                            <small class="text-muted">Precio unit: S/ {{ number_format($detalle->precio_unitario_momento, 2) }}</small>
                            <span class="badge bg-{{ $detalle->estado_item == 'SOLICITADO' ? 'warning' : ($detalle->estado_item == 'LISTO_PARA_ENTREGA' ? 'success' : 'info') }}">
                                {{ $detalle->estado_item }}
                            </span>
                        </div>
                    </div>
                    <div class="text-end">
                        <strong>S/ {{ number_format($detalle->subtotal, 2) }}</strong>
                    </div>
                </div>
            @endforeach

            <!-- Mostrar estado general del pedido -->
            @php
                $todosListos = $pedido->detalles->every(function($detalle) {
                    return $detalle->estado_item === 'LISTO_PARA_ENTREGA';
                });
                $algunosListos = $pedido->detalles->some(function($detalle) {
                    return $detalle->estado_item === 'LISTO_PARA_ENTREGA';
                });
            @endphp
            
            @if($estadoMostrar === 'PENDIENTE')
                <div class="alert alert-{{ $todosListos ? 'success' : ($algunosListos ? 'warning' : 'info') }} mt-3">
                    @if($todosListos)
                        <i class="bi bi-check-circle-fill"></i> <strong>Pedido listo para facturar</strong> - Todos los productos están preparados
                    @elseif($algunosListos)
                        <i class="bi bi-clock-fill"></i> <strong>Pedido en preparación</strong> - Algunos productos están listos
                    @else
                        <i class="bi bi-hourglass-split"></i> <strong>Pedido enviado a cocina/bar</strong> - Los productos están siendo preparados
                    @endif
                </div>
            @endif

            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                <h5 class="mb-0">Total del Pedido:</h5>
                <h5 class="mb-0 text-success">S/ {{ number_format($pedido->total_pedido, 2) }}</h5>
            </div>
        </div>
    </div>
</div>
@endsection
