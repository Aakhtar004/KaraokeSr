@extends('view_layout.app')

@push('styles')
<link href="{{ asset('css/mozo_pedido.css') }}" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
@endpush

@section('content')
<div class="mozo-header">
    <a href="{{ isset($editando) && $editando ? route('pedidos.editar', session('editando_pedido')) : route('vista.mozo_mesa') }}" class="mozo-header-back">
        <span class="mozo-header-back-icon">&#8592;</span>
    </a>
    <div class="mozo-header-content">
        <div class="mozo-header-title">Toma de Pedido</div>
        <div class="mozo-header-subtitle">Mozo</div>
    </div>
</div>

<div class="container mt-4 mb-5 pb-5">
    @if(isset($editando) && $editando)
        <div class="alert alert-info">
            Esta agregando productos al pedido existente
        </div>
    @endif

    <!-- Barra de búsqueda -->
    <div class="search-container mb-4">
        <input type="text" id="searchInput" class="form-control" placeholder="Buscar productos..." onkeyup="filterProducts()" style="border-radius: 25px; padding: 12px 20px; border: 2px solid #e5735c;">
    </div>

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
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100 position-relative product-card" data-stock="{{ $stockDisponible }}">
                                            <!-- Checkbox en la esquina superior derecha -->
                                            <input type="checkbox" 
                                                   name="productos[{{ $producto->id_producto }}][seleccionado]" 
                                                   value="1" 
                                                   class="form-check-input position-absolute top-0 end-0 m-2 producto-checkbox" 
                                                   {{ $stockDisponible == 0 ? 'disabled' : '' }}
                                                   {{ $cantidadYaPedida > 0 ? 'checked' : '' }}>
                                            <div class="card-body d-flex flex-column align-items-center">
                                                @if($producto->imagen_url)
                                                    <img src="{{ $producto->imagen_url }}" class="card-img-top mb-2" alt="{{ $producto->nombre }}">
                                                @endif
                                                <h5 class="card-title text-center">{{ $producto->nombre }}</h5>
                                                <p class="card-text text-center text-muted">{{ $producto->descripcion ?? '' }}</p>
                                                @if($stockDisponible == 0)
                                                    <div class="alert alert-danger p-1 text-center mb-2">Producto faltante</div>
                                                @endif
                                                @if($cantidadYaPedida > 0)
                                                    <div class="alert alert-warning p-1 text-center mb-2">Ya pedido: {{ $cantidadYaPedida }}</div>
                                                @endif
                                                <div class="d-flex align-items-center justify-content-center">
                                                    <button type="button" class="btn btn-link btn-restar" data-id="{{ $producto->id_producto }}" {{ $stockDisponible == 0 ? 'disabled' : '' }}>-</button>
                                                    <input type="text" readonly name="productos[{{ $producto->id_producto }}][cantidad]" id="cantidad-{{ $producto->id_producto }}" value="{{ $cantidadYaPedida > 0 ? $cantidadYaPedida : 1 }}" class="text-center border-0 bg-transparent fw-bold">
                                                    <button type="button" class="btn btn-link btn-sumar" data-id="{{ $producto->id_producto }}" {{ $stockDisponible == 0 ? 'disabled' : '' }}>+</button>
                                                </div>
                                                <div class="stock-info">
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
// Función para normalizar texto (remover tildes y convertir a minúsculas)
function normalizeText(text) {
    return text.toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '') // Remover acentos/tildes
        .trim();
}

// Función de filtrado de productos mejorada
function filterProducts() {
    const input = document.getElementById('searchInput');
    const filter = normalizeText(input.value);
    const categories = document.querySelectorAll('.accordion-item');
    
    categories.forEach(category => {
        const products = category.querySelectorAll('.product-card');
        let hasVisibleProduct = false;
        
        products.forEach(product => {
            const title = normalizeText(product.querySelector('.card-title').textContent);
            const description = product.querySelector('.card-text') ? 
                normalizeText(product.querySelector('.card-text').textContent) : '';
            
            if (title.includes(filter) || description.includes(filter)) {
                product.style.display = '';
                hasVisibleProduct = true;
            } else {
                product.style.display = 'none';
            }
        });
        
        // Mostrar/ocultar categoría completa
        if (hasVisibleProduct) {
            category.style.display = '';
            
            // Si hay productos visibles y hay texto de búsqueda, abrir automáticamente la categoría
            if (filter.length > 0) {
                const collapseElement = category.querySelector('.accordion-collapse');
                const button = category.querySelector('.accordion-button');
                
                if (collapseElement && button) {
                    // Abrir la categoría
                    collapseElement.classList.add('show');
                    button.classList.remove('collapsed');
                    button.setAttribute('aria-expanded', 'true');
                }
            }
        } else {
            category.style.display = 'none';
        }
    });
    
    // Si no hay texto de búsqueda, cerrar todas las categorías
    if (filter.length === 0) {
        categories.forEach(category => {
            const collapseElement = category.querySelector('.accordion-collapse');
            const button = category.querySelector('.accordion-button');
            
            if (collapseElement && button) {
                collapseElement.classList.remove('show');
                button.classList.add('collapsed');
                button.setAttribute('aria-expanded', 'false');
            }
        });
    }
}

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
    
    // NO pre-seleccionar productos cuando se está editando
    // Los productos deben empezar sin seleccionar, independientemente del contexto
    
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
            // Obtener el stock desde el div contenedor de la card
            const card = this.closest('.card');
            const maxStock = parseInt(card.dataset.stock);
            const currentValue = parseInt(input.value);
            if (currentValue < maxStock) {
                input.value = currentValue + 1;
            }
        });
    });

    // Boton limpiar
    document.querySelectorAll('button').forEach(button => {
        if (button.textContent.trim() === 'Limpiar') {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                // Limpiar todos los checkboxes
                checkboxes.forEach(cb => {
                    cb.checked = false;
                });
                // Resetear todas las cantidades a 1
                document.querySelectorAll('input[type="number"]').forEach(input => {
                    input.value = 1;
                });
                // Limpiar barra de búsqueda
                document.getElementById('searchInput').value = '';
                // Mostrar todos los productos y cerrar categorías
                filterProducts();
                actualizarContador();
            });
        }
    });

    // Estilo para el input de búsqueda al hacer focus
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('focus', function() {
        this.style.borderColor = '#d05e4a';
        this.style.boxShadow = '0 0 8px rgba(208, 94, 74, 0.4)';
    });
    
    searchInput.addEventListener('blur', function() {
        this.style.borderColor = '#e5735c';
        this.style.boxShadow = 'none';
    });
});
</script>
@endsection
