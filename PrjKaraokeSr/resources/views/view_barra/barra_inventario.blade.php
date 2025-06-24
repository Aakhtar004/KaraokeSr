@extends('view_layout.app')

@push('styles')
<link href="{{ asset('css/barra_inventario.css') }}" rel="stylesheet" media="all">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<meta name="csrf-token" content="{{ csrf_token() }}">
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
    <!-- Modal de confirmación (creado dinámicamente por JavaScript) -->
    <div class="modal-overlay-inventario" id="modalConfirmacion" style="display: none;">
        <div class="modal-inventario">
            <div class="modal-header-inventario">
                <h5 class="modal-title-inventario">Mensaje</h5>
                <button class="modal-close-inventario" onclick="cerrarModalConfirmacion()">&times;</button>
            </div>
            <div class="modal-body-inventario" id="modalBodyContent">
                <!-- Contenido dinámico -->
            </div>
            <div class="modal-footer-inventario" id="modalFooterContent">
                <!-- Botones dinámicos -->
            </div>
        </div>
    </div>

    <div class="search-container mb-4">
        <input type="text" id="searchInput" class="form-control" placeholder="Buscar por nombre o categoría..." onkeyup="filterProducts()">
    </div>

    <form id="pedidoForm" action="{{ route('barra.inventario.pedido') }}" method="POST">
        @csrf
        <input type="hidden" name="accion" id="inputAccion" value="nueva">
        <div class="footer-buttons">
            <button type="button" id="btnLimpiar" class="btn-limpiar" onclick="limpiarSeleccion()">Limpiar</button>
            <button type="submit" id="btnEnviar" class="btn-enviar" disabled>Enviar</button>
        </div>
        <div class="accordion mt-4" id="accordionCategorias">
            @if($categorias_producto->isEmpty())
                <div class="alert alert-info text-center">
                    <p>No hay categorías de inventario de bar disponibles.</p>
                </div>
            @else
                @foreach($categorias_producto as $categoria)
                    @php
                        $productosCategoria = $productos->where('id_categoria_producto', $categoria->id_categoria_producto);
                    @endphp
                    @if($productosCategoria->isNotEmpty())
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading{{ $categoria->id_categoria_producto }}">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $categoria->id_categoria_producto }}" aria-expanded="false" aria-controls="collapse{{ $categoria->id_categoria_producto }}">
                                    {{ $categoria->nombre }}
                                </button>
                            </h2>
                            <div id="collapse{{ $categoria->id_categoria_producto }}" class="accordion-collapse collapse" aria-labelledby="heading{{ $categoria->id_categoria_producto }}" data-bs-parent="#accordionCategorias">
                                <div class="accordion-body">
                                    <div class="row">
                                        @foreach($productosCategoria as $producto)
                                            <div class="col-md-6 mb-3">
                                                <div class="card h-100 position-relative">
                                                    <input type="checkbox" name="productos[]" value="{{ $producto->id_producto }}" class="form-check-input position-absolute top-0 end-0 m-2 producto-checkbox" style="z-index:2;">
                                                    <div class="card-body">
                                                        @if($producto->estado == 0)
                                                            @php
                                                                $esPedidoHoy = $producto->fecha_actualizacion && 
                                                                              \Carbon\Carbon::parse($producto->fecha_actualizacion)->format('Y-m-d') === now()->format('Y-m-d');
                                                            @endphp
                                                            @if($esPedidoHoy)
                                                                <div class="badge-pedido-hoy">
                                                                    <i class="fas fa-check-circle"></i> YA PEDIDO HOY
                                                                    <br><small>{{ \Carbon\Carbon::parse($producto->fecha_actualizacion)->format('d/m/Y H:i') }}</small>
                                                                </div>
                                                            @else
                                                                <div class="badge-pedido-anterior">
                                                                    <i class="fas fa-clock"></i> PEDIDO
                                                                    <br><small>{{ $producto->fecha_actualizacion ? \Carbon\Carbon::parse($producto->fecha_actualizacion)->format('d/m/Y H:i') : 'Sin fecha' }}</small>
                                                                </div>
                                                            @endif
                                                        @endif
                                                        @if($producto->imagen_url)
                                                            <img src="{{ $producto->imagen_url }}" class="card-img-top mb-2" alt="{{ $producto->nombre }}" style="height: 120px; object-fit: cover;">
                                                        @endif
                                                        <h6 class="card-title">{{ $producto->nombre }}</h6>
                                                        <p class="card-text text-muted small">{{ $producto->descripcion ?? 'Sin descripción' }}</p>
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <span class="badge bg-primary">Stock: {{ $producto->stock }}</span>
                                                            <span class="text-muted small">{{ $producto->unidad_medida }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            @endif
        </div>
    </form>
</div>

<script>
let productosSeleccionados = [];

function limpiarSeleccion() {
    const checkboxes = document.querySelectorAll('.producto-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = false;
    });
    document.getElementById('btnEnviar').disabled = true;
    productosSeleccionados = [];
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

function cerrarModalConfirmacion() {
    const modal = document.getElementById('modalConfirmacion');
    if (modal) {
        modal.style.display = 'none';
    }
}

// *** INTERCEPTAR EL ENVÍO DEL FORMULARIO ***
document.getElementById('pedidoForm').addEventListener('submit', function(e) {
    e.preventDefault(); // ¡Detener el envío automático!
    
    const checkboxes = document.querySelectorAll('.producto-checkbox:checked');
    
    if (checkboxes.length === 0) {
        alert('Por favor seleccione al menos un producto.');
        return;
    }

    // Obtener IDs de productos seleccionados
    productosSeleccionados = Array.from(checkboxes).map(cb => cb.value);
    
    // Hacer petición AJAX para verificar el estado
    fetch('{{ route("barra.inventario.verificar") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            productos: productosSeleccionados
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert(data.error);
            return;
        }
        
        mostrarModal(data);
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al verificar el estado del inventario.');
    });
});

// Función para mostrar el modal con el contenido apropiado
function mostrarModal(data) {
    const modal = document.getElementById('modalConfirmacion');
    const modalBody = document.getElementById('modalBodyContent');
    const modalFooter = document.getElementById('modalFooterContent');
    
    // Construir lista de productos nuevos
    let listaProductosNuevos = '';
    for (const [categoria, productos] of Object.entries(data.productos_nuevos)) {
        listaProductosNuevos += productos.map(p => p.nombre).join(', ');
        if (Object.keys(data.productos_nuevos).indexOf(categoria) < Object.keys(data.productos_nuevos).length - 1) {
            listaProductosNuevos += ', ';
        }
    }
    
    if (!data.tiene_pedido_previo) {
        // Primera vez del día
        modalBody.innerHTML = `
            <p class="mensaje-principal">¿Seguro que quieres enviar la siguiente lista?</p>
            <div class="seccion-productos">
                <strong>Barra:</strong>
                <div class="lista-productos-inline">${listaProductosNuevos}</div>
            </div>
        `;
        
        modalFooter.innerHTML = `
            <button type="button" class="btn-cancelar-inventario" onclick="cerrarModalConfirmacion()">Cancelar</button>
            <button type="button" class="btn-confirmar-inventario" onclick="confirmarPrimeraVez()">Confirmar</button>
        `;
    } else {
        // Segunda vez del día
        modalBody.innerHTML = `
            <p class="mensaje-principal">Esta es la segunda vez que envías una lista de control de inventario.</p>
            <div class="seccion-productos">
                <strong>Barra:</strong>
                <div class="lista-productos-inline">${listaProductosNuevos}</div>
            </div>
            <div class="pregunta-accion">
                <strong>¿Deseas reemplazar o agregar estos productos a la lista anterior?</strong>
            </div>
        `;
        
        modalFooter.innerHTML = `
            <button type="button" class="btn-cancelar-inventario" onclick="cerrarModalConfirmacion()">Cancelar</button>
            <button type="button" class="btn-agregar-inventario" onclick="confirmarAgregar()">Agregar y Confirmar</button>
            <button type="button" class="btn-reemplazar-inventario" onclick="confirmarReemplazar()">Reemplazar</button>
        `;
    }
    
    modal.style.display = 'block';
    
    setTimeout(() => {
        const modalDialog = modal.querySelector('.modal-inventario');
        if (modalDialog) {
            // Calcular posición centrada manualmente
            const windowHeight = window.innerHeight;
            const windowWidth = window.innerWidth;
            const modalHeight = modalDialog.offsetHeight;
            const modalWidth = modalDialog.offsetWidth;
            
            const top = Math.max(0, (windowHeight - modalHeight) / 2);
            const left = Math.max(0, (windowWidth - modalWidth) / 2);
            
            modalDialog.style.position = 'fixed';
            modalDialog.style.top = `${top}px`;
            modalDialog.style.left = `${left}px`;
            modalDialog.style.transform = 'none';
            modalDialog.style.margin = '0';
        }
    }, 10);
}

// Funciones para confirmar acciones
function confirmarPrimeraVez() {
    document.getElementById('inputAccion').value = 'confirmar_primera';
    cerrarModalConfirmacion();
    document.getElementById('pedidoForm').removeEventListener('submit', arguments.callee);
    document.getElementById('pedidoForm').submit();
}

function confirmarAgregar() {
    document.getElementById('inputAccion').value = 'agregar';
    cerrarModalConfirmacion();
    document.getElementById('pedidoForm').removeEventListener('submit', arguments.callee);
    document.getElementById('pedidoForm').submit();
}

function confirmarReemplazar() {
    document.getElementById('inputAccion').value = 'reemplazar';
    cerrarModalConfirmacion();
    document.getElementById('pedidoForm').removeEventListener('submit', arguments.callee);
    document.getElementById('pedidoForm').submit();
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
