@extends('view_layout.app')

@section('content')
<x-app-header backUrl="{{ route('vista.mozo_historial') }}" title="Editar Pedido" />

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

        <!-- Productos por Categoria -->
        @foreach($productosPorCategoria as $categoriaData)
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0">{{ $categoriaData['categoria']->nombre }}</h6>
                </div>
                <div class="card-body">
                    @foreach($categoriaData['productos'] as $detalle)
                        <div class="d-flex justify-content-between align-items-center mb-3 p-3 border rounded" id="producto-{{ $detalle->id_pedido_detalle }}">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">{{ $detalle->producto->nombre }}</h6>
                                <small class="text-muted">Precio: S/ {{ number_format($detalle->precio_unitario_momento, 2) }}</small>
                            </div>
                            <div class="d-flex align-items-center gap-3">
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
                                           style="width: 60px;" 
                                           min="1"
                                           max="{{ $detalle->cantidad + $detalle->producto->stock }}"
                                           data-max="{{ $detalle->cantidad + $detalle->producto->stock }}">
                                    <button type="button" 
                                            class="btn btn-outline-secondary btn-sm btn-sumar" 
                                            data-detalle-id="{{ $detalle->id_pedido_detalle }}"
                                            data-max="{{ $detalle->cantidad + $detalle->producto->stock }}">+</button>
                                </div>
                                
                                <input type="hidden" name="productos[{{ $detalle->id_pedido_detalle }}][accion]" value="modificar">
                                
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

        <!-- Footer -->
        <x-app-footer 
            tipo="agregar-confirmar" 
            backUrl="{{ route('pedidos.agregar', $pedido->id_pedido) }}" 
        />
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Botones de restar cantidad
    document.querySelectorAll('.btn-restar').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const detalleId = this.getAttribute('data-detalle-id');
            const input = document.getElementById('cantidad-' + detalleId);
            if (input && parseInt(input.value) > 1) {
                input.value = parseInt(input.value) - 1;
            }
        });
    });

    // Botones de sumar cantidad
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
