@extends('view_layout.app')

@push('styles')
<link href="{{ asset('css/mozo_pedido_facturacion.css') }}" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
@endpush

@section('content')
<div class="mozo-header">
    <a href="{{ route('vista.mozo_historial') }}" class="mozo-header-back">
        <span class="mozo-header-back-icon">&#8592;</span>
    </a>
    <div class="mozo-header-content">
        <div class="mozo-header-title">Facturación</div>
        <div class="mozo-header-subtitle">Mesa {{ $pedido->mesa->numero_mesa }}</div>
    </div>
</div>

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
            <label class="form-label">Tipo de comprobante</label>
            <select class="form-select" name="tipo_comprobante" id="tipo_comprobante" required>
                <option value="boleta">Boleta</option>
                <option value="factura">Factura</option>
            </select>
        </div>

        <!-- RUC o DNI con botón de consulta -->
        <div class="mb-3">
            <label class="form-label mb-0" id="labelDocumento" for="documento">Boleta</label>
            <span class="text-danger" id="tipoDoc">DNI</span>
            <div class="input-group">
                <input type="text" class="form-control" name="documento" id="documento" placeholder="Ingrese DNI" required>
                <!-- BOTÓN PARA CONSULTAR DNI/RUC -->
                <button type="button" class="btn btn-outline-primary" id="consultarBtn">
                    <i class="bi bi-search"></i> Consultar
                </button>
            </div>
            <!-- ÁREA PARA MOSTRAR RESULTADO DE LA CONSULTA -->
            <div id="resultadoConsulta" class="mt-2" style="display: none;">
                <small class="text-success">
                    <i class="bi bi-check-circle"></i>
                    <span id="nombreCompleto"></span>
                </small>
                <!-- INFORMACIÓN ADICIONAL PARA RUC -->
                <div id="infoRuc" style="display: none;">
                    <small class="text-muted d-block">
                        <i class="bi bi-building"></i>
                        <span id="direccionEmpresa"></span>
                    </small>
                    <small class="text-muted d-block">
                        <i class="bi bi-info-circle"></i>
                        Estado: <span id="estadoEmpresa"></span> | 
                        Condición: <span id="condicionEmpresa"></span>
                    </small>
                </div>
            </div>
        </div>

        <!-- CAMPO OCULTO PARA EL NOMBRE DEL CLIENTE -->
        <input type="hidden" name="nombre_cliente" id="nombre_cliente" value="Cliente">

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
                    @csrf
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

// Cambiar etiquetas según tipo de comprobante
document.getElementById('tipo_comprobante').addEventListener('change', function() {
    const tipo = this.value;
    const labelDocumento = document.getElementById('labelDocumento');
    const tipoDoc = document.getElementById('tipoDoc');
    const documento = document.getElementById('documento');
    const resultadoConsulta = document.getElementById('resultadoConsulta');
    const infoRuc = document.getElementById('infoRuc');
    const nombreCliente = document.getElementById('nombre_cliente');
    
    // Verificar que los elementos existen antes de usarlos
    if (labelDocumento) labelDocumento.textContent = tipo === 'factura' ? 'Factura' : 'Boleta';
    if (tipoDoc) tipoDoc.textContent = tipo === 'factura' ? 'RUC' : 'DNI';
    if (documento) documento.placeholder = tipo === 'factura' ? 'Ingrese RUC' : 'Ingrese DNI';
    
    // Limpiar resultado anterior al cambiar tipo
    if (resultadoConsulta) resultadoConsulta.style.display = 'none';
    if (infoRuc) infoRuc.style.display = 'none';
    if (nombreCliente) nombreCliente.value = 'Cliente';
});

// FUNCIÓN UNIFICADA PARA CONSULTAR DNI O RUC CON VALIDACIÓN MEJORADA
document.getElementById('consultarBtn').addEventListener('click', function() {
    const documento = document.getElementById('documento').value.trim();
    const tipoComprobante = document.getElementById('tipo_comprobante').value;
    const btn = this;
    const resultadoDiv = document.getElementById('resultadoConsulta');
    const nombreCompletoSpan = document.getElementById('nombreCompleto');
    const nombreClienteInput = document.getElementById('nombre_cliente');
    const infoRucDiv = document.getElementById('infoRuc');
    
    console.log('Iniciando consulta...', { documento, tipoComprobante });
    
    // Verificar que todos los elementos existen
    if (!resultadoDiv || !nombreCompletoSpan || !nombreClienteInput || !infoRucDiv) {
        console.error('Algunos elementos del DOM no fueron encontrados');
        alert('Error interno: elementos de la interfaz no encontrados');
        return;
    }
    
    // Determinar si es DNI o RUC y validar formato
    let esValido = false;
    let endpoint = '';
    let tipoBusqueda = '';
    
    if (tipoComprobante === 'boleta') {
        // Validar DNI (8 dígitos)
        esValido = /^[0-9]{8}$/.test(documento);
        endpoint = '{{ route("api.consultar_dni") }}';
        tipoBusqueda = 'dni';
        
        if (!esValido) {
            alert('Por favor ingrese un DNI válido de 8 dígitos');
            return;
        }
    } else {
        // Validar RUC (11 dígitos, empezar con 10 o 20)
        esValido = /^(10|20)[0-9]{9}$/.test(documento);
        endpoint = '{{ route("api.consultar_ruc") }}';
        tipoBusqueda = 'ruc';
        
        if (!esValido) {
            alert('Por favor ingrese un RUC válido de 11 dígitos que empiece con 10 o 20');
            return;
        }
    }
    
    console.log('Endpoint a usar:', endpoint);
    
    // Deshabilitar botón y mostrar estado de carga
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Consultando...';
    resultadoDiv.style.display = 'none';
    infoRucDiv.style.display = 'none';
    
    // Preparar datos para enviar
    const requestData = {};
    requestData[tipoBusqueda] = documento;
    
    console.log('Datos a enviar:', requestData);
    
    // Hacer petición AJAX al endpoint correspondiente
    fetch(endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(requestData)
    })
    .then(response => {
        console.log('Response status:', response.status);
        
        // Verificar si la respuesta es JSON válida
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('La respuesta no es JSON válido');
        }
        
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        
        if (data.success) {
            if (tipoBusqueda === 'dni') {
                // Mostrar resultado para DNI
                nombreCompletoSpan.textContent = data.data.nombre_completo;
                nombreClienteInput.value = data.data.nombre_completo;
                infoRucDiv.style.display = 'none';
            } else {
                // Mostrar resultado para RUC
                nombreCompletoSpan.textContent = data.data.razon_social;
                nombreClienteInput.value = data.data.razon_social;
                
                // Mostrar información adicional de la empresa
                const direccionEmpresa = document.getElementById('direccionEmpresa');
                const estadoEmpresa = document.getElementById('estadoEmpresa');
                const condicionEmpresa = document.getElementById('condicionEmpresa');
                
                if (direccionEmpresa) direccionEmpresa.textContent = data.data.direccion || 'No disponible';
                if (estadoEmpresa) estadoEmpresa.textContent = data.data.estado || 'No disponible';
                if (condicionEmpresa) condicionEmpresa.textContent = data.data.condicion || 'No disponible';
                infoRucDiv.style.display = 'block';
            }
            resultadoDiv.style.display = 'block';
        } else {
            // Mostrar error
            console.error('Error de API:', data.message);
            alert('Error: ' + data.message);
            nombreClienteInput.value = 'Cliente';
        }
    })
    .catch(error => {
        console.error('Error completo:', error);
        alert('Error de conexión al consultar el ' + (tipoBusqueda === 'dni' ? 'DNI' : 'RUC') + '. Revisa la consola para más detalles.');
        nombreClienteInput.value = 'Cliente';
    })
    .finally(() => {
        // Restaurar botón a estado original
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-search"></i> Consultar';
    });
});

// Agregar métodos de pago adicionales
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

// Generar números aleatorios
document.getElementById('randomBtn').addEventListener('click', function() {
    const tipo = document.getElementById('tipo_comprobante').value;
    if (tipo === 'factura') {
        // Generar RUC aleatorio válido (empezar con 20)
        const rucAleatorio = '20' + Math.floor(Math.random() * 1000000000).toString().padStart(9, '0');
        document.getElementById('documento').value = rucAleatorio;
    } else {
        // Generar DNI aleatorio
        document.getElementById('documento').value = Math.floor(10000000 + Math.random() * 90000000);
    }
});

// Procesar formulario de facturación
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
        // Imprimir: redirigir a la vista previa del comprobante usando la ruta de Laravel
        const vistaPrevia = "{{ route('factura.vista_previa', ':comprobante_id') }}".replace(':comprobante_id', comprobanteId);
        window.location.href = vistaPrevia;
    } else {
        // Enviar al correo
        const modal = new bootstrap.Modal(document.getElementById('enviarCorreoModal'));
        modal.show();
    }
}

document.getElementById('enviarCorreoBtn').addEventListener('click', function() {
    const form = document.getElementById('enviarCorreoForm');
    const formData = new FormData(form);
    
    const enviarUrl = "{{ route('factura.enviar_correo', ':comprobante_id') }}".replace(':comprobante_id', comprobanteId);
    
    fetch(enviarUrl, {
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
            
            // Mostrar vista previa usando la ruta de Laravel
            const vistaPrevia = "{{ route('factura.vista_previa', ':comprobante_id') }}".replace(':comprobante_id', comprobanteId);
            window.location.href = vistaPrevia;
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