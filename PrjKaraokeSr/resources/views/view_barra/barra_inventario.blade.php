@extends('view_layout.app')

@push('styles')
<link href="{{ asset('css/barra_inventario.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="barra-header">
    <a href="{{ route('vista.user_menu') }}" class="barra-header-back">
        <span class="barra-header-back-icon">&#8592;</span>
    </a>
    <div class="barra-header-content">
        <div class="barra-header-title">Control de Inventario</div>
        <div class="barra-header-subtitle">Bar</div>
    </div>
</div>
<div class="container">
    <div class="search-container mb-4">
        <input type="text" id="searchInput" class="form-control" placeholder="Buscar por nombre o categoría..." onkeyup="filterProducts()">

    <form id="pedidoForm" action="{{ route('cocina.inventario.pedido') }}" method="POST">
        @csrf
        <div class="footer-buttons">
            <button type="button" id="btnLimpiar" class="btn-limpiar" onclick="limpiarSeleccion()">Limpiar</button>
            <button type="submit" id="btnEnviar" class="btn-enviar" disabled>Enviar</button>
        </div>
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
                            <p>No hay productos en esta categoría.</p>
                        @else
                            <div class="row">
                                @foreach($productosCategoria as $producto)
                                    <div class="col-md-4 mb-4">
                                        <div class="card h-100 position-relative">
                                            <!-- Checkbox en la esquina superior derecha -->
                                            <input type="checkbox" name="productos[]" value="{{ $producto->id_producto }}" class="form-check-input position-absolute top-0 end-0 m-2 producto-checkbox" style="z-index:2;">
                                            <div class="card-body">
                                                @if($producto->estado == 0)
                                                    <div class="alert alert-warning p-1 text-center mb-2" style="font-size:0.9rem;">PEDIDO</div>
                                                @endif
                                                @if($producto->imagen_url)
                                                    <img src="{{ $producto->imagen_url }}" class="card-img-top" alt="{{ $producto->nombre }}">
                                                @endif
                                                <h5 class="card-title">{{ $producto->nombre }}</h5>
                                                <p class="card-text">{{ $producto->descripcion }}</p>
                                                <p><strong>Precio:</strong> S/ {{ number_format($producto->precio_unitario, 2) }}</p>
                                                <p><strong>Stock:</strong> {{ $producto->stock }} {{ $producto->unidad_medida }}</p>
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
    </form>
</div>

<script>
function limpiarSeleccion() {
    const checkboxes = document.querySelectorAll('.producto-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = false;
    });
    document.getElementById('btnEnviar').disabled = true;
}

function filterProducts() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toLowerCase();
    const categories = document.querySelectorAll('.accordion-item');

    categories.forEach(category => {
        const products = category.querySelectorAll('.card');
        let hasVisibleProduct = false;

        products.forEach(product => {
            const title = product.querySelector('.card-title').textContent.toLowerCase();
            const description = product.querySelector('.card-text').textContent.toLowerCase();

            if (title.includes(filter) || description.includes(filter)) {
                product.style.display = '';
                hasVisibleProduct = true;
            } else {
                product.style.display = 'none';
            }
        });

        category.style.display = hasVisibleProduct ? '' : 'none';
    });
}

// Habilitar/deshabilitar el botón Enviar según los checkboxes
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.producto-checkbox');
    const btnEnviar = document.getElementById('btnEnviar');
    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            btnEnviar.disabled = document.querySelectorAll('.producto-checkbox:checked').length === 0;
        });
    });
});
</script>
@endsection
