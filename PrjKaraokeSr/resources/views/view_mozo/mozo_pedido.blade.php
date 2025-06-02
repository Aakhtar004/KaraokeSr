@extends('view_layout.app')

@section('content')
<x-app-header 
    backUrl="{{ isset($editando) && $editando ? route('pedidos.editar', session('editando_pedido')) : route('vista.mozo_mesa') }}" 
    title="{{ isset($editando) && $editando ? 'Agregar Productos' : 'Pedido' }} - Mesa {{ $mesa->numero_mesa }}" 
/>

<div class="container mt-4 mb-5 pb-5">
    @if(isset($editando) && $editando)
        <div class="alert alert-info">
            Esta agregando productos al pedido existente
        </div>
    @endif

    <form id="pedidoForm" action="{{ route('vista.procesar_mozo_pedido') }}" method="POST">
        @csrf
        <input type="hidden" name="id_mesa" value="{{ $mesa->id_mesa }}">
        <div class="accordion mt-4" id="accordionCategorias">
            @foreach($categorias_producto as $categoria)
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading{{ $categoria->id_categoria_producto }}">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $categoria->id_categoria_producto }}" aria-expanded="false" aria-controls="collapse{{ $categoria->id_categoria_producto }}">
                        {{ $categoria->nombre }}
                    </button>
                </h2>
                <div id="collapse{{ $categoria->id_categoria_producto }}" class="accordion-collapse collapse" aria-labelledby="heading{{ $categoria->id_categoria_producto }}" data-bs-parent="#accordionCategorias">
                    <div class="accordion-body">
                        @php
                            $productosCategoria = $productos->where('id_categoria_producto', $categoria->id_categoria_producto);
                        @endphp
                        @if($productosCategoria->isEmpty())
                            <p>No hay productos en esta categoria.</p>
                        @else
                            <div class="row">
                                @foreach($productosCategoria as $producto)
                                    @php
                                        $cantidadYaPedida = isset($productosYaPedidos) ? ($productosYaPedidos[$producto->id_producto] ?? 0) : 0;
                                        $stockDisponible = $producto->stock + $cantidadYaPedida;
                                    @endphp
                                    <div class="col-md-4 mb-4">
                                        <div class="card h-100 position-relative p-2" style="border-radius: 20px; background: #fffbe9;" data-stock="{{ $stockDisponible }}">
                                            <!-- Checkbox en la esquina superior derecha -->
                                            <input type="checkbox" 
                                                   name="productos[{{ $producto->id_producto }}][seleccionado]" 
                                                   value="1" 
                                                   class="form-check-input position-absolute top-0 end-0 m-2 producto-checkbox" 
                                                   style="z-index:2;" 
                                                   {{ $stockDisponible == 0 ? 'disabled' : '' }}
                                                   {{ $cantidadYaPedida > 0 ? 'checked' : '' }}>
                                            <div class="card-body d-flex flex-column align-items-center">
                                                @if($producto->imagen_url)
                                                    <img src="{{ $producto->imagen_url }}" class="card-img-top mb-2" alt="{{ $producto->nombre }}" style="max-height:90px;object-fit:contain;">
                                                @endif
                                                <h5 class="card-title text-center">{{ $producto->nombre }}</h5>
                                                @if($stockDisponible == 0)
                                                    <div class="alert alert-danger p-1 text-center mb-2" style="font-size:0.9rem;">Producto faltante</div>
                                                @endif
                                                @if($cantidadYaPedida > 0)
                                                    <div class="alert alert-warning p-1 text-center mb-2" style="font-size:0.8rem;">Ya pedido: {{ $cantidadYaPedida }}</div>
                                                @endif
                                                <div class="d-flex align-items-center justify-content-center mt-2" style="background:#e6bdbd; border-radius: 20px; width:90%;">
                                                    <button type="button" class="btn btn-link text-dark px-2 py-0 btn-restar" data-id="{{ $producto->id_producto }}" style="font-size:1.5rem;" {{ $stockDisponible == 0 ? 'disabled' : '' }}>-</button>
                                                    <input type="text" readonly name="productos[{{ $producto->id_producto }}][cantidad]" id="cantidad-{{ $producto->id_producto }}" value="{{ $cantidadYaPedida > 0 ? $cantidadYaPedida : 1 }}" class="text-center border-0 bg-transparent fw-bold" style="width: 30px; font-size:1.3rem;">
                                                    <button type="button" class="btn btn-link text-dark px-2 py-0 btn-sumar" data-id="{{ $producto->id_producto }}" style="font-size:1.5rem;" {{ $stockDisponible == 0 ? 'disabled' : '' }}>+</button>
                                                </div>
                                                <div class="mt-2 stock-info" style="font-size:0.9rem;">
                                                    <strong>Stock:</strong> {{ $stockDisponible }} {{ $producto->unidad_medida }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <!-- Footer caso 3 -->
        <x-app-footer tipo="limpiar-enviar" />
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('pedidoForm');
    const checkboxes = document.querySelectorAll('.producto-checkbox');
    const contadorSpan = document.querySelector('.badge.bg-danger');
    const submitButton = document.querySelector('button[type="submit"]');
    
    // Funcion para actualizar contador
    function actualizarContador() {
        const seleccionados = document.querySelectorAll('.producto-checkbox:checked').length;
        if (contadorSpan) {
            contadorSpan.textContent = seleccionados;
        }
        if (submitButton) {
            submitButton.disabled = seleccionados === 0;
        }
    }
    
    // Inicializar contador
    actualizarContador();

    // Eventos para checkboxes
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', actualizarContador);
    });

    // Botones de restar cantidad
    document.querySelectorAll('.btn-restar').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.dataset.id;
            const input = document.getElementById('cantidad-' + id);
            const currentValue = parseInt(input.value);
            if (currentValue > 1) {
                input.value = currentValue - 1;
            }
        });
    });

    // Botones de sumar cantidad
    document.querySelectorAll('.btn-sumar').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.dataset.id;
            const input = document.getElementById('cantidad-' + id);
            const card = this.closest('.card');
            const stock = parseInt(card.dataset.stock);
            
            const currentValue = parseInt(input.value);
            if (currentValue < stock) {
                input.value = currentValue + 1;
            }
        });
    });

    // Boton limpiar
    document.querySelectorAll('button').forEach(button => {
        if (button.textContent.trim() === 'Limpiar') {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                // Limpiar checkboxes
                checkboxes.forEach(cb => {
                    if (!cb.disabled) {
                        cb.checked = false;
                    }
                });
                // Resetear cantidades
                document.querySelectorAll('[id^="cantidad-"]').forEach(input => {
                    input.value = 1;
                });
                // Actualizar contador
                actualizarContador();
            });
        }
    });
});
</script>
@endsection
