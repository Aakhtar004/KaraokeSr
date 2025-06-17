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
    <div class="barra-historial-content">
        @if($pedidos->isEmpty())
            <div class="no-pedidos">
                <p>No hay pedidos pendientes</p>
            </div>
        @else
            @php
                $pedidosAgrupados = $pedidos->groupBy('pedido.id_pedido');
            @endphp
            
            @foreach($pedidosAgrupados as $idPedido => $detalles)
                <div class="card-historial" data-pedido-id="{{ $idPedido }}" data-mesa-numero="{{ $detalles->first()->pedido->mesa->numero_mesa ?? 'N/A' }}">
                    <div class="card-historial-lateral">
                        <span>{{ $loop->iteration }}</span>
                    </div>
                    <div class="card-historial-main">
                        <div class="card-historial-mesa">Mesa {{ $detalles->first()->pedido->mesa->numero_mesa ?? 'N/A' }}</div>
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
                            <button class="btn-listo" onclick="confirmarPedido({{ $idPedido }}, '{{ $detalles->first()->pedido->mesa->numero_mesa ?? 'N/A' }}')">
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

@endsection

@push('scripts')
<script src="{{ asset('js/app.js') }}" type="module"></script>
<script>
let pedidoIdActual = null;

function confirmarPedido(pedidoId, mesaNumero) {
    pedidoIdActual = pedidoId;
    const modal = document.getElementById('confirmModal');
    const mesaSpan = document.getElementById('mesaNumero');
    const detallesDiv = document.getElementById('pedidoDetalles');
    
    mesaSpan.textContent = mesaNumero;
    
    // Obtener detalles del pedido
    const card = document.querySelector(`[data-pedido-id="${pedidoId}"]`);
    const filas = card.querySelectorAll('tbody tr');
    let detallesHTML = '<div class="pedido-detalles">';
    filas.forEach(fila => {
        const producto = fila.cells[2].textContent;
        const cantidad = fila.cells[1].textContent;
        detallesHTML += `<div class="detalle-item">
            <span class="producto">${producto}</span>
            <span class="cantidad">x${cantidad}</span>
        </div>`;
    });
    detallesHTML += '</div>';
    
    detallesDiv.innerHTML = detallesHTML;
    modal.style.display = 'block';
}

function cerrarModal() {
    const modal = document.getElementById('confirmModal');
    modal.style.display = 'none';
}

function marcarPedidoListo() {
    if (!pedidoIdActual) return;
    
    fetch(`/marcar-pedido-listo/${pedidoIdActual}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            cerrarModal();
            mostrarMensajeExito(data.mesa);
        } else {
            alert('Error al marcar el pedido como listo');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al marcar el pedido como listo');
    });
}

function mostrarMensajeExito(mesa) {
    const modal = document.getElementById('successModal');
    const mensaje = document.getElementById('successMessage');
    mensaje.textContent = `Pedido de la mesa N° ${mesa} enviado correctamente :)`;
    
    modal.style.display = 'block';
    
    setTimeout(() => {
        modal.style.display = 'none';
        window.location.reload();
    }, 2000);
}

// Cerrar modal al hacer clic en la X
document.querySelector('.close').onclick = cerrarModal;

// Cerrar modal al hacer clic fuera
window.onclick = function(event) {
    const modal = document.getElementById('confirmModal');
    if (event.target == modal) {
        cerrarModal();
    }
}

// Inicializar los botones Listo
document.addEventListener('DOMContentLoaded', function() {
    const botonesListo = document.querySelectorAll('.btn-listo');
    botonesListo.forEach(boton => {
        boton.addEventListener('click', function() {
            const pedidoId = this.closest('.card-historial').dataset.pedidoId;
            const mesaNumero = this.closest('.card-historial').dataset.mesaNumero;
            confirmarPedido(pedidoId, mesaNumero);
        });
    });
});
</script>
@endpush
