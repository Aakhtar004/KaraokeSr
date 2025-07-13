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
    <form id="facturacionForm" action="{{ route('pedidos.procesar_facturacion', $pedido->id_pedido) }}" method="POST" class="w-100" style="max-width: 600px;">
        @csrf

        <!-- Botón Dividir Cuenta -->
        <div class="mb-3 text-center">
            <button type="button" class="btn btn-outline-primary" id="toggleDividirCuenta">
                <i class="fas fa-columns"></i> Dividir Cuenta
            </button>
        </div>

        <div id="formSinDivision">
            <div class="card mb-3">
                <div class="card-body">
                    <h6>Mesa: {{ $pedido->mesa->numero_mesa }}</h6>
                    <h6>Total: S/ {{ number_format($pedido->total_pedido, 2) }}</h6>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Tipo de comprobante</label>
                <select class="form-select" name="tipo_comprobante" id="tipo_comprobante" required>
                    <option value="boleta">Boleta</option>
                    <option value="nota_venta">Nota de Venta</option>
                </select>
            </div>
            <div class="mb-3" id="campoDocumentoContainer">
                <label id="labelDocumento" class="form-label">Boleta</label>
                <div class="input-group mb-2">
                    <span class="input-group-text" id="tipoDoc">DNI</span>
                    <input type="text" class="form-control" id="documento" name="documento" placeholder="Ingrese DNI">
                    <button class="btn btn-outline-secondary" type="button" id="consultarBtn">
                        <i class="bi bi-search"></i> Consultar
                    </button>
                </div>
                <div id="resultadoConsulta" style="display:none;">
                    <span>Nombre: <span id="nombreCompleto"></span></span>
                </div>
                <input type="text" class="form-control mt-2" id="nombre_cliente" name="nombre_cliente" placeholder="Nombre del cliente" required>
                <div id="infoRuc" style="display:none;" class="mt-2">
                    <div><strong>Dirección:</strong> <span id="direccionEmpresa"></span></div>
                    <div><strong>Estado:</strong> <span id="estadoEmpresa"></span></div>
                    <div><strong>Condición:</strong> <span id="condicionEmpresa"></span></div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Método de pago</label>
                <select class="form-select" name="metodo_pago[]" required>
                    <option value="">Seleccione</option>
                    <option value="efectivo">Efectivo</option>
                    <option value="tarjeta">Tarjeta</option>
                    <option value="yape">Yape</option>
                    <option value="plin">Plin</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Monto</label>
                <input type="number" class="form-control" name="monto_pago[]" value="{{ $pedido->total_pedido }}" readonly>
            </div>
        </div>

        <div id="formDivision" style="display:none;">
            <div class="card mb-3">
                <div class="card-body">
                    <h6>Mesa: {{ $pedido->mesa->numero_mesa }}</h6>
                    <h6>Total: S/ {{ number_format($pedido->total_pedido, 2) }}</h6>
                </div>
            </div>
            <div class="mb-3">
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="modoDivision" id="divisionPorItem" value="item" checked>
                    <label class="form-check-label" for="divisionPorItem">Dividir por ítem</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="modoDivision" id="divisionPorMonto" value="monto">
                    <label class="form-check-label" for="divisionPorMonto">Dividir por monto</label>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">¿En cuántas cuentas se divide?</label>
                <input type="number" min="2" max="10" class="form-control" id="numCuentas" name="num_cuentas" value="2">
            </div>
            <div class="mb-3 text-center">
                <button type="button" class="btn btn-primary" id="generarDivisionesBtn" disabled>Generar divisiones</button>
            </div>
            <div id="bloquesDivision"></div>
            <div id="tablaDivisionItemsContainer" style="display:none;" class="mt-3"></div>
            <div id="resumenDivision" class="mt-3"></div>
        </div>

        <input type="hidden" name="division" id="inputDivision" value="0">

        <div class="d-grid mt-4">
            <button type="submit" class="btn btn-dark" id="btnConfirmar" disabled>Confirmar</button>
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

<!-- Modal de éxito comprobante generado -->
<div class="modal fade" id="modalExitoComprobante" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">¡Éxito!</h5>
            </div>
            <div class="modal-body">
                <p>Comprobante generado correctamente.</p>
            </div>
            <div class="modal-footer">
                <button type="button" id="btnAceptarExito" class="btn btn-primary" data-bs-dismiss="modal">Aceptar</button>
            </div>
        </div>
    </div>
</div>

<span id="productosPedidoData" data-productos='@json($productosPedido)'></span>

<script>
const productosPedido = JSON.parse(document.getElementById('productosPedidoData').dataset.productos);

let comprobanteId = null;

document.addEventListener('DOMContentLoaded', function() {
    const tipoComprobanteSelect = document.getElementById('tipo_comprobante');
    const labelDocumento = document.getElementById('labelDocumento');
    const tipoDoc = document.getElementById('tipoDoc');
    const documento = document.getElementById('documento');
    const consultarBtn = document.getElementById('consultarBtn');
    const resultadoConsulta = document.getElementById('resultadoConsulta');
    const infoRuc = document.getElementById('infoRuc');
    const nombreCliente = document.getElementById('nombre_cliente');
    const grupoDni = tipoDoc ? tipoDoc.closest('.input-group') : null;

    if (tipoComprobanteSelect) {
        tipoComprobanteSelect.addEventListener('change', function() {
            const tipo = this.value;

            if (tipo === 'nota_venta') {
                if (grupoDni) grupoDni.style.display = 'none';
                if (resultadoConsulta) resultadoConsulta.style.display = 'none';
                if (infoRuc) infoRuc.style.display = 'none';
                if (nombreCliente) {
                    nombreCliente.value = '';
                    nombreCliente.readOnly = false;
                    nombreCliente.placeholder = 'Nombre del cliente';
                    nombreCliente.style.display = '';
                }
                if (labelDocumento) labelDocumento.textContent = 'Nombre';
                
                // AGREGAR: Validar después de cambiar a nota de venta
                setTimeout(function() {
                    if (formDivision && formDivision.style.display === 'none') {
                        const btnConfirmar = document.getElementById('btnConfirmar');
                        const metodoPago = document.querySelector('select[name="metodo_pago[]"]');
                        let esValido = tipoComprobanteSelect.value && metodoPago && metodoPago.value;
                        if (btnConfirmar) btnConfirmar.disabled = !esValido;
                    }
                }, 50);
            } else {
                if (grupoDni) grupoDni.style.display = '';
                
                if (tipoDoc) tipoDoc.textContent = 'DNI';
                if (documento) {
                    documento.placeholder = 'Ingrese DNI';
                    documento.value = '';
                }
                if (consultarBtn) consultarBtn.disabled = false;
                
                if (nombreCliente) {
                    nombreCliente.value = '';
                    nombreCliente.readOnly = false;
                    nombreCliente.placeholder = 'Nombre del cliente';
                    nombreCliente.style.display = '';
                }
                if (labelDocumento) labelDocumento.textContent = 'Boleta';
                
                // AGREGAR: Validar después de cambiar a boleta
                setTimeout(function() {
                    if (formDivision && formDivision.style.display === 'none') {
                        const btnConfirmar = document.getElementById('btnConfirmar');
                        const metodoPago = document.querySelector('select[name="metodo_pago[]"]');
                        let esValido = tipoComprobanteSelect.value && metodoPago && metodoPago.value;
                        if (btnConfirmar) btnConfirmar.disabled = !esValido;
                    }
                }, 50);
            }
        });
    }

    if (consultarBtn) {
        consultarBtn.addEventListener('click', function() {
            const documento = document.getElementById('documento');
            const tipoComprobante = document.getElementById('tipo_comprobante');
            const btn = this;
            const resultadoDiv = document.getElementById('resultadoConsulta');
            const nombreCompletoSpan = document.getElementById('nombreCompleto');
            const nombreClienteInput = document.getElementById('nombre_cliente');
            const infoRucDiv = document.getElementById('infoRuc');
            
            if (!documento || !tipoComprobante || !resultadoDiv || !nombreCompletoSpan || !nombreClienteInput || !infoRucDiv) {
                console.error('Algunos elementos del DOM no fueron encontrados');
                alert('Error interno: elementos de la interfaz no encontrados');
                return;
            }

            const documentoValue = documento.value.trim();
            const tipoComprobanteValue = tipoComprobante.value;
            
            console.log('Iniciando consulta...', { documento: documentoValue, tipoComprobante: tipoComprobanteValue });
            
            let esValido = false;
            let endpoint = '';
            let tipoBusqueda = '';
            
            if (tipoComprobanteValue === 'boleta') {
                esValido = /^[0-9]{8}$/.test(documentoValue);
                endpoint = '{{ route("api.consultar_dni") }}';
                tipoBusqueda = 'dni';
                
                if (!esValido) {
                    alert('Por favor ingrese un DNI válido de 8 dígitos');
                    return;
                }
            } else {
                esValido = /^(10|20)[0-9]{9}$/.test(documentoValue);
                endpoint = '{{ route("api.consultar_ruc") }}';
                tipoBusqueda = 'ruc';
                
                if (!esValido) {
                    alert('Por favor ingrese un RUC válido de 11 dígitos que empiece con 10 o 20');
                    return;
                }
            }
            
            console.log('📡 Endpoint a usar:', endpoint);
            
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Consultando...';
            resultadoDiv.style.display = 'none';
            infoRucDiv.style.display = 'none';
            
            const requestData = {};
            requestData[tipoBusqueda] = documentoValue;
            
            console.log('📤 Datos a enviar:', requestData);
            
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
                
                if (!response.ok) {
                    return response.text().then(text => {
                        console.error('Error response:', text);
                        throw new Error(`HTTP error! status: ${response.status} - ${text}`);
                    });
                }
                
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        console.error('Response is not JSON:', text);
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
                        
                        const nombreCompleto = data.data.nombre_completo || 'Nombre no disponible';
                        
                        nombreCompletoSpan.textContent = nombreCompleto;
                        nombreClienteInput.value = nombreCompleto;
                        nombreClienteInput.readOnly = true;

                        infoRucDiv.style.display = 'none';
                        
                        console.log('👤 DNI procesado:', { nombre: nombreCompleto });
                        
                    } else {
                        console.log('Procesando resultado RUC');
                        console.log('Datos RUC recibidos:', data.data);
                        
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
                        
                        nombreCompletoSpan.textContent = razonSocial;
                        nombreClienteInput.value = razonSocial;
                        nombreClienteInput.readOnly = true;
                        
                        const direccionEmpresa = document.getElementById('direccionEmpresa');
                        const estadoEmpresa = document.getElementById('estadoEmpresa');
                        const condicionEmpresa = document.getElementById('condicionEmpresa');
                        
                        if (direccionEmpresa) {
                            direccionEmpresa.textContent = direccion;
                            console.log('Dirección asignada:', direccion);
                        } else {
                            console.warn('Elemento direccionEmpresa no encontrado');
                        }
                        
                        if (estadoEmpresa) {
                            estadoEmpresa.textContent = estado;
                            console.log('Estado asignado:', estado);
                        } else {
                            console.warn('Elemento estadoEmpresa no encontrado');
                        }
                        
                        if (condicionEmpresa) {
                            condicionEmpresa.textContent = condicion;
                            console.log('Condición asignada:', condicion);
                        } else {
                            console.warn('Elemento condicionEmpresa no encontrado');
                        }
                        
                        infoRucDiv.style.display = 'block';
                        console.log(' Información RUC mostrada correctamente');
                    }
                    
                    resultadoDiv.style.display = 'block';
                    console.log('Resultado mostrado en interfaz');
                    
                    // AGREGAR: Hacer readonly después de consulta exitosa y validar
                    nombreClienteInput.readOnly = true;
                    if (formDivision && formDivision.style.display === 'none') {
                        const tipoComprobante = document.getElementById('tipo_comprobante');
                        const metodoPago = document.querySelector('select[name="metodo_pago[]"]');
                        const btnConfirmar = document.getElementById('btnConfirmar');
                        
                        let esValido = tipoComprobante && tipoComprobante.value && metodoPago && metodoPago.value;
                        if (tipoComprobante.value === 'boleta' || tipoComprobante.value === 'nota_venta') {
                            esValido = esValido && nombreClienteInput && nombreClienteInput.value.trim();
                        }
                        
                        if (btnConfirmar) btnConfirmar.disabled = !esValido;
                    }
                    
                } else {
                    console.error('❌ Error de API:', data.message);
                    alert('Error: ' + (data.message || 'Error desconocido'));
                    nombreClienteInput.value = 'Cliente';
                    nombreClienteInput.readOnly = false; // Permitir edición manual si falla
                }
            })
            .catch(error => {
                console.error('🔴 Error completo:', error);
                console.error('🔴 Error type:', typeof error);
                console.error('🔴 Error name:', error.name);
                console.error('🔴 Error message:', error.message);
                
                alert('Error de conexión al consultar el ' + (tipoBusqueda === 'dni' ? 'DNI' : 'RUC') + '. Revisa la consola para más detalles.');
                nombreClienteInput.value = 'Cliente';
                nombreClienteInput.readOnly = false; // Permitir edición manual si hay error
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-search"></i> Consultar';
                console.log('🔄 Botón restaurado');
            });
        });
    }

    const toggleDividirCuenta = document.getElementById('toggleDividirCuenta');
    const inputDivision = document.getElementById('inputDivision');
    const formDivision = document.getElementById('formDivision');
    const formSinDivision = document.getElementById('formSinDivision');
    if (toggleDividirCuenta && formDivision && formSinDivision) {
        // AGREGAR: Inicialización correcta al cargar la página
        const numCuentas = document.getElementById('numCuentas');
        if (numCuentas) numCuentas.required = false; // Inicialmente sin required
        
        toggleDividirCuenta.addEventListener('click', function() {
            const estaDividido = inputDivision.value === '1';
            if (estaDividido) {
                // Cambiar a modo SIN división
                inputDivision.value = '0';
                formDivision.style.display = 'none';
                formSinDivision.style.display = '';
                document.querySelectorAll('#formSinDivision [name="nombre_cliente"], #formSinDivision [name="metodo_pago[]"]').forEach(el => el.required = true);
                document.querySelectorAll('#formDivision [required]').forEach(el => el.required = false);
                // CRÍTICO: Quitar required del campo num_cuentas
                if (numCuentas) {
                    numCuentas.required = false;
                    numCuentas.removeAttribute('required');
                }
            } else {
                // Cambiar a modo CON división
                inputDivision.value = '1';
                formDivision.style.display = '';
                formSinDivision.style.display = 'none';
                document.querySelectorAll('#formSinDivision [required]').forEach(el => el.required = false);
                // SOLO agregar required cuando realmente se muestra la división
                if (numCuentas) {
                    numCuentas.required = true;
                    numCuentas.setAttribute('required', 'required');
                }
            }
            
            // AGREGAR: Revalidar después del toggle
            setTimeout(function() {
                if (formDivision.style.display === 'none') {
                    const tipoComprobante = document.getElementById('tipo_comprobante');
                    const metodoPago = document.querySelector('select[name="metodo_pago[]"]');
                    const btnConfirmar = document.getElementById('btnConfirmar');
                    let esValido = tipoComprobante && tipoComprobante.value && metodoPago && metodoPago.value;
                    if (btnConfirmar) btnConfirmar.disabled = !esValido;
                }
            }, 100);
        });
    }

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

    const randomBtn = document.getElementById('randomBtn');
    if (randomBtn) {
        randomBtn.addEventListener('click', function() {
            const tipoComprobante = document.getElementById('tipo_comprobante');
            const documento = document.getElementById('documento');
            
            if (tipoComprobante && documento) {
                const tipo = tipoComprobante.value;
                if (tipo === 'factura') {
                    const rucAleatorio = '20' + Math.floor(Math.random() * 1000000000).toString().padStart(9, '0');
                    documento.value = rucAleatorio;
                } else {
                    documento.value = Math.floor(10000000 + Math.random() * 90000000);
                }
            }
        });
    }

    const facturacionForm = document.getElementById('facturacionForm');
    if (facturacionForm) {
        facturacionForm.addEventListener('submit', function(e) {
            e.preventDefault(); 
            if (inputDivision.value === '1') {
                let valido = true;
                document.querySelectorAll('.tipo-comprobante-division').forEach(function(select) {
                    const tipo = select.value;
                    const idx = select.getAttribute('data-idx');
                    if (tipo === 'nota_venta') {
                        const inputNombre = document.querySelector(`#campoNombreDivision${idx} input`);
                        if (!inputNombre.value.trim()) {
                            valido = false;
                            inputNombre.classList.add('is-invalid');
                        } else {
                            inputNombre.classList.remove('is-invalid');
                        }
                    }
                });
                if (!valido) {
                    e.preventDefault();
                    alert('Debes ingresar el nombre para cada cliente de nota de venta.');
                    return false;
                }
            }
            
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(async response => {
                let data;
                try {
                    data = await response.json();
                } catch (e) {
                    alert('Error inesperado en la respuesta del servidor');
                    return;
                }
                if (data.comprobante_id) {
                    comprobanteId = data.comprobante_id;
                    mostrarModalAcciones();
                } else if (data.success && data.redirect_url) {
                    window.location.href = data.redirect_url;
                } else {
                    alert('Error: ' + (data.message || 'Error desconocido'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al procesar la facturación');
            });
        });
    }

    const numCuentasInput = document.getElementById('numCuentas');
    const generarDivisionesBtn = document.getElementById('generarDivisionesBtn');
    const modoDivisionRadios = document.querySelectorAll('input[name="modoDivision"]');
    const bloquesDivision = document.getElementById('bloquesDivision');

    if (numCuentasInput && generarDivisionesBtn) {
        numCuentasInput.addEventListener('input', function() {
            generarDivisionesBtn.disabled = !(parseInt(numCuentasInput.value) >= 2 && parseInt(numCuentasInput.value) <= 10);
        });
    }

    if (modoDivisionRadios && bloquesDivision) {
        modoDivisionRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                bloquesDivision.innerHTML = '';
            });
        });
    }

    if (generarDivisionesBtn && numCuentasInput && bloquesDivision) {
        generarDivisionesBtn.addEventListener('click', function() {
            const n = parseInt(numCuentasInput.value);
            if (isNaN(n) || n < 2 || n > 10) return;
            const modo = document.querySelector('input[name="modoDivision"]:checked').value;
            bloquesDivision.innerHTML = '';
            document.getElementById('tablaDivisionItemsContainer').style.display = 'none';

            if (modo === 'item') {
                renderTablaDivisionItems(n);
            } else {

                for (let i = 1; i <= n; i++) {
                    bloquesDivision.innerHTML += `
                        <div class="card mb-2">
                            <div class="card-body">
                                <h6>Cuenta ${i}</h6>
                                <div class="mb-2">
                                    <label>Tipo de comprobante</label>
                                    <select class="form-select tipo-comprobante-division" name="divisiones[${i-1}][tipo_comprobante]" data-idx="${i-1}">
                                        <option value="boleta">Boleta</option>
                                        <option value="nota_venta">Nota de Venta</option>
                                    </select>
                                </div>
                                <div class="mb-2 campo-dni-division" id="campoDniDivision${i-1}">
                                    <div class="input-group mb-2">
                                        <span class="input-group-text">DNI</span>
                                        <input type="text" class="form-control dni-division" name="divisiones[${i-1}][dni]" placeholder="Ingrese DNI" data-idx="${i-1}">
                                        <button class="btn btn-outline-secondary btn-consultar-dni" type="button" data-idx="${i-1}">
                                            <i class="bi bi-search"></i> Consultar
                                        </button>
                                    </div>
                                    <div class="mb-2">
                                        <input type="text" class="form-control nombre-boleta-division" name="divisiones[${i-1}][nombre_boleta]" placeholder="Nombre del cliente" data-idx="${i-1}" readonly>
                                    </div>
                                </div>
                                <div class="mb-2 campo-nombre-division" id="campoNombreDivision${i-1}" style="display:none;">
                                    <input type="text" class="form-control nombre-notaventa-division" name="divisiones[${i-1}][nombre_notaventa]" placeholder="Nombre del cliente" data-idx="${i-1}" required>
                                </div>
                                <div class="mb-2">
                                    <label>Método de pago</label>
                                    <select class="form-select" name="divisiones[${i-1}][metodo_pago]" required>
                                        <option value="">Seleccione</option>
                                        <option value="efectivo">Efectivo</option>
                                        <option value="tarjeta">Tarjeta</option>
                                        <option value="yape">Yape</option>
                                        <option value="plin">Plin</option>
                                    </select>
                                </div>
                                <div class="mb-2">
                                    <label>Monto</label>
                                    <input type="number" class="form-control" name="divisiones[${i-1}][monto]" min="0.01" step="0.01" required>
                                </div>
                            </div>
                        </div>
                    `;
                }

            }


        });
    }

    function renderTablaDivisionItems(nCuentas) {
        const container = document.getElementById('tablaDivisionItemsContainer');
        if (!container) return;
        container.innerHTML = '';

        let html = `<table class="table table-bordered align-middle text-center">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Precio Unitario</th>`;
        for (let i = 1; i <= nCuentas; i++) {
            html += `<th>Cliente ${i}</th>`;
        }
        html += `</tr></thead><tbody>`;

        productosPedido.forEach((prod, idx) => {
            for (let q = 1; q <= prod.cantidad; q++) {
                html += `<tr>
                    <td>${prod.nombre}</td>
                    <td>S/ ${parseFloat(prod.precio).toFixed(2)}</td>`;
                for (let c = 0; c < nCuentas; c++) {
                    html += `<td>
                        <input type="checkbox" class="chk-prod-cuenta" 
                            data-prod="${prod.id}" data-idx="${idx}" data-cuenta="${c}" data-q="${q}"
                            name="chk_${prod.id}_${q}_${c}">
                    </td>`;
                }
                html += `</tr>`;
            }
        });

        html += `</tbody></table>`;

        html += `<div class="row mt-3" id="datosClientesDivision">`;
        for (let c = 0; c < nCuentas; c++) {
            html += `
            <div class="col">
                <div class="card">
                    <div class="card-body">
                        <h6>Cliente ${c+1}</h6>
                        <div class="mb-2">
                            <label>Tipo de comprobante</label>
                            <select class="form-select tipo-comprobante-division" name="divisiones[${c}][tipo_comprobante]" data-idx="${c}">
                                <option value="boleta">Boleta</option>
                                <option value="nota_venta">Nota de Venta</option>
                            </select>
                        </div>
                        <div class="mb-2 campo-dni-division" id="campoDniDivision${c}">
                            <div class="input-group mb-2">
                                <span class="input-group-text">DNI</span>
                                <input type="text" class="form-control dni-division" name="divisiones[${c}][dni]" placeholder="Ingrese DNI" data-idx="${c}">
                                <button class="btn btn-outline-secondary btn-consultar-dni" type="button" data-idx="${c}">
                                    <i class="bi bi-search"></i> Consultar
                                </button>
                            </div>
                            <div class="mb-2">
                                <input type="text" class="form-control nombre-boleta-division" name="divisiones[${c}][nombre_boleta]" placeholder="Nombre del cliente" data-idx="${c}" readonly>
                            </div>
                        </div>
                        <div class="mb-2 campo-nombre-division" id="campoNombreDivision${c}" style="display:none;">
                            <input type="text" class="form-control nombre-notaventa-division" name="divisiones[${c}][nombre_notaventa]" placeholder="Nombre del cliente" data-idx="${c}">
                        </div>
                        <div class="mb-2">
                            <label>Método de pago</label>
                            <select class="form-select" name="divisiones[${c}][metodo_pago]" required>
                                <option value="">Seleccione</option>
                                <option value="efectivo">Efectivo</option>
                                <option value="tarjeta">Tarjeta</option>
                                <option value="yape">Yape</option>
                                <option value="plin">Plin</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label>Monto</label>
                            <input type="number" class="form-control monto-division" name="divisiones[${c}][monto]" min="0.01" step="0.01" readonly>
                        </div>
                    </div>
                </div>
            </div>`;
        }
        html += `</div>
        <div class="text-danger mt-2" id="errorDivisionItems"></div>`;

        container.innerHTML = html;
        container.style.display = '';

        document.querySelectorAll('.tipo-comprobante-division').forEach(function(select) {
            select.addEventListener('change', function() {
                const idx = this.getAttribute('data-idx');
                const tipo = this.value;
                const campoDni = document.getElementById('campoDniDivision' + idx);
                const campoNombre = document.getElementById('campoNombreDivision' + idx);
                const inputNombre = campoNombre.querySelector('input');
                if (tipo === 'boleta') {
                    campoDni.style.display = '';
                    campoNombre.style.display = 'none';
                    inputNombre.required = false;
                } else {
                    campoDni.style.display = 'none';
                    campoNombre.style.display = '';
                    inputNombre.required = true;
                }
            });
            select.dispatchEvent(new Event('change'));
        });

        document.querySelectorAll('.btn-consultar-dni').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const idx = this.getAttribute('data-idx');
                const inputDni = document.querySelector('.dni-division[data-idx="'+idx+'"]');
                const inputNombre = document.querySelector('.nombre-boleta-division[data-idx="'+idx+'"]');
                const dni = inputDni.value.trim();
                if (!/^[0-9]{8}$/.test(dni)) {
                    alert('Ingrese un DNI válido de 8 dígitos');
                    return;
                }
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Consultando...';
                fetch('{{ route("api.consultar_dni") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector("meta[name='csrf-token']").getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ dni: dni })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data && data.data.nombre_completo) {
                        inputNombre.value = data.data.nombre_completo;
                    } else {
                        inputNombre.value = '';
                        alert('No se encontró el DNI');
                    }
                })
                .catch(() => {
                    inputNombre.value = '';
                    alert('Error al consultar el DNI');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-search"></i> Consultar';
                });
            });
        });

        document.querySelectorAll('.chk-prod-cuenta').forEach(chk => {
            chk.addEventListener('change', function() {
                const prod = this.getAttribute('data-prod');
                const idx = this.getAttribute('data-idx');
                const q = this.getAttribute('data-q');
                if (this.checked) {
                    document.querySelectorAll(`.chk-prod-cuenta[data-prod="${prod}"][data-idx="${idx}"][data-q="${q}"]`).forEach(otherChk => {
                        if (otherChk !== this) otherChk.checked = false;
                    });
                }
                calcularMontosYValidar(nCuentas);
            });
        });

        calcularMontosYValidar(nCuentas);
    }

    function calcularMontosYValidar(nCuentas) {
        let error = '';
        let montos = Array(nCuentas).fill(0);

        productosPedido.forEach((prod, idx) => {
            for (let q = 1; q <= prod.cantidad; q++) {
                let cuentaMarcada = -1;
                let cuentaMarcadas = 0;
                for (let c = 0; c < nCuentas; c++) {
                    const chk = document.querySelector(`.chk-prod-cuenta[data-prod="${prod.id}"][data-idx="${idx}"][data-cuenta="${c}"][data-q="${q}"]`);
                    if (chk && chk.checked) {
                        cuentaMarcada = c;
                        cuentaMarcadas++;
                    }
                }
                if (cuentaMarcadas === 0) {
                    error = `Cada producto debe estar asignado a un cliente.`;
                }
                if (cuentaMarcadas > 1) {
                    error = `Un producto solo puede ser asignado a un cliente.`;
                }
                if (cuentaMarcada >= 0) {
                    montos[cuentaMarcada] += parseFloat(prod.precio);
                }
            }
        });

        document.querySelectorAll('.monto-division').forEach((input, idx) => {
            input.value = montos[idx].toFixed(2);
        });

        document.getElementById('errorDivisionItems').textContent = error;
        document.getElementById('btnConfirmar').disabled = !!error;
    }

    if (generarDivisionesBtn && numCuentasInput) {
        generarDivisionesBtn.addEventListener('click', function() {
            const n = parseInt(numCuentasInput.value);
            if (document.querySelector('input[name="modoDivision"]:checked').value === 'item') {
                renderTablaDivisionItems(n);
            } else {
                document.getElementById('tablaDivisionItemsContainer').style.display = 'none';
            }
        });
    };

    const btnConfirmar = document.getElementById('btnConfirmar');
    const enviarCorreoBtn = document.getElementById('enviarCorreoBtn');

    if (facturacionForm && btnConfirmar) {
        facturacionForm.addEventListener('input', function() {
            if (formDivision && formDivision.style.display === 'none') {
                const tipoComprobante = document.getElementById('tipo_comprobante');
                const metodoPago = document.querySelector('select[name="metodo_pago[]"]');
                const documento = document.getElementById('documento');
                const nombreCliente = document.getElementById('nombre_cliente');
                
                let esValido = true;
                
                if (!tipoComprobante || !tipoComprobante.value || !metodoPago || !metodoPago.value) {
                    esValido = false;
                }
                
                if (tipoComprobante && tipoComprobante.value) {
                    if (tipoComprobante.value === 'boleta') {
                        if (documento && documento.value.trim() !== '') {
                            if (!/^[0-9]{8}$/.test(documento.value.trim())) {
                                esValido = false;
                            }
                        }
                        if (!nombreCliente || !nombreCliente.value.trim()) {
                            esValido = false;
                        }
                    } else if (tipoComprobante.value === 'nota_venta') {
                        if (!nombreCliente || !nombreCliente.value.trim()) {
                            esValido = false;
                        }
                    }
                }
                
                btnConfirmar.disabled = !esValido;
            }
        });
        
        facturacionForm.addEventListener('change', function() {
            if (formDivision && formDivision.style.display === 'none') {
                const tipoComprobante = document.getElementById('tipo_comprobante');
                const metodoPago = document.querySelector('select[name="metodo_pago[]"]');
                const documento = document.getElementById('documento');
                const nombreCliente = document.getElementById('nombre_cliente');
                
                let esValido = true;
                
                if (!tipoComprobante || !tipoComprobante.value || !metodoPago || !metodoPago.value) {
                    esValido = false;
                }
                
                if (tipoComprobante && tipoComprobante.value) {
                    if (tipoComprobante.value === 'boleta') {
                        if (documento && documento.value.trim() !== '') {
                            if (!/^[0-9]{8}$/.test(documento.value.trim())) {
                                esValido = false;
                            }
                        }
                        if (!nombreCliente || !nombreCliente.value.trim()) {
                            esValido = false;
                        }
                    } else if (tipoComprobante.value === 'nota_venta') {
                        if (!nombreCliente || !nombreCliente.value.trim()) {
                            esValido = false;
                        }
                    }
                }
                
                btnConfirmar.disabled = !esValido;
            }
        });
        
        if (formDivision && formDivision.style.display === 'none') {
            const tipoComprobante = document.getElementById('tipo_comprobante');
            const metodoPago = document.querySelector('select[name="metodo_pago[]"]');
            btnConfirmar.disabled = !(tipoComprobante && tipoComprobante.value && metodoPago && metodoPago.value);
        }
    }
    
    document.querySelectorAll('.tipo-comprobante-division').forEach(function(select) {
        select.addEventListener('change', function() {
            const idx = this.getAttribute('data-idx');
            const tipo = this.value;
            const campoDni = document.getElementById('campoDniDivision' + idx);
            const campoNombre = document.getElementById('campoNombreDivision' + idx);
            const inputNombre = campoNombre.querySelector('input');
            if (tipo === 'boleta') {
                campoDni.style.display = '';
                campoNombre.style.display = 'none';
                inputNombre.required = false;
            } else {
                campoDni.style.display = 'none';
                campoNombre.style.display = '';
                inputNombre.required = true;
            }
        });
        select.dispatchEvent(new Event('change'));
    });

    document.querySelectorAll('.btn-consultar-dni').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const idx = this.getAttribute('data-idx');
            const inputDni = document.querySelector('.dni-division[data-idx="'+idx+'"]');
            const inputNombre = document.querySelector('.nombre-boleta-division[data-idx="'+idx+'"]');
            const dni = inputDni.value.trim();
            if (!/^[0-9]{8}$/.test(dni)) {
                alert('Ingrese un DNI válido de 8 dígitos');
                return;
            }
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Consultando...';
            fetch('{{ route("api.consultar_dni") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector("meta[name='csrf-token']").getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ dni: dni })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data && data.data.nombre_completo) {
                    inputNombre.value = data.data.nombre_completo;
                } else {
                    inputNombre.value = '';
                    alert('No se encontró el DNI');
                }
            })
            .catch(() => {
                inputNombre.value = '';
                alert('Error al consultar el DNI');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-search"></i> Consultar';
            });
        });
    });

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
    // Asegurar que num_cuentas no tenga required inicialmente
    const numCuentasInit = document.getElementById('numCuentas');
    if (numCuentasInit) {
        numCuentasInit.required = false;
        numCuentasInit.removeAttribute('required');
        console.log('✅ Required removido de num_cuentas');
    }

    // También asegurar que esté sin required en la inicialización
    setTimeout(function() {
        const numCuentas2 = document.getElementById('numCuentas');
        if (numCuentas2 && numCuentas2.hasAttribute('required')) {
            numCuentas2.required = false;
            numCuentas2.removeAttribute('required');
            console.log('✅ Required removido de num_cuentas (verificación)');
        }
    }, 100);

});


function mostrarModalAcciones() {
    const modalElement = document.getElementById('modalExitoComprobante');
    if (modalElement && typeof bootstrap !== 'undefined') {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();

        modalElement.addEventListener('hidden.bs.modal', function handler() {
            const vistaPrevia = "{{ route('factura.vista_previa', ':comprobante_id') }}".replace(':comprobante_id', comprobanteId);
            window.location.href = vistaPrevia;
            modalElement.removeEventListener('hidden.bs.modal', handler);
        });

        document.getElementById('btnAceptarExito').onclick = function() {
            modal.hide();
        };
    } else {
        const vistaPrevia = "{{ route('factura.vista_previa', ':comprobante_id') }}".replace(':comprobante_id', comprobanteId);
        window.location.href = vistaPrevia;
    }
}


</script>
@endsection