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

// Esperamos a que el DOM esté completamente cargado para que el script funcione correctamente
// Esto asegura que todos los elementos del DOM estén disponibles para manipulación
// Usamos 'DOMContentLoaded' para evitar problemas de carga asíncrona
// y asegurarnos de que el script se ejecute después de que el HTML esté completamente cargados
document.addEventListener('DOMContentLoaded', function() {
    
    // Cambiar etiquetas según tipo de comprobante
    const tipoComprobanteSelect = document.getElementById('tipo_comprobante');
    if (tipoComprobanteSelect) {
        tipoComprobanteSelect.addEventListener('change', function() {
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
    }

    // FUNCIÓN UNIFICADA PARA CONSULTAR DNI O RUC CON VALIDACIÓN MEJORADA
    const consultarBtn = document.getElementById('consultarBtn');
    if (consultarBtn) {
        consultarBtn.addEventListener('click', function() {
            const documento = document.getElementById('documento');
            const tipoComprobante = document.getElementById('tipo_comprobante');
            const btn = this;
            const resultadoDiv = document.getElementById('resultadoConsulta');
            const nombreCompletoSpan = document.getElementById('nombreCompleto');
            const nombreClienteInput = document.getElementById('nombre_cliente');
            const infoRucDiv = document.getElementById('infoRuc');
            
            // Verificar que todos los elementos existen
            if (!documento || !tipoComprobante || !resultadoDiv || !nombreCompletoSpan || !nombreClienteInput || !infoRucDiv) {
                console.error('Algunos elementos del DOM no fueron encontrados');
                alert('Error interno: elementos de la interfaz no encontrados');
                return;
            }

            const documentoValue = documento.value.trim();
            const tipoComprobanteValue = tipoComprobante.value;
            
            console.log('🚀 Iniciando consulta...', { documento: documentoValue, tipoComprobante: tipoComprobanteValue });
            
            // Determinar si es DNI o RUC y validar formato
            let esValido = false;
            let endpoint = '';
            let tipoBusqueda = '';
            
            if (tipoComprobanteValue === 'boleta') {
                // Validar DNI (8 dígitos)
                esValido = /^[0-9]{8}$/.test(documentoValue);
                endpoint = '{{ route("api.consultar_dni") }}';
                tipoBusqueda = 'dni';
                
                if (!esValido) {
                    alert('Por favor ingrese un DNI válido de 8 dígitos');
                    return;
                }
            } else {
                // Validar RUC (11 dígitos, empezar con 10 o 20)
                esValido = /^(10|20)[0-9]{9}$/.test(documentoValue);
                endpoint = '{{ route("api.consultar_ruc") }}';
                tipoBusqueda = 'ruc';
                
                if (!esValido) {
                    alert('Por favor ingrese un RUC válido de 11 dígitos que empiece con 10 o 20');
                    return;
                }
            }
            
            console.log('📡 Endpoint a usar:', endpoint);
            
            // Deshabilitar botón y mostrar estado de carga
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Consultando...';
            resultadoDiv.style.display = 'none';
            infoRucDiv.style.display = 'none';
            
            // Preparar datos para enviar
            const requestData = {};
            requestData[tipoBusqueda] = documentoValue;
            
            console.log('📤 Datos a enviar:', requestData);
            
            // Hacer petición AJAX al endpoint correspondiente
            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(requestData)
            })
            .then(response => {
                console.log('📡 Response status:', response.status);
                
                // Verificar si la respuesta es JSON válida
                if (!response.ok) {
                    return response.text().then(text => {
                        console.error('❌ Error response:', text);
                        throw new Error(`HTTP error! status: ${response.status} - ${text}`);
                    });
                }
                
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        console.error('❌ Response is not JSON:', text);
                        throw new Error('La respuesta no es JSON válido');
                    });
                }
                
                return response.json();
            })
            .then(data => {
                console.log('📥 Response data completa:', data);
                
                if (data.success) {
                    console.log('✅ Consulta exitosa');
                    
                    if (tipoBusqueda === 'dni') {
                        console.log('👤 Procesando resultado DNI');
                        
                        // ✅ MANEJO CORRECTO PARA DNI
                        const nombreCompleto = data.data.nombre_completo || 'Nombre no disponible';
                        
                        nombreCompletoSpan.textContent = nombreCompleto;
                        nombreClienteInput.value = nombreCompleto;
                        infoRucDiv.style.display = 'none';
                        
                        console.log('👤 DNI procesado:', { nombre: nombreCompleto });
                        
                    } else {
                        console.log('🏢 Procesando resultado RUC');
                        console.log('🏢 Datos RUC recibidos:', data.data);
                        
                        // ✅ MANEJO CORRECTO PARA RUC - USAR ESTRUCTURA DE FACTILIZA
                        const razonSocial = data.data.razon_social || data.data.nombre_o_razon_social || 'Razón social no disponible';
                        const direccion = data.data.direccion || data.data.direccion_completa || 'Dirección no disponible';
                        const estado = data.data.estado || 'Estado no disponible';
                        const condicion = data.data.condicion || 'Condición no disponible';
                        const ruc = data.data.ruc || data.data.numero || documentoValue;
                        
                        console.log('🏢 Datos procesados:', {
                            razonSocial,
                            direccion,
                            estado,
                            condicion,
                            ruc
                        });
                        
                        // Mostrar nombre/razón social principal
                        nombreCompletoSpan.textContent = razonSocial;
                        nombreClienteInput.value = razonSocial;
                        
                        // Mostrar información adicional de la empresa
                        const direccionEmpresa = document.getElementById('direccionEmpresa');
                        const estadoEmpresa = document.getElementById('estadoEmpresa');
                        const condicionEmpresa = document.getElementById('condicionEmpresa');
                        
                        if (direccionEmpresa) {
                            direccionEmpresa.textContent = direccion;
                            console.log('📍 Dirección asignada:', direccion);
                        } else {
                            console.warn('⚠️ Elemento direccionEmpresa no encontrado');
                        }
                        
                        if (estadoEmpresa) {
                            estadoEmpresa.textContent = estado;
                            console.log('📊 Estado asignado:', estado);
                        } else {
                            console.warn('⚠️ Elemento estadoEmpresa no encontrado');
                        }
                        
                        if (condicionEmpresa) {
                            condicionEmpresa.textContent = condicion;
                            console.log('📊 Condición asignada:', condicion);
                        } else {
                            console.warn('⚠️ Elemento condicionEmpresa no encontrado');
                        }
                        
                        // Mostrar la sección de información adicional
                        infoRucDiv.style.display = 'block';
                        console.log('🏢 Información RUC mostrada correctamente');
                    }
                    
                    // Mostrar el resultado
                    resultadoDiv.style.display = 'block';
                    console.log('✅ Resultado mostrado en interfaz');
                    
                } else {
                    // Mostrar error de la API
                    console.error('❌ Error de API:', data.message);
                    alert('Error: ' + (data.message || 'Error desconocido'));
                    nombreClienteInput.value = 'Cliente';
                }
            })
            .catch(error => {
                console.error('🔴 Error completo:', error);
                console.error('🔴 Error type:', typeof error);
                console.error('🔴 Error name:', error.name);
                console.error('🔴 Error message:', error.message);
                
                alert('Error de conexión al consultar el ' + (tipoBusqueda === 'dni' ? 'DNI' : 'RUC') + '. Revisa la consola para más detalles.');
                nombreClienteInput.value = 'Cliente';
            })
            .finally(() => {
                // Restaurar botón a estado original
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-search"></i> Consultar';
                console.log('🔄 Botón restaurado');
            });
        });
    }

    // Agregar métodos de pago adicionales
    const addMetodoPago = document.getElementById('addMetodoPago');
    if (addMetodoPago) {
        addMetodoPago.addEventListener('click', function(e) {
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
            const metodosPagoExtras = document.getElementById('metodosPagoExtras');
            if (metodosPagoExtras) {
                metodosPagoExtras.appendChild(extra);
            }
        });
    }

    // Generar números aleatorios
    const randomBtn = document.getElementById('randomBtn');
    if (randomBtn) {
        randomBtn.addEventListener('click', function() {
            const tipoComprobante = document.getElementById('tipo_comprobante');
            const documento = document.getElementById('documento');
            
            if (tipoComprobante && documento) {
                const tipo = tipoComprobante.value;
                if (tipo === 'factura') {
                    // Generar RUC aleatorio válido (empezar con 20)
                    const rucAleatorio = '20' + Math.floor(Math.random() * 1000000000).toString().padStart(9, '0');
                    documento.value = rucAleatorio;
                } else {
                    // Generar DNI aleatorio
                    documento.value = Math.floor(10000000 + Math.random() * 90000000);
                }
            }
        });
    }

    // Procesar formulario de facturación
    const facturacionForm = document.getElementById('facturacionForm');
    if (facturacionForm) {
        facturacionForm.addEventListener('submit', function(e) {
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
    }

}); // Fin de DOMContentLoaded

function mostrarModalAcciones() {
    if (confirm('¿Qué quieres hacer?\nPresiona OK para Imprimir o Cancelar para Enviar al correo')) {
        // Imprimir: redirigir a la vista previa del comprobante usando la ruta de Laravel
        const vistaPrevia = "{{ route('factura.vista_previa', ':comprobante_id') }}".replace(':comprobante_id', comprobanteId);
        window.location.href = vistaPrevia;
    } else {
        // Enviar al correo
        const modalElement = document.getElementById('enviarCorreoModal');
        if (modalElement && typeof bootstrap !== 'undefined') {
            const modal = new bootstrap.Modal(modalElement);
            modal.show();
        }
    }
}

// Envío de correo (fuera del DOMContentLoaded porque se ejecuta después)
document.addEventListener('DOMContentLoaded', function() {
    const enviarCorreoBtn = document.getElementById('enviarCorreoBtn');
    if (enviarCorreoBtn) {
        enviarCorreoBtn.addEventListener('click', function() {
            const form = document.getElementById('enviarCorreoForm');
            if (form && comprobanteId) {
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
                        alert('Correo enviado exitosamente');
                        const modalElement = document.getElementById('enviarCorreoModal');
                        if (modalElement && typeof bootstrap !== 'undefined') {
                            const modal = bootstrap.Modal.getInstance(modalElement);
                            if (modal) modal.hide();
                        }
                    } else {
                        alert('Error al enviar correo: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al enviar correo');
                });
            }
        });
    }
});
</script>
@endsection