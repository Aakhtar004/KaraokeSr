@extends('view_layout.app')

@section('content')
<x-app-header backUrl="{{ route('vista.user_menu') }}" />
<div class="container mt-4">
    <h2>Control de Inventario de la Barra</h2>
    <label>Buscar producto:</label>
    <input type="text" id="searchInput" class="form-control mb-3" placeholder="Buscar por nombre o categoría" onkeyup="filterProducts()">
    <script>
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
    </script>
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
                                    <div class="card h-100">
                                        @if($producto->imagen_url)
                                            <img src="{{ $producto->imagen_url }}" class="card-img-top" alt="{{ $producto->nombre }}">
                                        @endif
                                        <div class="card-body">
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
</div>
@endsection
