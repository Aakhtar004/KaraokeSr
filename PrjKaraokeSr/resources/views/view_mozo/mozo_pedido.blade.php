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
                            $productosCategoria = $productos->filter(function($producto) use ($categoria) {
                                $producto = (object)$producto;
                                $categoriaNombre = is_array($producto->categoria) ? $producto->categoria['nombre'] : $producto->categoria->nombre;
                                return $categoriaNombre === $categoria->nombre;
                            });
                        @endphp
                        @if($productosCategoria->isNotEmpty())
                            <div class="row">
                                @foreach($productosCategoria as $producto)
                                    @php
                                        $cantidadYaPedida = isset($productosYaPedidos) ? ($productosYaPedidos[$producto->id_producto] ?? 0) : 0;
                                        $producto = (object)$producto;
                                        $categoriaNombre = is_array($producto->categoria) ? $producto->categoria['nombre'] : $producto->categoria->nombre;
                                        
                                        // Manejo especial para baldes
                                        if($categoriaNombre === 'Baldes') {
                                            $disponible = $producto->stock > 0 && $producto->estado == 1;
                                            $stockDisponible = $producto->stock;
                                            $mostrarStock = true;
                                        } elseif($categoriaNombre === 'Cocteles') {
                                            $disponible = $producto->estado == 1;
                                            $stockDisponible = $disponible ? 999 : 0;
                                            $mostrarStock = false;
                                        } else {
                                            $stockDisponible = $producto->stock + $cantidadYaPedida;
                                            $disponible = $stockDisponible > 0 && $producto->estado == 1;
                                            $mostrarStock = true;
                                        }
                                    @endphp
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100 position-relative product-card" 
                                             data-stock="{{ $stockDisponible }}" 
                                             data-es-coctel="{{ $categoriaNombre === 'Cocteles' ? 'true' : 'false' }}" 
                                             data-es-balde="{{ $categoriaNombre === 'Baldes' ? 'true' : 'false' }}" 
                                             data-es-personalizado="{{ isset($producto->es_personalizado) ? 'true' : 'false' }}">
                                        
                                            <!-- Checkbox en la esquina superior derecha -->
                                            <input type="checkbox" 
                                                   name="productos[{{ $producto->id_producto }}][seleccionado]" 
                                                   value="1" 
                                                   id="producto{{ $producto->id_producto }}"
                                                   class="form-check-input position-absolute top-0 end-0 m-2 producto-checkbox" 
                                                   style="z-index:2;"
                                                   {{ !$disponible ? 'disabled' : '' }}
                                                   {{ $cantidadYaPedida > 0 ? 'checked' : '' }}>
                                            
                                            <input type="hidden" name="productos[{{ $producto->id_producto }}][id_producto]" value="{{ $producto->id_producto }}">
                                            
                                            <div class="card-body d-flex flex-column align-items-center">
                                                {{-- IMAGEN DEL PRODUCTO - USANDO EL ESTILO ORIGINAL --}}
                                                @if(isset($producto->imagen_url) && $producto->imagen_url)
                                                    <img src="{{ $producto->imagen_url }}" 
                                                         class="card-img-top mb-2" 
                                                         alt="{{ $producto->nombre }}"
                                                         style="height: 120px; object-fit: cover;">
                                                @elseif($categoriaNombre === 'Baldes')
                                                    {{-- Imagen por defecto para baldes sin imagen --}}
                                                    <div class="card-img-top mb-2 d-flex align-items-center justify-content-center" 
                                                         style="height: 120px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 8px; color: #6c757d;">
                                                        <i class="fas fa-beer fa-3x"></i>
                                                    </div>
                                                @endif
                                                
                                                <h5 class="card-title text-center">{{ $producto->nombre }}</h5>
                                                <p class="card-text text-center text-muted">{{ $producto->descripcion ?? '' }}</p>
                                                
                                                {{-- PRECIOS CON PROMOCIONES --}}
                                                @if($producto->en_promocion ?? false)
                                                    <span class="original-price" style="color: #999; text-decoration: line-through; font-size: 1.1em;">
                                                        S/ {{ number_format($producto->precio_original, 2) }}
                                                    </span>
                                                    <span class="promo-price" style="color: #c4361d; background: #fffbe8; font-weight: bold; font-size: 1.5em; padding: 2px 8px; border-radius: 6px;">
                                                        S/ {{ number_format($producto->precio_promocion, 2) }}
                                                    </span>
                                                @else
                                                    <span class="price" style="font-weight: bold; font-size: 1.5em; color: #c4361d;">
                                                        @if(isset($producto->es_personalizado) && $producto->es_personalizado)
                                                            <span id="precio-personalizado-{{ $producto->id_producto }}">A calcular</span>
                                                        @else
                                                            S/ {{ number_format($producto->precio_unitario, 2) }}
                                                        @endif
                                                    </span>
                                                @endif
                                                
                                                {{-- ESTADO DEL PRODUCTO --}}
                                                @if(!$disponible)
                                                    @if($categoriaNombre === 'Cocteles')
                                                        <div class="alert alert-warning p-1 text-center mb-2">Coctel no disponible</div>
                                                    @elseif($categoriaNombre === 'Baldes')
                                                        <div class="alert alert-danger p-1 text-center mb-2">Sin stock suficiente</div>
                                                    @else
                                                        <div class="alert alert-danger p-1 text-center mb-2">Producto faltante</div>
                                                    @endif
                                                @endif
                                                
                                                @if($cantidadYaPedida > 0)
                                                    <div class="alert alert-warning p-1 text-center mb-2">Ya pedido: {{ $cantidadYaPedida }}</div>
                                                @endif
                                                
                                                {{-- CONTROLES DE CANTIDAD O PERSONALIZACIÓN --}}
                                                <div class="d-flex align-items-center justify-content-center">
                                                    @if(isset($producto->es_personalizado) && $producto->es_personalizado)
                                                        <button type="button" 
                                                                class="btn btn-primary btn-personalizar" 
                                                                data-producto-id="{{ $producto->id_producto }}" 
                                                                {{ !$disponible ? 'disabled' : '' }}>
                                                            Personalizar Balde
                                                        </button>
                                                    @else
                                                        <button type="button" 
                                                                class="btn btn-link btn-restar" 
                                                                data-id="{{ $producto->id_producto }}" 
                                                                {{ !$disponible ? 'disabled' : '' }}>-</button>
                                                        <input type="text" 
                                                               readonly 
                                                               name="productos[{{ $producto->id_producto }}][cantidad]" 
                                                               id="cantidad-{{ $producto->id_producto }}" 
                                                               value="{{ $cantidadYaPedida > 0 ? $cantidadYaPedida : 1 }}" 
                                                               class="text-center border-0 bg-transparent fw-bold">
                                                        <button type="button" 
                                                                class="btn btn-link btn-sumar" 
                                                                data-id="{{ $producto->id_producto }}" 
                                                                {{ !$disponible ? 'disabled' : '' }}>+</button>
                                                    @endif
                                                </div>
                                                
                                                {{-- INFORMACIÓN DE DISPONIBILIDAD --}}
                                                <div class="stock-info">
                                                    @if($categoriaNombre === 'Cocteles')
                                                        <strong>Estado:</strong> 
                                                        <span class="badge {{ $disponible ? 'bg-success' : 'bg-danger' }}">
                                                            {{ $disponible ? 'Disponible' : 'No Disponible' }}
                                                        </span>
                                                    @elseif($categoriaNombre === 'Baldes')
                                                        @if(!isset($producto->es_personalizado))
                                                            <strong>Baldes disponibles:</strong> {{ $stockDisponible }}
                                                        @else
                                                            <strong>Cervezas disponibles:</strong> {{ $cervezasPequenas->sum('stock') }}
                                                        @endif
                                                    @else
                                                        <strong>Stock:</strong> {{ $stockDisponible }} {{ $producto->unidad_medida ?? '' }}
                                                    @endif
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

<!-- Modal para Balde Personalizado -->
<div class="modal fade" id="modalBaldePersonalizado" tabindex="-1" aria-labelledby="modalBaldePersonalizadoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalBaldePersonalizadoLabel">Personalizar Balde</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Selecciona hasta 6 cervezas pequeñas para tu balde personalizado:</p>
                <div class="row" id="cervezasPersonalizadas">
                    @foreach($cervezasPequenas as $cerveza)
                        <div class="col-md-6 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">{{ $cerveza->nombre }}</h6>
                                    <p class="card-text">Stock disponible: {{ $cerveza->stock }}</p>
                                    <p class="card-text">Precio: S/ {{ number_format($cerveza->precio_unitario, 2) }}</p>
                                    <div class="d-flex align-items-center">
                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-restar-cerveza" data-cerveza-id="{{ $cerveza->id_producto }}">-</button>
                                        <input type="number" class="form-control mx-2 text-center cerveza-cantidad" 
                                               id="cerveza-{{ $cerveza->id_producto }}" 
                                               data-cerveza-id="{{ $cerveza->id_producto }}"

                                               data-precio="{{ $cerveza->precio_unitario }}"
                                               data-stock="{{ $cerveza->stock }}"
                                               value="0" min="0" max="{{ $cerveza->stock }}" readonly>
                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-sumar-cerveza" data-cerveza-id="{{ $cerveza->id_producto }}">+</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3">
                    <p><strong>Total cervezas seleccionadas:</strong> <span id="totalCervezas">0</span> / 6</p>
                    <p><strong>Precio total:</strong> S/ <span id="precioTotal">0.00</span></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmarBaldePersonalizado">Confirmar Balde</button>
            </div>
        </div>
    </div>
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
    //  Usar la clase correcta del ejemplo original
    const checkboxes = document.querySelectorAll('.producto-checkbox');
    const contadorSpan = document.querySelector('.badge.bg-danger');
    const submitButton = document.querySelector('button[type="submit"]');
    
    // Función para actualizar contador - CORREGIDA
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

    // Eventos para checkboxes - CORREGIDO
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

    // Botones de sumar cantidad - MODIFICADO para cocteles
    document.querySelectorAll('.btn-sumar').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.dataset.id;
            const input = document.getElementById('cantidad-' + id);
            const card = this.closest('.card');
            const maxStock = parseInt(card.dataset.stock);
            const esCoctel = card.dataset.esCoctel === 'true';
            const currentValue = parseInt(input.value);
            
            // Para cocteles, no hay límite de stock
            if (esCoctel) {
                input.value = currentValue + 1;
            } else {
                // Para otros productos, respetar el stock
                if (currentValue < maxStock) {
                    input.value = currentValue + 1;
                }
            }
        });
    });

    // Botón limpiar - CORREGIDO
    document.querySelectorAll('button').forEach(button => {
        if (button.textContent.trim() === 'Limpiar') {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                // Limpiar todos los checkboxes - CORREGIDO
                document.querySelectorAll('.form-check-input[type="checkbox"]').forEach(cb => {
                    cb.checked = false;
                });
                // Resetear todas las cantidades a 1
                document.querySelectorAll('input[name*="cantidad"]').forEach(input => {
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

// Variables para balde personalizado
let baldePersonalizadoActual = null;
let cervezasSeleccionadas = {};

// Evento para botón personalizar balde
document.querySelectorAll('.btn-personalizar').forEach(btn => {
    btn.addEventListener('click', function() {
        baldePersonalizadoActual = this.dataset.productoId;
        cervezasSeleccionadas = {};
        
        // Resetear cantidades
        document.querySelectorAll('.cerveza-cantidad').forEach(input => {
            input.value = 0;
        });
        actualizarTotalBalde();
        
        // Mostrar modal
        new bootstrap.Modal(document.getElementById('modalBaldePersonalizado')).show();
    });
});

// Eventos para botones de sumar/restar cervezas
document.querySelectorAll('.btn-sumar-cerveza').forEach(btn => {
    btn.addEventListener('click', function() {
        const cervezaId = this.dataset.cervezaId;
        const input = document.getElementById('cerveza-' + cervezaId);
        const currentValue = parseInt(input.value);
        const maxStock = parseInt(input.dataset.stock);
        const totalActual = calcularTotalCervezas();
        
        if (currentValue < maxStock && totalActual < 6) {
            input.value = currentValue + 1;
            actualizarTotalBalde();
        }
    });
});

document.querySelectorAll('.btn-restar-cerveza').forEach(btn => {
    btn.addEventListener('click', function() {
        const cervezaId = this.dataset.cervezaId;
        const input = document.getElementById('cerveza-' + cervezaId);
        const currentValue = parseInt(input.value);
        
        if (currentValue > 0) {
            input.value = currentValue - 1;
            actualizarTotalBalde();
        }
    });
});

// Función para calcular total de cervezas
function calcularTotalCervezas() {
    let total = 0;
    document.querySelectorAll('.cerveza-cantidad').forEach(input => {
        total += parseInt(input.value);
    });
    return total;
}

// Función para actualizar totales del balde
function actualizarTotalBalde() {
    const totalCervezas = calcularTotalCervezas();
    let precioTotal = 0;
    
    document.querySelectorAll('.cerveza-cantidad').forEach(input => {
        const cantidad = parseInt(input.value);
        const precio = parseFloat(input.dataset.precio);
        precioTotal += cantidad * precio;
    });
    
    document.getElementById('totalCervezas').textContent = totalCervezas;
    document.getElementById('precioTotal').textContent = precioTotal.toFixed(2);
    
    // Actualizar precio en el producto principal
    if (baldePersonalizadoActual) {
        const precioSpan = document.getElementById('precio-personalizado-' + baldePersonalizadoActual);
        if (precioSpan) {
            precioSpan.textContent = totalCervezas > 0 ? 'S/ ' + precioTotal.toFixed(2) : 'A calcular';
        }
    }
    
    // Habilitar/deshabilitar botón confirmar
    const btnConfirmar = document.getElementById('confirmarBaldePersonalizado');
    btnConfirmar.disabled = totalCervezas === 0;
}

// Confirmar balde personalizado - CORREGIDO para actualizar contador
document.getElementById('confirmarBaldePersonalizado').addEventListener('click', function() {
    const totalCervezas = calcularTotalCervezas();
    
    if (totalCervezas === 0) {
        alert('Debe seleccionar al menos una cerveza');
        return;
    }
    
    if (totalCervezas > 6) {
        alert('No puede seleccionar más de 6 cervezas');
        return;
    }
    
    // Guardar configuración del balde personalizado
    cervezasSeleccionadas = {};
    document.querySelectorAll('.cerveza-cantidad').forEach(input => {
        const cantidad = parseInt(input.value);
        if (cantidad > 0) {
            cervezasSeleccionadas[input.dataset.cervezaId] = {
                cantidad: cantidad,
                precio: parseFloat(input.dataset.precio)
            };
        }
    });
    
    // Marcar checkbox del balde personalizado
    const checkbox = document.getElementById('producto' + baldePersonalizadoActual);
    if (checkbox) {
        checkbox.checked = true;
        
        // Crear input hidden con la configuración
        const configInput = document.createElement('input');
        configInput.type = 'hidden';
        configInput.name = 'productos[' + baldePersonalizadoActual + '][configuracion_balde]';
        configInput.value = JSON.stringify(cervezasSeleccionadas);
        checkbox.parentNode.appendChild(configInput);
    }
    
    // Cerrar modal
    bootstrap.Modal.getInstance(document.getElementById('modalBaldePersonalizado')).hide();
    
    //  Usar setTimeout para actualizar contador
    setTimeout(() => {
        const seleccionados = document.querySelectorAll('.producto-checkbox:checked').length;
        const contadorSpan = document.querySelector('.badge.bg-danger');
        const submitButton = document.querySelector('button[type="submit"]');
        
        if (contadorSpan) {
            contadorSpan.textContent = seleccionados;
        }
        if (submitButton) {
            submitButton.disabled = seleccionados === 0;
        }
    }, 100);
});
</script>
@endsection
