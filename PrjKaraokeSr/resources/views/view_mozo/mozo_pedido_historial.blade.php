@extends('view_layout.app')

@push('styles')
<link href="{{ asset('css/mozo_pedido_historial.css') }}" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
@endpush

@section('content')
<div class="mozo-header">
    <a href="{{ route('vista.mozo_pedido_mesa', ['mesa' => session('mesa_temp', 1)]) }}" class="mozo-header-back">
        <span class="mozo-header-back-icon">&#8592;</span>
    </a>
    <div class="mozo-header-content">
        <div class="mozo-header-title">Resumen</div>
        <div class="mozo-header-subtitle">Pedido</div>
    </div>
</div>

<div class="container mt-4 mb-5 pb-5">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form action="{{ route('vista.confirmar_mozo_pedido') }}" method="POST">
        @csrf
        
        <div class="card mb-4">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    @if(session('agregando_a_pedido'))
                        Productos a Agregar al Pedido
                    @else
                        Pedidos Seleccionados
                    @endif
                </h5>
                <span class="badge bg-light text-dark">Mesa {{ session('mesa_temp') }}</span>
            </div>
            <div class="card-body">
                @if(!empty($pedidosTemp))
                    @if(session('agregando_a_pedido'))
                        <div class="alert alert-info">
                            Se agregaran los siguientes productos al pedido existente:
                        </div>
                    @endif
                    
                    @php $totalGeneral = 0; @endphp
                    @foreach($pedidosTemp as $producto)
                        <div class="d-flex justify-content-between align-items-center mb-3 p-2 border-bottom">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">{{ $producto['nombre'] }}</h6>
                                <div class="d-flex gap-3">
                                    <small class="text-muted">
                                        Cantidad: {{ $producto['cantidad'] }}
                                        @if(session('agregando_a_pedido'))
                                            <span class="text-success">(adicional)</span>
                                        @endif
                                    </small>
                                    <small class="text-muted">Precio unit: S/ {{ number_format($producto['precio'], 2) }}</small>
                                    @if(isset($producto['area_destino']))
                                        <span class="badge bg-{{ $producto['area_destino'] == 'cocina' ? 'success' : ($producto['area_destino'] == 'bar' ? 'info' : 'warning') }}">
                                            {{ ucfirst($producto['area_destino']) }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-end">
                                <strong>S/ {{ number_format($producto['subtotal'], 2) }}</strong>
                            </div>
                        </div>
                        @php $totalGeneral += $producto['subtotal']; @endphp
                    @endforeach
                    
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <h5 class="mb-0">
                            @if(session('agregando_a_pedido'))
                                Total Adicional:
                            @else
                                Total del Pedido:
                            @endif
                        </h5>
                        <h5 class="mb-0 text-success">S/ {{ number_format($totalGeneral, 2) }}</h5>
                    </div>
                @else
                    <p class="text-center">No hay productos seleccionados</p>
                @endif
            </div>
        </div>

        @if(!empty($pedidosTemp))
            <!-- Notas adicionales -->
            @if(!session('agregando_a_pedido'))
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">Notas Adicionales (Opcional)</h6>
                    </div>
                    <div class="card-body">
                        <textarea 
                            name="notas_adicionales" 
                            class="form-control" 
                            rows="3" 
                            placeholder="Agregar notas especiales para el pedido..."
                        ></textarea>
                    </div>
                </div>
            @endif

            <!-- Footer de confirmacion -->
            <x-app-footer 
                tipo="regresar-confirmar" 
                :backUrl="route('vista.mozo_pedido_mesa', ['mesa' => session('mesa_temp', 1)])" 
            />
        @else
            <div class="text-center">
                <a href="{{ route('vista.mozo_mesa') }}" class="btn btn-dark">Volver a Mesas</a>
            </div>
        @endif
    </form>
</div>
@endsection
