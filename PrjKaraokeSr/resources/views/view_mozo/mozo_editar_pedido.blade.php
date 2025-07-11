@extends('view_layout.app')

@push('styles')
<link href="{{ asset('css/mozo_editar_pedido.css') }}" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
@endpush

@section('content')
<div class="mozo-header">
    <a href="{{ route('vista.mozo_historial') }}" class="mozo-header-back">
        <span class="mozo-header-back-icon">&#8592;</span>
    </a>
    <div class="mozo-header-content">
        <div class="mozo-header-title">Editar</div>
        <div class="mozo-header-subtitle">Pedido</div>
    </div>
</div>

<div class="container mt-4 mb-5 pb-5">
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form action="{{ route('pedidos.actualizar', $pedido->id_pedido) }}" method="POST">
        @csrf
        @method('PUT')

        <!-- Seleccion de Mesa -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">Seleccionar Mesa</h6>
            </div>
            <div class="card-body">
                <select class="form-select" name="id_mesa" required>
                    @foreach($mesas as $mesa)
                        <option value="{{ $mesa->id_mesa }}" {{ $mesa->id_mesa == $pedido->id_mesa ? 'selected' : '' }}>
                            Mesa {{ $mesa->numero_mesa }}
                        </option>
                    @endforeach
                </select>
                <small class="form-text text-muted">Solo se muestran las mesas disponibles y la mesa actual</small>
            </div>
        </div>

        <!-- Productos Editables por Categoria -->
        @if(!empty($productosPorCategoria))
            <div class="alert alert-info">
                <strong>Productos Editables:</strong> Solo puedes editar productos que no han sido marcados como listos para entrega.
            </div>
            
            @foreach($productosPorCategoria as $categoriaData)
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0">{{ $categoriaData['categoria']->nombre }} (Editables)</h6>
                    </div>
                    <div class="card-body">
                        @foreach($categoriaData['productos'] as $detalle)
                            <div class="d-flex justify-content-between align-items-center mb-3 p-3 border rounded" id="producto-{{ $detalle->id_pedido_detalle }}">
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

                                    {{-- PRECIO - SOLO PARA PRODUCTOS NORMALES CON PROMOCIONES --}}
                                    @if($detalle->tipo_producto === 'normal' && isset($detalle->producto->en_promocion) && $detalle->producto->en_promocion)
                                        <div class="promo-precio-container" style="display: flex; flex-direction: column; align-items: flex-start;">
                                            <span class="original-price" style="color: #999; text-decoration: line-through; font-size: 1.1em;">
                                                S/ {{ number_format($detalle->producto->precio_original, 2) }}
                                            </span>
                                            <span class="promo-price" style="color: #c4361d; background: #fffbe8; font-weight: bold; font-size: 1.5em; padding: 2px 8px; border-radius: 6px;">
                                                S/ {{ number_format($detalle->producto->precio_promocion, 2) }}
                                            </span>
                                        </div>
                                    @else
                                        <span class="price" style="font-weight: bold; font-size: 1.5em; color: #c4361d;">
                                            S/ {{ number_format($detalle->precio_unitario_momento, 2) }}
                                        </span>
                                    @endif
                                    <span class="badge bg-warning ms-2">{{ $detalle->estado_item }}</span>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    {{-- CONTROLES DE CANTIDAD - SOLO PARA PRODUCTOS NORMALES --}}
                                    @if($detalle->tipo_producto === 'normal')
                                        <!-- Control de cantidad -->
                                        <div class="d-flex align-items-center">
                                            <button type="button" 
                                                    class="btn btn-outline-secondary btn-sm btn-restar" 
                                                    data-detalle-id="{{ $detalle->id_pedido_detalle }}">-</button>
                                            <input type="number" 
                                                   name="productos[{{ $detalle->id_pedido_detalle }}][cantidad]" 
                                                   id="cantidad-{{ $detalle->id_pedido_detalle }}"
                                                   value="{{ $detalle->cantidad }}" 
                                                   class="form-control mx-2 text-center" 
                                                   style="width: 60px; color: #000;" 
                                                   min="1"
                                                   max="{{ $detalle->cantidad + $detalle->producto->stock }}"
                                                   data-max="{{ $detalle->cantidad + $detalle->producto->stock }}">
                                            <button type="button" 
                                                    class="btn btn-outline-secondary btn-sm btn-sumar" 
                                                    data-detalle-id="{{ $detalle->id_pedido_detalle }}"
                                                    data-max="{{ $detalle->cantidad + $detalle->producto->stock }}">+</button>
                                        </div>
                                        
                                        <input type="hidden" name="productos[{{ $detalle->id_pedido_detalle }}][accion]" value="modificar">
                                    @else
                                        {{-- PARA BALDES - SOLO MOSTRAR CANTIDAD SIN EDITAR --}}
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-primary">Cantidad: {{ $detalle->cantidad }}</span>
                                        </div>
                                        <span class="text-muted">No editable</span>
                                    @endif
                                    
                                    <!-- Boton eliminar -->
                                    <button type="button" 
                                            class="btn btn-danger btn-sm btn-eliminar" 
                                            data-detalle-id="{{ $detalle->id_pedido_detalle }}">
                                        Eliminar
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif

        <!-- Productos No Editables (Listos para Entrega) -->
        @if(!empty($productosNoEditablesPorCategoria))
            <div class="alert alert-warning">
                <strong>Productos Listos para Entrega:</strong> Estos productos ya están preparados y no pueden ser modificados.
            </div>
            
            @foreach($productosNoEditablesPorCategoria as $categoriaData)
                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0">{{ $categoriaData['categoria']->nombre }} (Listos para Entrega)</h6>
                    </div>
                    <div class="card-body">
                        @foreach($categoriaData['productos'] as $detalle)
                            <div class="d-flex justify-content-between align-items-center mb-3 p-3 border rounded bg-light">
                                <div class="flex-grow-1">
                                    {{-- NOMBRE DEL PRODUCTO - CORREGIDO PARA BALDES --}}
                                    <h6 class="mb-1">
                                        @if($detalle->tipo_producto === 'balde_personalizado')
                                            {{ $detalle->nombre_producto_personalizado }}
                                        @elseif($detalle->tipo_producto === 'balde_normal')
                                            {{ $detalle->nombre_producto_personalizado }}
                                        @else
                                            {{ $detalle->producto->nombre ?? 'Producto no encontrado' }}
                                        @endif
                                    </h6>
                                    <small class="text-muted">Precio: S/ {{ number_format($detalle->precio_unitario_momento, 2) }}</small>
                                    <span class="badge bg-success ms-2">{{ $detalle->estado_item }}</span>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <!-- Cantidad solo lectura -->
                                    <div class="d-flex align-items-center">
                                        <span class="badge bg-primary">Cantidad: {{ $detalle->cantidad }}</span>
                                    </div>
                                    <span class="text-muted">No editable</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        @endif

        @if(empty($productosPorCategoria) && empty($productosNoEditablesPorCategoria))
            <div class="alert alert-info text-center">
                Este pedido no tiene productos para mostrar.
            </div>
        @endif

        <!-- Footer -->
        <x-app-footer 
            tipo="agregar-confirmar" 
            backUrl="{{ route('pedidos.agregar', $pedido->id_pedido) }}" 
        />
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Botones de restar cantidad - SOLO PARA PRODUCTOS NORMALES
    document.querySelectorAll('.btn-restar').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const detalleId = this.getAttribute('data-detalle-id');
            const input = document.getElementById('cantidad-' + detalleId);
            if (input && parseInt(input.value) > 1) {
                input.value = parseInt(input.value) - 1;
            }
        });
    });

    // Botones de sumar cantidad - SOLO PARA PRODUCTOS NORMALES
    document.querySelectorAll('.btn-sumar').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const detalleId = this.getAttribute('data-detalle-id');
            const maxCantidad = parseInt(this.getAttribute('data-max'));
            const input = document.getElementById('cantidad-' + detalleId);
            if (input && parseInt(input.value) < maxCantidad) {
                input.value = parseInt(input.value) + 1;
            }
        });
    });

    // Botones de eliminar
    document.querySelectorAll('.btn-eliminar').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const detalleId = this.getAttribute('data-detalle-id');
            if (confirm('Esta seguro de eliminar este producto del pedido?')) {
                // Cambiar la accion a eliminar
                const accionInput = document.querySelector('input[name="productos[' + detalleId + '][accion]"]');
                if (accionInput) {
                    accionInput.value = 'eliminar';
                } else {
                    // Para baldes, crear el input hidden
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'productos[' + detalleId + '][accion]';
                    hiddenInput.value = 'eliminar';
                    document.querySelector('form').appendChild(hiddenInput);
                }
                
                // Ocultar el elemento visualmente
                const elemento = document.getElementById('producto-' + detalleId);
                if (elemento) {
                    elemento.style.opacity = '0.5';
                    elemento.style.pointerEvents = 'none';
                }
            }
        });
    });

    // Funcionalidad de los botones del footer
    const agregarUrl = "{{ route('pedidos.agregar', $pedido->id_pedido) }}";
    
    document.querySelectorAll('button').forEach(function(button) {
        if (button.textContent.trim() === 'Agregar') {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                window.location.href = agregarUrl;
            });
        }
    });

    // Boton Confirmar - mostrar popup de confirmacion
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!confirm('Esta seguro de guardar los cambios realizados en el pedido?')) {
                e.preventDefault();
            }
        });
    }
});
</script>
@endsection
