@extends('view_layout.app')

@push('styles')
<link href="{{ asset('css/cocina_historial.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="cocina-header">
    <a href="{{ route('vista.user_menu') }}" class="cocina-header-back">
        <span class="cocina-header-back-icon">&#8592;</span>
    </a>
    <div class="cocina-header-content">
        <div class="cocina-header-title">Historial de Pedidos</div>
        <div class="cocina-header-subtitle">Cocina</div>
    </div>
</div>

<div class="cocina-historial-container">
    <div class="cocina-historial-content" id="pedidosContainer">
        @if($pedidos->isEmpty())
            <div class="no-pedidos">
                <p>No hay pedidos pendientes</p>
            </div>
        @else
            @php
                $pedidosAgrupados = $pedidos->groupBy('pedido.id_pedido');
            @endphp
            @foreach($pedidosAgrupados as $idPedido => $detalles)
                <div class="card-historial" 
                     data-pedido-id="{{ $idPedido }}" 
                     data-mesa-numero="{{ $detalles->first()->pedido->mesa->numero_mesa ?? 'N/A' }}"
                     data-fecha="{{ $detalles->first()->fecha_creacion }}">
                    <div class="card-historial-lateral">
                        <span class="numero-orden">{{ $loop->iteration }}</span>
                    </div>
                    <div class="card-historial-main">
                        <!-- ✨ HEADER CON NÚMERO DE MESA PROMINENTE -->
                        <div class="card-historial-mesa-header">
                            <h2 class="mesa-titulo">Mesa {{ $detalles->first()->pedido->mesa->numero_mesa ?? 'N/A' }}</h2>
                            <span class="fecha-hora">
                                @php
                                    $fechaCreacion = $detalles->first()->fecha_creacion;
                                    if (is_string($fechaCreacion)) {
                                        try {
                                            $fechaFormateada = \Carbon\Carbon::parse($fechaCreacion)->format('H:i');
                                        } catch (\Exception $e) {
                                            $fechaFormateada = $fechaCreacion;
                                        }
                                    } elseif ($fechaCreacion instanceof \Carbon\Carbon || $fechaCreacion instanceof \DateTime) {
                                        $fechaFormateada = $fechaCreacion->format('H:i');
                                    } else {
                                        $fechaFormateada = 'N/A';
                                    }
                                @endphp
                                {{ $fechaFormateada }}
                            </span>
                        </div>
                        
                        <div class="card-historial-table-container">
                            <table class="card-historial-table">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>Cant.</th>
                                        <th>Pedido</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($detalles as $index => $detalle)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td data-cantidad="{{ $detalle->cantidad }}">
                                                @if ($detalle->cantidad < 10)
                                                    0{{ $detalle->cantidad }}
                                                @else
                                                    {{ $detalle->cantidad }}
                                                @endif
                                            </td>
                                            <td data-nombre-producto="{{ $detalle->producto->nombre ?? 'Producto no encontrado' }}">{{ $detalle->producto->nombre ?? 'Producto no encontrado' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="card-historial-footer">
                            <div class="card-historial-tiempo">
                                <div class="card-historial-tiempo-label">Tiempo Aprox.</div>
                                <strong>{{ $detalles->first()->pedido->tiempo_aproximado ?? '20 min' }}</strong>
                                <div class="card-historial-tiempo-line"></div>
                            </div>
                            <button class="btn-listo"
                                    data-pedido-id="{{ $idPedido }}"
                                    data-mesa="{{ $detalles->first()->pedido->mesa->numero_mesa ?? 'N/A' }}">
                                Listo
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
</div>

<!-- Modal de Confirmación -->
<div id="confirmModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Confirmar Pedido</h2>
            <span class="close">&times;</span>
        </div>
        <div class="modal-body">
            <p>¿Estás seguro de que quieres marcar como listo el pedido de la Mesa <span id="mesaNumero"></span>?</p>
            <div id="pedidoDetalles"></div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancelar" onclick="cerrarModal()">Cancelar</button>
            <button class="btn-confirmar" onclick="marcarPedidoListo()">Confirmar</button>
        </div>
    </div>
</div>

<!-- Modal de Éxito -->
<div id="successModal" class="modal">
    <div class="modal-content success-modal">
        <div class="success-icon">✓</div>
        <p id="successMessage"></p>
    </div>
</div>

<!-- Modal de Selección de Productos -->
<div id="modalSeleccionPedido" class="modal" style="display: none;">
    <div class="modal-content modal-seleccion-productos">
        <div class="modal-header">
            <h2>Marcar Productos como Listos</h2>
            <span class="close" onclick="cerrarModalSeleccion()">&times;</span>
        </div>
        <div class="modal-body">
            <div class="info-mesa">
                <p>Pedido de la <strong>Mesa <span id="mesaNumeroSeleccion"></span></strong></p>
                <p class="text-muted">Selecciona los productos que están listos para entregar:</p>
            </div>
            
            <form id="formSeleccionProductos">
                @csrf
                <div id="listaProductosSeleccion" class="productos-seleccion">
                    <!-- Aquí se cargarán los productos dinámicamente -->
                </div>
                
                <div class="contador-seleccion">
                    <small class="text-muted">
                        <span id="contadorSeleccionados">0</span> producto(s) seleccionado(s)
                    </small>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-cancelar" onclick="cerrarModalSeleccion()">Cancelar</button>
            <button type="button" class="btn-enviar-todo" onclick="enviarTodosLosProductos()">Enviar Todo</button>
            <button type="button" class="btn-enviar-seleccionados" id="btnEnviarSeleccionados" disabled onclick="enviarProductosSeleccionados()">Enviar Seleccionado</button>
        </div>
    </div>
</div>

<script>
let pedidoDetalleIdActual = null;
let mesaNumeroActual = null;
let pedidoIdActual = null;
let productosDelPedido = [];

// FUNCIÓN PARA ORDENAR PEDIDOS POR FECHA Y REENUMERAR
function ordenarYRenumerarPedidos() {
    const container = document.getElementById('pedidosContainer');
    const cards = Array.from(container.querySelectorAll('.card-historial'));
    
    if (cards.length === 0) return;
    
    // Ordenar por fecha (más reciente primero)
    cards.sort((a, b) => {
        const fechaA = new Date(a.dataset.fecha || 0);
        const fechaB = new Date(b.dataset.fecha || 0);
        return fechaB - fechaA; // Orden descendente (más reciente primero)
    });
    
    // Reenumerar y reorganizar en el DOM
    cards.forEach((card, index) => {
        const numeroOrden = card.querySelector('.numero-orden');
        if (numeroOrden) {
            numeroOrden.textContent = index + 1;
        }
        
        // Reordenar en el DOM
        container.appendChild(card);
    });
    
    console.log('Pedidos reordenados por fecha y renumerados');
}

// FUNCIÓN PARA ABRIR EL MODAL DE SELECCIÓN
function abrirModalSeleccion(pedidoId, mesaNumero) {
    pedidoIdActual = pedidoId;
    mesaNumeroActual = mesaNumero;
    
    document.getElementById('mesaNumeroSeleccion').textContent = mesaNumero;
    document.getElementById('modalSeleccionPedido').style.display = 'block';
    
    // Cargar productos del pedido
    cargarProductosPedido(pedidoId);
}

// FUNCIÓN PARA CARGAR PRODUCTOS DEL PEDIDO
function cargarProductosPedido(pedidoId) {
    console.log('Cargando productos para pedido ID:', pedidoId); // Debug
    
    const url = "{{ route('cocina.pedido.detalles', ':pedidoId') }}".replace(':pedidoId', pedidoId);
    console.log('URL generada:', url); // Debug
    
    fetch(url, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => {
        console.log('Response status:', response.status); // Debug
        return response.json();
    })
    .then(data => {
        console.log('Data recibida:', data); // Debug
        
        if (data.success) {
            productosDelPedido = data.data.detalles;
            mostrarProductosEnModal(data.data.detalles);
        } else {
            alert('Error al cargar productos: ' + data.message);
            cerrarModalSeleccion();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión al cargar productos');
        cerrarModalSeleccion();
    });
}

// FUNCIÓN PARA MOSTRAR PRODUCTOS EN EL MODAL
function mostrarProductosEnModal(productos) {
    const container = document.getElementById('listaProductosSeleccion');
    container.innerHTML = '';
    
    if (productos.length === 0) {
        container.innerHTML = '<div class="alert alert-info">No hay productos disponibles para marcar.</div>';
        return;
    }
    
    productos.forEach(producto => {
        const productoDiv = document.createElement('div');
        productoDiv.className = 'producto-item-seleccion';
        productoDiv.innerHTML = `
            <input type="checkbox" 
                   class="producto-checkbox" 
                   value="${producto.id_detalle}" 
                   id="producto_${producto.id_detalle}"
                   onchange="actualizarContadorSeleccion()">
            <label for="producto_${producto.id_detalle}" class="producto-info">
                <div class="producto-nombre">${producto.producto}</div>
                <div class="producto-cantidad">Cantidad: ${producto.cantidad}</div>
            </label>
        `;
        container.appendChild(productoDiv);
    });
    
    actualizarContadorSeleccion();
}

// FUNCIÓN PARA ACTUALIZAR CONTADOR DE SELECCIÓN
function actualizarContadorSeleccion() {
    const checkboxes = document.querySelectorAll('.producto-checkbox:checked');
    const contador = document.getElementById('contadorSeleccionados');
    const btnEnviarSeleccionados = document.getElementById('btnEnviarSeleccionados');
    
    const cantidadSeleccionada = checkboxes.length;
    contador.textContent = cantidadSeleccionada;
    
    // Habilitar/deshabilitar botón según selección
    btnEnviarSeleccionados.disabled = cantidadSeleccionada === 0;
    
    // Actualizar estilos visuales
    document.querySelectorAll('.producto-item-seleccion').forEach(item => {
        const checkbox = item.querySelector('.producto-checkbox');
        if (checkbox.checked) {
            item.classList.add('seleccionado');
        } else {
            item.classList.remove('seleccionado');
        }
    });
}

// FUNCIÓN PARA ENVIAR TODOS LOS PRODUCTOS
function enviarTodosLosProductos() {
    if (productosDelPedido.length === 0) {
        alert('No hay productos para enviar');
        return;
    }
    
    const todosLosIds = productosDelPedido.map(producto => producto.id_detalle);
    enviarProductosSeleccionados(todosLosIds);
}

// FUNCIÓN PARA ENVIAR PRODUCTOS SELECCIONADOS
function enviarProductosSeleccionados(idsPersonalizados = null) {
    let detallesIds;
    
    if (idsPersonalizados) {
        detallesIds = idsPersonalizados;
    } else {
        const checkboxes = document.querySelectorAll('.producto-checkbox:checked');
        detallesIds = Array.from(checkboxes).map(cb => cb.value);
    }
    
    if (detallesIds.length === 0) {
        alert('Por favor selecciona al menos un producto');
        return;
    }
    
    const url = "{{ route('cocina.pedido.marcar_seleccionados') }}";
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            detalles_ids: detallesIds
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            cerrarModalSeleccion();
            mostrarModalExito(data.message);
            
            // Remover la card del pedido
            const card = document.querySelector(`.card-historial[data-pedido-id="${pedidoIdActual}"]`);
            if (card) {
                card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                card.style.opacity = '0';
                card.style.transform = 'translateX(-20px)';
                
                setTimeout(() => {
                    card.remove();
                    ordenarYRenumerarPedidos();
                    
                    const remainingCards = document.querySelectorAll('.card-historial');
                    if (remainingCards.length === 0) {
                        const content = document.querySelector('.cocina-historial-content');
                        content.innerHTML = `
                            <div class="no-pedidos">
                                <p>No hay pedidos pendientes</p>
                            </div>
                        `;
                    }
                }, 300);
            }
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión al marcar productos');
    });
}

// FUNCIÓN PARA CERRAR EL MODAL DE SELECCIÓN
function cerrarModalSeleccion() {
    document.getElementById('modalSeleccionPedido').style.display = 'none';
    pedidoIdActual = null;
    mesaNumeroActual = null;
    productosDelPedido = [];
}

// FUNCIONES ORIGINALES MANTENIDAS
function mostrarModalConfirmacion(detalleId, mesaNumero, detalles) {
    pedidoDetalleIdActual = detalleId;
    mesaNumeroActual = mesaNumero;
    
    document.getElementById('mesaNumero').textContent = mesaNumero;
    const pedidoDetalles = document.getElementById('pedidoDetalles');
    pedidoDetalles.innerHTML = '';

    detalles.forEach(detalle => {
        const detalleDiv = document.createElement('div');
        detalleDiv.className = 'detalle-item';
        detalleDiv.innerHTML = `
            <span class="producto">${detalle.nombre}</span>
            <span class="cantidad">x${detalle.cantidad}</span>
        `;
        pedidoDetalles.appendChild(detalleDiv);
    });

    document.getElementById('confirmModal').style.display = 'block';
}

function cerrarModal() {
    document.getElementById('confirmModal').style.display = 'none';
    pedidoDetalleIdActual = null;
    mesaNumeroActual = null;
}

function mostrarModalExito(mensaje) {
    document.getElementById('successMessage').textContent = mensaje;
    document.getElementById('successModal').style.display = 'block';
    setTimeout(() => {
        document.getElementById('successModal').style.display = 'none';
        window.location.reload();
    }, 2000);
}

function marcarPedidoListo() {
    if (!pedidoDetalleIdActual) return;

    // CORRECCIÓN: Usar la helper route() de Laravel correctamente con el parámetro
    const url = "{{ route('cocina.pedido.listo', ':detalle') }}".replace(':detalle', pedidoDetalleIdActual);
    
    console.log('URL construida:', url);

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            cerrarModal();
            mostrarModalExito(`Pedido de la Mesa ${data.mesa} marcado como listo.`);
            
            // Remover la card del pedido con animación
            const card = document.querySelector(`.card-historial[data-pedido-id="${pedidoDetalleIdActual}"]`);
            if (card) {
                card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                card.style.opacity = '0';
                card.style.transform = 'translateX(-20px)';
                
                setTimeout(() => {
                    card.remove();
                    // ✨ REORDENAR DESPUÉS DE ELIMINAR
                    ordenarYRenumerarPedidos();
                    
                    // Verificar si no quedan más pedidos
                    const remainingCards = document.querySelectorAll('.card-historial');
                    if (remainingCards.length === 0) {
                        const content = document.querySelector('.cocina-historial-content');
                        content.innerHTML = `
                            <div class="no-pedidos">
                                <p>No hay pedidos pendientes</p>
                            </div>
                        `;
                    }
                }, 300);
            }
        } else {
            alert('Error al marcar el pedido como listo: ' + (data.message || 'Error desconocido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión al marcar el pedido como listo.');
    });
}

document.addEventListener('DOMContentLoaded', () => {
    // ✨ ORDENAR PEDIDOS AL CARGAR LA PÁGINA
    ordenarYRenumerarPedidos();
    
    // CONFIGURAR EVENTOS PARA LOS BOTONES "LISTO" - USAR MODAL DE SELECCIÓN
    const confirmButtons = document.querySelectorAll('.btn-listo');
    confirmButtons.forEach(button => {
        button.addEventListener('click', () => {
            const pedidoId = button.getAttribute('data-pedido-id');
            const mesaNumero = button.getAttribute('data-mesa');
            
            console.log('Botón clickeado:', { pedidoId, mesaNumero }); // Debug
            
            // Abrir modal de selección en lugar del modal de confirmación simple
            abrirModalSeleccion(pedidoId, mesaNumero);
        });
    });
    
    // Configurar eventos para cerrar modales
    const closeModalButtons = document.querySelectorAll('.close');
    closeModalButtons.forEach(button => {
        button.addEventListener('click', function() {
            cerrarModal();
            cerrarModalSeleccion();
        });
    });
    
    // Cerrar modal al hacer clic fuera
    window.addEventListener('click', (event) => {
        const confirmModal = document.getElementById('confirmModal');
        const seleccionModal = document.getElementById('modalSeleccionPedido');
        
        if (event.target === confirmModal) {
            cerrarModal();  
        }
        
        if (event.target === seleccionModal) {
            cerrarModalSeleccion();
        }
    });
});
</script>
@endsection
