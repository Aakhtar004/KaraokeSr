@extends('view_layout.app')

@section('content')
<x-app-header backUrl="{{ route('vista.mozo_historial') }}" title="Facturación - Mesa {{ $pedido->mesa->numero_mesa }}" />

<div class="container d-flex flex-column align-items-center justify-content-center" style="min-height: 80vh;">
    <form id="facturacionForm" action="{{ route('pedidos.procesar_facturacion', $pedido->id_pedido) }}" method="POST" class="w-100" style="max-width: 400px;">
        @csrf

        <!-- Información del pedido -->
        <div class="card mb-3">
            <div class="card-body">
                <h6>Mesa: {{ $pedido->mesa->numero_mesa }}</h6>
                <h6>Total: S/ {{ number_format($pedido->total_pedido, 2) }}</h6>
            </div>
        </div>

        <!-- Tipo de comprobante -->
        <div class="mb-3">
            <select class="form-select" name="tipo_comprobante" id="tipo_comprobante" required>
                <option value="boleta">Boleta</option>
                <option value="factura">Factura</option>
            </select>
        </div>

        <!-- RUC o DNI -->
        <div class="mb-3">
            <label class="form-label mb-0" id="labelDocumento" for="documento">Boleta</label>
            <span class="text-danger" id="tipoDoc">DNI</span>
            <div class="input-group">
                <input type="text" class="form-control" name="documento" id="documento" placeholder="Ingrese DNI" required>
                <button type="button" class="btn btn-outline-dark" id="vistaPreviaBtn">Vista Previa</button>
                <button type="button" class="btn btn-outline-secondary" id="randomBtn">Randomizar</button>
            </div>
        </div>

        <!-- Métodos de pago -->
        <div class="mb-3">
            <label class="form-label">Método de pago</label>
            <div class="row g-2 align-items-center" id="metodoPagoContainer">
                <div class="col-1 d-flex justify-content-center align-items-center">
                    <button type="button" class="btn btn-outline-dark btn-sm" id="addMetodoPago" title="Agregar método de pago">+</button>
                </div>
                <div class="col">
                    <select class="form-select mb-2" name="metodo_pago[]" required>
                        <option value="">Seleccione</option>
                        <option value="efectivo">Efectivo</option>
                        <option value="tarjeta">Tarjeta</option>
                        <option value="yape">Yape</option>
                        <option value="plin">Plin</option>
                    </select>
                </div>
                <div class="col">
                    <input type="number" class="form-control mb-2 monto-pago" name="monto_pago[]" placeholder="Monto" min="0" step="0.01" value="{{ $pedido->total_pedido }}" required>
                </div>
            </div>
            <div id="metodosPagoExtras"></div>
        </div>

        <!-- Confirmar -->
        <div class="d-grid">
            <button type="submit" class="btn btn-dark">Confirmar</button>
        </div>
    </form>
</div>

<!-- Modal para enviar correo -->
<div class="modal fade" id="enviarCorreoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Enviar al correo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="enviarCorreoForm">
                    <div class="mb-3">
                        <label class="form-label">DNI del cliente</label>
                        <input type="text" class="form-control" name="dni_correo" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Correo electrónico</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="enviarCorreoBtn">Enviar</button>
            </div>
        </div>
    </div>
</div>

<script>
let comprobanteId = null;

document.getElementById('tipo_comprobante').addEventListener('change', function() {
    const tipo = this.value;
    document.getElementById('labelDocumento').textContent = tipo === 'factura' ? 'Factura' : 'Boleta';
    document.getElementById('tipoDoc').textContent = tipo === 'factura' ? 'RUC' : 'DNI';
    document.getElementById('documento').placeholder = tipo === 'factura' ? 'Ingrese RUC' : 'Ingrese DNI';
});

document.getElementById('addMetodoPago').addEventListener('click', function(e) {
    e.preventDefault();
    const extra = document.createElement('div');
    extra.className = 'row g-2 align-items-center mt-2';
    extra.innerHTML = `
        <div class="col-1"></div>
        <div class="col">
            <select class="form-select" name="metodo_pago[]" required>
                <option value="">Seleccione</option>
                <option value="efectivo">Efectivo</option>
                <option value="tarjeta">Tarjeta</option>
                <option value="yape">Yape</option>
                <option value="plin">Plin</option>
            </select>
        </div>
        <div class="col">
            <input type="number" class="form-control monto-pago" name="monto_pago[]" placeholder="Monto" min="0" step="0.01" required>
        </div>
    `;
    document.getElementById('metodosPagoExtras').appendChild(extra);
});

document.getElementById('randomBtn').addEventListener('click', function() {
    const tipo = document.getElementById('tipo_comprobante').value;
    document.getElementById('documento').value = tipo === 'factura'
        ? Math.floor(10000000000 + Math.random() * 90000000000)
        : Math.floor(10000000 + Math.random() * 90000000);
});

document.getElementById('facturacionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch(this.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            comprobanteId = data.comprobante_id;
            mostrarModalAcciones();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al procesar la facturación');
    });
});

function mostrarModalAcciones() {
    if (confirm('¿Qué quieres hacer?\nPresiona OK para Imprimir o Cancelar para Enviar al correo')) {
        // Imprimir - por implementar
        alert('Funcionalidad de impresión por implementar');
    } else {
        // Enviar al correo
        const modal = new bootstrap.Modal(document.getElementById('enviarCorreoModal'));
        modal.show();
    }
}

document.getElementById('enviarCorreoBtn').addEventListener('click', function() {
    const form = document.getElementById('enviarCorreoForm');
    const formData = new FormData(form);
    
    fetch(`/factura/${comprobanteId}/enviar`, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('enviarCorreoModal'));
            modal.hide();
            
            // Mostrar vista previa - Corregir la URL
            window.location.href = `/factura/${comprobanteId}/vista-previa`;
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al enviar el correo');
    });
});
</script>
@endsection