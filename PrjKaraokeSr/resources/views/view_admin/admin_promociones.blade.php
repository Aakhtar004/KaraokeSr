@extends('view_layout.app')

@section('content')
<x-app-header backUrl="{{ route('vista.user_menu') }}" title="Gestión de Promociones" />

<div class="container mt-4 mb-5 pb-5">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Lista de promociones -->
    @if($promociones->isEmpty())
        <div class="alert alert-info text-center">
            <i class="fas fa-info-circle me-2"></i>
            No hay promociones creadas aún.
        </div>
    @else
        <div class="row">
            @foreach($promociones as $promocion)
                <div class="col-12 mb-3">
                    <div class="card shadow-sm border-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h5 class="card-title text-primary fw-bold">{{ $promocion->nombre_promocion }}</h5>
                                    <p class="card-text mb-2">
                                        <span class="badge bg-secondary me-2">{{ $promocion->descripcion_promocion }}</span>
                                        <span class="text-success fw-bold fs-5">S/ {{ number_format($promocion->precio_promocion, 2) }}</span>
                                    </p>
                                    <div class="mb-2">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            {{ \Carbon\Carbon::parse($promocion->fecha_inicio)->format('d/m/Y') }} - 
                                            {{ \Carbon\Carbon::parse($promocion->fecha_fin)->format('d/m/Y') }}
                                        </small>
                                        <small class="text-muted ms-3">
                                            <i class="fas fa-box me-1"></i>
                                            Stock: {{ $promocion->stock_promocion ?? 0 }}
                                        </small>
                                    </div>
                                    <div class="mt-2">
                                        <strong class="text-dark">Productos incluidos:</strong>
                                        <div class="mt-1">
                                            @foreach($promocion->productos as $promoProducto)
                                                <span class="badge bg-light text-dark border me-1 mb-1">{{ $promoProducto->producto->nombre }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex flex-column align-items-end gap-2">
                                    <!-- Botones de acción -->
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-warning btn-sm" 
                                                data-promocion-id="{{ $promocion->id_promocion }}" 
                                                onclick="editarPromocion(this.dataset.promocionId)">
                                            <i class="fas fa-edit me-1"></i>Editar
                                        </button>
                                        <button class="btn btn-danger btn-sm" 
                                                data-promocion-id="{{ $promocion->id_promocion }}" 
                                                onclick="confirmarEliminar(this.dataset.promocionId)">
                                            <i class="fas fa-trash me-1"></i>Borrar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
    
    <!-- Botón flotante para nueva promoción -->
    <button id="btnNuevaPromocion" class="btn btn-primary position-fixed shadow-lg" 
            style="bottom: 20px; right: 20px; border-radius: 50px; padding: 15px 25px; z-index: 1000;">
        <i class="fas fa-plus me-2"></i>Nueva Promoción
    </button>
</div>

<!-- Modal para Nueva/Editar Promoción -->
<div class="modal fade" id="modalPromocion" tabindex="-1" aria-labelledby="modalPromocionLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalPromocionLabel">Nueva Promoción</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formPromocion">
                    @csrf
                    <input type="hidden" id="promocionId" name="promocion_id">
                    
                    <!-- Nombre y Estado -->
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label for="nombrePromocion" class="form-label">Nombre de la Promoción *</label>
                            <input type="text" class="form-control" id="nombrePromocion" name="nombre_promocion" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Estado</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="estadoPromocion" name="estado_promocion" checked>
                                <label class="form-check-label" for="estadoPromocion">
                                    <span id="estadoTexto">Activa</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Tipo de Promoción y Stock -->
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label for="tipoPromocion" class="form-label">Tipo de Promoción *</label>
                            <select class="form-select" id="tipoPromocion" name="tipo_promocion" required>
                                <option value="">Seleccionar tipo</option>
                                <option value="2x1">2x1 - Paga 1 y lleva 2</option>
                                <option value="10%descuento">10% de descuento</option>
                                <option value="50%descuento">50% de descuento</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="stockPromocion" class="form-label">Stock Disponible *</label>
                            <input type="number" class="form-control" id="stockPromocion" name="stock_promocion" min="1" max="999" required>
                        </div>
                    </div>

                    <!-- Fechas -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="fechaInicio" class="form-label">Fecha de Inicio *</label>
                            <input type="date" class="form-control" id="fechaInicio" name="fecha_inicio" required>
                        </div>
                        <div class="col-md-6">
                            <label for="fechaFin" class="form-label">Fecha de Fin *</label>
                            <input type="date" class="form-control" id="fechaFin" name="fecha_fin" required>
                        </div>
                    </div>

                    <!-- Selección de productos -->
                    <div class="mb-3">
                        <label class="form-label">Productos incluidos *</label>
                        <button type="button" class="btn btn-outline-primary btn-sm ms-2" onclick="abrirSelectorProductos()">
                            <i class="fas fa-plus me-1"></i>Agregar Productos
                        </button>
                        <div id="productosSeleccionados" class="mt-2 p-3 border rounded bg-light" style="min-height: 60px;">
                            <small class="text-muted">No hay productos seleccionados</small>
                        </div>
                        <input type="hidden" id="productosIds" name="productos_ids">
                    </div>

                    <!-- Precio calculado -->
                    <div class="alert alert-info">
                        <strong>Precio de promoción:</strong> <span id="precioCalculado">S/ 0.00</span>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarPromocion">
                    <i class="fas fa-save me-1"></i>Guardar Promoción
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Seleccionar Productos -->
<div class="modal fade" id="modalSeleccionarProductos" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title">Seleccionar Productos</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Barra de búsqueda -->
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="buscarProducto" placeholder="Buscar producto...">
                    </div>
                </div>
                
                <!-- Contenedor de productos por categoría -->
                <div id="contenedorProductosModal" style="max-height: 500px; overflow-y: auto;">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" onclick="confirmarSeleccionProductos()">
                    <i class="fas fa-check me-1"></i>Confirmar Selección
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.producto-card {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
}

.producto-card:hover {
    border-color: #007bff;
    box-shadow: 0 4px 12px rgba(0,123,255,0.15);
    transform: translateY(-2px);
}

.producto-card.selected {
    border-color: #28a745;
    background-color: #f8fff9;
    box-shadow: 0 4px 12px rgba(40,167,69,0.15);
}

.categoria-header {
    background: linear-gradient(45deg, #007bff, #0056b3);
    color: white;
    padding: 12px 20px;
    border-radius: 8px;
    margin-bottom: 15px;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(0,123,255,0.2);
}

.producto-seleccionado {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 10px;
    margin: 5px;
    display: inline-block;
    position: relative;
}

.btn-quitar-producto {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 12px;
    cursor: pointer;
}
</style>

<script>
let productosDisponibles = [];
let productosSeleccionadosTemp = [];
let modoEdicion = false;

document.addEventListener('DOMContentLoaded', function() {
    // Configurar fecha mínima
    const hoy = new Date().toISOString().split('T')[0];
    document.getElementById('fechaInicio').setAttribute('min', hoy);
    
    // Event listeners
    document.getElementById('btnNuevaPromocion').addEventListener('click', abrirModalNuevaPromocion);
    document.getElementById('btnGuardarPromocion').addEventListener('click', guardarPromocion);
    document.getElementById('buscarProducto').addEventListener('input', filtrarProductos);
    document.getElementById('estadoPromocion').addEventListener('change', actualizarTextoEstado);
    document.getElementById('fechaInicio').addEventListener('change', validarFechas);
    document.getElementById('fechaFin').addEventListener('change', validarFechas);
    
    // Cargar productos disponibles
    cargarProductosDisponibles();
});

function abrirModalNuevaPromocion() {
    modoEdicion = false;
    document.getElementById('modalPromocionLabel').textContent = 'Nueva Promoción';
    document.getElementById('btnGuardarPromocion').innerHTML = '<i class="fas fa-save me-1"></i>Guardar Promoción';
    document.getElementById('formPromocion').reset();
    document.getElementById('promocionId').value = '';
    document.getElementById('estadoPromocion').checked = true;
    
    // Limpiar productos seleccionados
    productosSeleccionadosTemp = [];
    actualizarProductosSeleccionados();
    
    // Configurar fecha mínima
    const hoy = new Date().toISOString().split('T')[0];
    document.getElementById('fechaInicio').setAttribute('min', hoy);
    
    actualizarTextoEstado();
    new bootstrap.Modal(document.getElementById('modalPromocion')).show();
}

function editarPromocion(promocionId) {
    modoEdicion = true;
    document.getElementById('modalPromocionLabel').textContent = 'Editar Promoción';
    document.getElementById('btnGuardarPromocion').innerHTML = '<i class="fas fa-save me-1"></i>Actualizar Promoción';
    
    // Obtener datos de la promoción
    const url = '{{ route("admin.obtener_promocion", ":id") }}'.replace(':id', promocionId);
    
    fetch(url, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const promocion = data.promocion;
                
                document.getElementById('promocionId').value = promocion.id_promocion;
                document.getElementById('nombrePromocion').value = promocion.nombre_promocion;
                document.getElementById('stockPromocion').value = promocion.stock_promocion;
                document.getElementById('fechaInicio').value = promocion.fecha_inicio;
                document.getElementById('fechaFin').value = promocion.fecha_fin;
                
                // Determinar tipo de promoción
                if (promocion.descripcion_promocion.includes('2x1')) {
                    document.getElementById('tipoPromocion').value = '2x1';
                } else if (promocion.descripcion_promocion.includes('10%')) {
                    document.getElementById('tipoPromocion').value = '10%descuento';
                } else if (promocion.descripcion_promocion.includes('50%')) {
                    document.getElementById('tipoPromocion').value = '50%descuento';
                }
                
                // Cargar productos seleccionados
                productosSeleccionadosTemp = promocion.productos || [];
                actualizarProductosSeleccionados();
                
                new bootstrap.Modal(document.getElementById('modalPromocion')).show();
            } else {
                alert('Error al cargar promoción: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar promoción: ' + error.message);
        });
}

function cargarProductosDisponibles() {
    fetch('{{ route("admin.productos_promocion") }}')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                productosDisponibles = data.productos;
            } else {
                console.error('Error al cargar productos:', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function abrirSelectorProductos() {
    cargarProductosEnModal();
    new bootstrap.Modal(document.getElementById('modalSeleccionarProductos')).show();
}

function cargarProductosEnModal() {
    const contenedor = document.getElementById('contenedorProductosModal');
    contenedor.innerHTML = '';
    
    if (productosDisponibles.length === 0) {
        contenedor.innerHTML = '<div class="alert alert-warning">No hay productos disponibles</div>';
        return;
    }
    
    // Agrupar por categoría
    const productosPorCategoria = {};
    productosDisponibles.forEach(producto => {
        const categoria = producto.categoria.nombre;
        if (!productosPorCategoria[categoria]) {
            productosPorCategoria[categoria] = [];
        }
        productosPorCategoria[categoria].push(producto);
    });
    
    Object.keys(productosPorCategoria).forEach(categoria => {
        const categoriaDiv = document.createElement('div');
        categoriaDiv.className = 'mb-4';
        
        const header = document.createElement('div');
        header.className = 'categoria-header';
        header.innerHTML = '<i class="fas fa-utensils me-2"></i>' + categoria;
        categoriaDiv.appendChild(header);
        
        const productosDiv = document.createElement('div');
        productosDiv.className = 'row';
        
        productosPorCategoria[categoria].forEach(producto => {
            const col = document.createElement('div');
            col.className = 'col-md-6 col-lg-4';
            
            const isSelected = productosSeleccionadosTemp.includes(producto.id_producto);
            
            col.innerHTML = `
                <div class="producto-card ${isSelected ? 'selected' : ''}" 
                     data-producto-id="${producto.id_producto}" 
                     data-producto-nombre="${producto.nombre}"
                     data-producto-precio="${producto.precio_unitario}"
                     onclick="toggleProducto(${producto.id_producto})">
                    <h6 class="mb-1">${producto.nombre}</h6>
                    <small class="text-success fw-bold">S/ ${parseFloat(producto.precio_unitario).toFixed(2)}</small>
                    ${isSelected ? '<div class="text-success mt-1"><i class="fas fa-check-circle"></i> Seleccionado</div>' : ''}
                </div>
            `;
            productosDiv.appendChild(col);
        });
        
        categoriaDiv.appendChild(productosDiv);
        contenedor.appendChild(categoriaDiv);
    });
}

function toggleProducto(productoId) {
    const index = productosSeleccionadosTemp.indexOf(productoId);
    const card = document.querySelector(`[data-producto-id="${productoId}"]`);
    
    if (index > -1) {
        productosSeleccionadosTemp.splice(index, 1);
        card.classList.remove('selected');
        card.querySelector('.text-success')?.remove();
    } else {
        productosSeleccionadosTemp.push(productoId);
        card.classList.add('selected');
        card.innerHTML += '<div class="text-success mt-1"><i class="fas fa-check-circle"></i> Seleccionado</div>';
    }
}

function confirmarSeleccionProductos() {
    actualizarProductosSeleccionados();
    bootstrap.Modal.getInstance(document.getElementById('modalSeleccionarProductos')).hide();
}

function actualizarProductosSeleccionados() {
    const contenedor = document.getElementById('productosSeleccionados');
    const inputIds = document.getElementById('productosIds');
    
    if (productosSeleccionadosTemp.length === 0) {
        contenedor.innerHTML = '<small class="text-muted">No hay productos seleccionados</small>';
        inputIds.value = '';
        document.getElementById('precioCalculado').textContent = 'S/ 0.00';
        return;
    }
    
    contenedor.innerHTML = '';
    let precioTotal = 0;
    
    productosSeleccionadosTemp.forEach(productoId => {
        const producto = productosDisponibles.find(p => p.id_producto == productoId);
        if (producto) {
            precioTotal += parseFloat(producto.precio_unitario);
            
            const div = document.createElement('div');
            div.className = 'producto-seleccionado';
            div.innerHTML = `
                ${producto.nombre} - S/ ${parseFloat(producto.precio_unitario).toFixed(2)}
                <button type="button" class="btn-quitar-producto" onclick="quitarProducto(${productoId})">×</button>
            `;
            contenedor.appendChild(div);
        }
    });
    
    inputIds.value = productosSeleccionadosTemp.join(',');
    calcularPrecioPromocion(precioTotal);
}

function quitarProducto(productoId) {
    const index = productosSeleccionadosTemp.indexOf(productoId);
    if (index > -1) {
        productosSeleccionadosTemp.splice(index, 1);
        actualizarProductosSeleccionados();
    }
}

function calcularPrecioPromocion(precioTotal) {
    const tipoPromocion = document.getElementById('tipoPromocion').value;
    let precioPromocion = precioTotal;
    
    switch (tipoPromocion) {
        case '2x1':
            precioPromocion = precioTotal / 2;
            break;
        case '10%descuento':
            precioPromocion = precioTotal * 0.9;
            break;
        case '50%descuento':
            precioPromocion = precioTotal * 0.5;
            break;
    }
    
    document.getElementById('precioCalculado').textContent = 'S/ ' + precioPromocion.toFixed(2);
}

function filtrarProductos() {
    const busqueda = document.getElementById('buscarProducto').value.toLowerCase();
    const productCards = document.querySelectorAll('.producto-card');
    
    productCards.forEach(card => {
        const nombre = card.dataset.productoNombre.toLowerCase();
        const categoriaHeader = card.closest('.mb-4').querySelector('.categoria-header');
        
        if (nombre.includes(busqueda)) {
            card.parentElement.style.display = 'block';
        } else {
            card.parentElement.style.display = 'none';
        }
    });
}

function actualizarTextoEstado() {
    const checkbox = document.getElementById('estadoPromocion');
    const texto = document.getElementById('estadoTexto');
    texto.textContent = checkbox.checked ? 'Activa' : 'Inactiva';
}

function validarFechas() {
    const fechaInicio = document.getElementById('fechaInicio').value;
    const fechaFin = document.getElementById('fechaFin').value;
    
    if (fechaInicio && fechaFin && fechaFin <= fechaInicio) {
        alert('La fecha de fin debe ser posterior a la fecha de inicio');
        document.getElementById('fechaFin').value = '';
    }
}

function guardarPromocion() {
    const form = document.getElementById('formPromocion');
    const formData = new FormData(form);
    
    // Validaciones
    if (productosSeleccionadosTemp.length === 0) {
        alert('Debe seleccionar al menos un producto');
        return;
    }
    
    // Limpiar productos anteriores del FormData
    formData.delete('productos[]');
    
    // Agregar productos seleccionados
    productosSeleccionadosTemp.forEach(id => {
        formData.append('productos[]', id);
    });
    
    // Agregar estado de promoción
    formData.append('estado_promocion', document.getElementById('estadoPromocion').checked ? 'activa' : 'inactiva');
    
    const mensaje = modoEdicion ? '¿Deseas actualizar esta promoción?' : '¿Confirmar creación de promoción?';
    
    if (confirm(mensaje)) {
        let url, method;
        
        if (modoEdicion) {
            const promocionId = document.getElementById('promocionId').value;
            url = '{{ route("admin.actualizar_promocion", ":id") }}'.replace(':id', promocionId);
            method = 'POST';
            formData.append('_method', 'PUT');
        } else {
            url = '{{ route("admin.store_promocion") }}';
            method = 'POST';
        }
        
        // Mostrar loading
        const btnGuardar = document.getElementById('btnGuardarPromocion');
        const textoOriginal = btnGuardar.innerHTML;
        btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';
        btnGuardar.disabled = true;
        
        fetch(url, {
            method: method,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => {
            return response.json().then(data => {
                return { status: response.status, data: data };
            });
        })
        .then(({ status, data }) => {
            if (status === 200 && data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                // Manejar errores de validación
                if (status === 422 && data.errors) {
                    let errorMessages = [];
                    for (let field in data.errors) {
                        errorMessages.push(...data.errors[field]);
                    }
                    alert('Errores de validación:\n' + errorMessages.join('\n'));
                } else {
                    alert('Error: ' + (data.message || 'Error desconocido'));
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al procesar la solicitud: ' + error.message);
        })
        .finally(() => {
            // Restaurar botón
            btnGuardar.innerHTML = textoOriginal;
            btnGuardar.disabled = false;
        });
    }
}

function togglePromocion(id) {
    fetch('{{ route("admin.toggle_promocion", ":id") }}'.replace(':id', id), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert('Error al cambiar estado de la promoción');
            location.reload();
        } else {
            // Actualizar badge de estado
            const badge = document.querySelector(`#promocion${id}`).closest('.card').querySelector('.badge');
            if (badge) {
                badge.textContent = data.estado === 'activa' ? 'Activa' : 'Inactiva';
                badge.className = data.estado === 'activa' ? 'badge bg-success' : 'badge bg-secondary';
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al cambiar estado de la promoción');
        location.reload();
    });
}

function confirmarEliminar(id) {
    if (confirm('¿Estás seguro de que quieres eliminar esta promoción? Esta acción no se puede deshacer.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("admin.eliminar_promocion", ":id") }}'.replace(':id', id);
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = document.querySelector('meta[name="csrf-token"]').content;
        
        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'DELETE';
        
        form.appendChild(csrfToken);
        form.appendChild(methodField);
        document.body.appendChild(form);
        form.submit();
    }
}

// Event listener para cambio de tipo de promoción
document.getElementById('tipoPromocion').addEventListener('change', function() {
    if (productosSeleccionadosTemp.length > 0) {
        actualizarProductosSeleccionados();
    }
});
</script>
@endsection
