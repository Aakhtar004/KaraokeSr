@extends('view_layout.app')

@push('styles')
<link href="{{ asset('css/barra_historial.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="barra-header">
    <a href="{{ route('vista.user_menu') }}" class="barra-header-back">
        <span class="barra-header-back-icon">&#8592;</span>
    </a>
    <div class="barra-header-content">
        <div class="barra-header-title">Historial de Pedidos</div>
        <div class="barra-header-subtitle">Barra</div>
    </div>
</div>

<div class="barra-historial-container">
    <div class="barra-historial-content" id="pedidosContainer">
        @if($pedidos->isEmpty())
            <div class="no-pedidos">
                <p>No hay pedidos pendientes</p>
            </div>
        @else
            @php
                $pedidosAgrupados = $pedidos->groupBy('pedido.id_pedido');
            @endphp
            
            @foreach($pedidosAgrupados as $idPedido => $detalles)
                <div class="card-historial-wrapper">
                    <div class="card-historial" 
                         data-pedido-id="{{ $idPedido }}" 
                         data-mesa-numero="{{ $detalles->first()->pedido->mesa->numero_mesa ?? 'N/A' }}"
                         data-fecha="{{ $detalles->first()->fecha_creacion }}">
                        
                        <!-- NÚMERO DE ORDEN AL LADO IZQUIERDO -->
                        <div class="card-historial-lateral">
                            <span class="numero-orden">{{ $loop->iteration }}</span>
                        </div>
                        
                        <div class="card-historial-main">
                            <!-- HEADER CON NÚMERO DE MESA PROMINENTE -->
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
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>N°</th>
                                            <th>Cant.</th>
                                            <th>Producto</th>
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
                                        data-id="{{ $detalles->first()->id_pedido_detalle }}"
                                        data-mesa="{{ $detalles->first()->pedido->mesa->numero_mesa ?? 'N/A' }}">
                                    Listo
                                </button>
                            </div>
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

<script>
let pedidoDetalleIdActual = null;
let mesaNumeroActual = null;

// FUNCIÓN PARA ORDENAR PEDIDOS POR FECHA Y REENUMERAR
function ordenarYRenumerarPedidos() {
    const container = document.getElementById('pedidosContainer');
    const cards = Array.from(container.querySelectorAll('.card-historial-wrapper'));
    
    if (cards.length === 0) return;
    
    // Ordenar por fecha (más reciente primero)
    cards.sort((a, b) => {
        const fechaA = new Date(a.querySelector('.card-historial').dataset.fecha || 0);
        const fechaB = new Date(b.querySelector('.card-historial').dataset.fecha || 0);
        return fechaB - fechaA; // Orden descendente (más reciente primero)
    });
    
    // Reenumerar y reorganizar en el DOM
    cards.forEach((cardWrapper, index) => {
        const numeroOrden = cardWrapper.querySelector('.numero-orden');
        if (numeroOrden) {
            numeroOrden.textContent = index + 1;
        }
        
        // Reordenar en el DOM
        container.appendChild(cardWrapper);
    });
    
    console.log('Pedidos reordenados por fecha y renumerados');
}

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
    const url = "{{ route('barra.pedido.listo', ':detalle') }}".replace(':detalle', pedidoDetalleIdActual);
    
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
            
            // Encontrar y remover la card específica
            const cardWrapper = document.querySelector(`.card-historial[data-pedido-id="${pedidoDetalleIdActual}"]`)?.closest('.card-historial-wrapper');
            if (cardWrapper) {
                cardWrapper.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                cardWrapper.style.opacity = '0';
                cardWrapper.style.transform = 'translateX(-20px)';
                
                setTimeout(() => {
                    cardWrapper.remove();
                    // REORDENAR DESPUÉS DE ELIMINAR
                    ordenarYRenumerarPedidos();
                    
                    const remainingCards = document.querySelectorAll('.card-historial-wrapper');
                    if (remainingCards.length === 0) {
                        const content = document.querySelector('.barra-historial-content');
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
        console.error('Error completo:', error);
        alert('Error de conexión al marcar el pedido como listo. Revisa la consola para más detalles.');
    });
}

document.addEventListener('DOMContentLoaded', () => {
    // ORDENAR PEDIDOS AL CARGAR LA PÁGINA
    ordenarYRenumerarPedidos();
    
    // Configurar eventos para los botones "Listo"
    const confirmButtons = document.querySelectorAll('.btn-listo');
    confirmButtons.forEach(button => {
        button.addEventListener('click', () => {
            const detalleId = button.getAttribute('data-id');
            const mesaNumero = button.getAttribute('data-mesa');
            
            // Obtener detalles del pedido de la tabla
            const detalles = Array.from(button.closest('.card-historial').querySelectorAll('tbody tr')).map(row => {
                const cantidadCell = row.querySelector('td[data-cantidad]');
                const nombreCell = row.querySelector('td[data-nombre-producto]');
                
                return {
                    cantidad: cantidadCell ? cantidadCell.getAttribute('data-cantidad') : '1',
                    nombre: nombreCell ? nombreCell.getAttribute('data-nombre-producto') : 'Producto'
                };
            });
            
            mostrarModalConfirmacion(detalleId, mesaNumero, detalles);
        });
    });
    
    // Configurar eventos para cerrar modales
    const closeModalButtons = document.querySelectorAll('.close');
    closeModalButtons.forEach(button => {
        button.addEventListener('click', cerrarModal);
    });
    
    // Cerrar modal al hacer clic fuera
    window.addEventListener('click', (event) => {
        const modal = document.getElementById('confirmModal');
        if (event.target === modal) {
            cerrarModal();  
        }
    });
});
</script>
@endsection
