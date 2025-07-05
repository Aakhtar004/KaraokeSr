@extends('view_layout.app')

@push('styles')
<link href="{{ asset('css/mozo_pedido_vista_previa.css') }}" rel="stylesheet" media="all">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
@endpush

@section('content')

<div class="mozo-header">
    <a href="{{ route('vista.mozo_historial') }}" class="mozo-header-back">
        <span class="mozo-header-back-icon">&#8592;</span>
    </a>
    <div class="mozo-header-content">
        <div class="mozo-header-title">Vista Previa</div>
        <div class="mozo-header-subtitle">Comprobante</div>
    </div>
</div>

@php
$haySlide = isset($comprobantesDivision) && $comprobantesDivision->count() > 1;
@endphp

@if($haySlide)
    <style>
        .slide-division {
            position: fixed;
            top: 0; right: 0;
            width: 340px;
            height: 100vh;
            background: #fff;
            border-left: 2px solid #eee;
            box-shadow: -2px 0 8px rgba(0,0,0,0.07);
            z-index: 1050;
            padding: 24px 18px 18px 18px;
            overflow-y: auto;
            transition: transform 0.3s;
        }
        .slide-division.hide {
            transform: translateX(100%);
        }
        @media (max-width: 900px) {
            .slide-division { position: fixed; width: 100%; height: 60vh; bottom: 0; top: auto; border-left: none; border-top: 2px solid #eee; box-shadow: 0 -2px 8px rgba(0,0,0,0.07); }
        }
    </style>
    <div id="slideDivision" class="slide-division hide">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Comprobantes</h5>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('slideDivision').classList.add('hide')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="d-grid gap-3">
            @foreach($comprobantesDivision as $c)
                @php
                    $isActive = $c->id_comprobante == $comprobante->id_comprobante;
                    $vistaPreviaUrl = route('factura.vista_previa', $c->id_comprobante);
                @endphp
                <a href="{{ $vistaPreviaUrl }}" class="btn btn-block {{ $isActive ? 'btn-primary' : 'btn-outline-primary' }}">
                    <div class="fw-bold">{{ strtoupper($c->tipo_comprobante) }}</div>
                    <div class="small">{{ $c->nombre_razon_social_cliente ?? $c->numero_documento_cliente }}</div>
                </a>
            @endforeach
        </div>
    </div>
@else
    <div class="container-fluid p-4">
@endif
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body p-4" style="background-color: #f8f9fa;">
                    <div id="comprobante-contenido" class="bg-white p-4 border" style="min-height: 600px;">
                        <!-- Encabezado -->
                        <div class="row mb-4">
                            <div class="col-6">
                                <div class="empresa-header" style="background: #333; color: white; padding: 10px; border-radius: 5px;">
                                    <h6 class="mb-1">Restobar Salón Rojo</h6>
                                    <small>Karaoke</small>
                                </div>
                                <div class="mt-2">
                                    <small>RUC: 10255667781</small><br>
                                    <small>Dirección: Gral Deustua 160, Tacna 23001</small>
                                </div>
                            </div>
                            <div class="col-6 text-end">
                                <div class="border p-2">
                                    <strong>{{ strtoupper($comprobante->tipo_comprobante) }} ELECTRÓNICA</strong><br>
                                    <strong>{{ $comprobante->serie_comprobante }}-{{ $comprobante->numero_correlativo_comprobante }}</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Datos del cliente -->
                        <div class="mb-3">
                            <div class="row">
                                <div class="col-6">
                                    <strong>Fecha:</strong>
                                    @if($comprobante->fecha_emision instanceof \Carbon\Carbon)
                                        {{ $comprobante->fecha_emision->format('d/m/Y H:i') }}
                                    @else
                                        {{ \Carbon\Carbon::parse($comprobante->fecha_emision)->format('d/m/Y H:i') }}
                                    @endif
                                </div>
                                <div class="col-6 text-end">
                                    <strong>Mesa:</strong> {{ $comprobante->pedido->mesa->numero_mesa }}
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <strong>CLIENTE:</strong> {{ $comprobante->nombre_razon_social_cliente }}<br>
                            <strong>{{ $comprobante->tipo_documento_cliente }}:</strong> {{ $comprobante->numero_documento_cliente }}
                        </div>

                        <!-- Tabla de productos -->
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>CANT.</th>
                                    <th>DESCRIPCIÓN</th>
                                    <th>P. UNIT.</th>
                                    <th>IMPORTE</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- Si hay división de cuenta, se muestran SOLO los productos pagados por este comprobante --}}
                                @if(isset($detallesPagados) && count($detallesPagados) > 0)
                                    @foreach($detallesPagados as $detalle)
                                    <tr>
                                        <td>{{ $detalle['cantidad'] }}</td>
                                        <td>{{ $detalle['producto'] }}</td>
                                        <td>S/ {{ number_format($detalle['precio_unitario'], 2) }}</td>
                                        <td>S/ {{ number_format($detalle['subtotal'], 2) }}</td>
                                    </tr>
                                    @endforeach
                                {{-- Si NO hay división de cuenta, se muestran todos los productos del pedido --}}
                                @else
                                    @foreach($comprobante->pedido->detalles as $detalle)
                                    <tr>
                                        <td>{{ $detalle->cantidad }}</td>
                                        <td>{{ $detalle->producto->nombre }}</td>
                                        <td>S/ {{ number_format($detalle->precio_unitario_momento, 2) }}</td>
                                        <td>S/ {{ number_format($detalle->subtotal, 2) }}</td>
                                    </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>

                        <!-- Totales -->
                        <div class="row">
                            <div class="col-6"></div>
                            <div class="col-6">
                                <table class="table table-sm">
                                    <tr>
                                        <td>SUBTOTAL:</td>
                                        <td class="text-end">S/ {{ number_format($comprobante->subtotal_comprobante, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>IGV (18%):</td>
                                        <td class="text-end">S/ {{ number_format($comprobante->monto_igv, 2) }}</td>
                                    </tr>
                                    <tr class="fw-bold">
                                        <td>TOTAL:</td>
                                        <td class="text-end">S/ {{ number_format($comprobante->monto_total_comprobante, 2) }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Botones de accion -->
    <div class="row justify-content-center mt-4">
        <div class="col-md-8">
            <div class="d-flex justify-content-between">
                <a href="{{ route('vista.mozo_historial') }}" class="btn btn-dark">
                    Volver al Historial
                </a>
                <div>
                    <button type="button" class="btn btn-secondary me-2" onclick="window.print()">
                        <i class="fas fa-print"></i> Imprimir
                    </button>
                    <button type="button" class="btn btn-primary" id="btnEnviarCorreo">
                        <i class="fas fa-envelope"></i> Enviar
                    </button>
                </div>
            </div>
        </div>
    </div>

    @if($haySlide)
    <button id="btnToggleSlide" class="btn btn-primary rounded-circle shadow" style="position:fixed;bottom:32px;right:32px;z-index:1100;width:60px;height:60px;display:flex;align-items:center;justify-content:center;">
        <i class="fas fa-receipt fa-lg"></i>
    </button>
    @endif
</div>
<!-- Modal para enviar correo -->
<div class="modal fade" id="enviarCorreoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Enviar comprobante por correo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="enviarCorreoForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Correo electrónico</label>
                        <input type="email" class="form-control" id="emailInput" name="email" required>
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

<!-- Modal de confirmación -->
<div class="modal fade" id="confirmacionModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar envío</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de enviar el comprobante al correo <span id="emailConfirmacion"></span>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="confirmarEnvioBtn">Confirmar</button>
            </div>
        </div>
    </div>
</div>

@if($haySlide)
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Slide toggle
    const btnToggleSlide = document.getElementById('btnToggleSlide');
    const slideDivision = document.getElementById('slideDivision');
    if(btnToggleSlide && slideDivision) {
        btnToggleSlide.addEventListener('click', function() {
            slideDivision.classList.toggle('hide');
        });
    }
});
</script>
@endpush
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // JS para envío de correo
    const btnEnviarCorreo = document.getElementById('btnEnviarCorreo');
    const enviarCorreoModal = document.getElementById('enviarCorreoModal');
    const confirmacionModal = document.getElementById('confirmacionModal');
    const enviarCorreoBtn = document.getElementById('enviarCorreoBtn');
    const confirmarEnvioBtn = document.getElementById('confirmarEnvioBtn');
    const emailInput = document.getElementById('emailInput');
    const emailConfirmacion = document.getElementById('emailConfirmacion');
    if (
        btnEnviarCorreo && enviarCorreoModal && confirmacionModal &&
        enviarCorreoBtn && confirmarEnvioBtn && emailInput && emailConfirmacion
    ) {
        const enviarModal = new bootstrap.Modal(enviarCorreoModal);
        const confirmarModal = new bootstrap.Modal(confirmacionModal);

        btnEnviarCorreo.addEventListener('click', function() {
            enviarModal.show();
        });

        enviarCorreoBtn.addEventListener('click', function() {
            const email = emailInput.value.trim();
            if (!email) {
                alert('Por favor, ingrese un correo electrónico válido');
                return;
            }
            emailConfirmacion.textContent = email;
            enviarModal.hide();
            confirmarModal.show();
        });

        confirmarEnvioBtn.addEventListener('click', function() {
            confirmarEnvioBtn.disabled = true;
            confirmarEnvioBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enviando...';

            const email = emailInput.value.trim();
            const comprobanteId = "{{ $comprobante->id_comprobante }}";
            const dniCorreo = "{{ $comprobante->numero_documento_cliente }}";

            const formData = new FormData();
            formData.append('email', email);
            formData.append('dni_correo', dniCorreo);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

            const enviarUrl = "{{ route('factura.enviar_correo', ':comprobante_id') }}".replace(':comprobante_id', comprobanteId);

            const modalBody = document.querySelector('#confirmacionModal .modal-body');
            modalBody.innerHTML = `
                <div class="text-center">
                    <p>Enviando correo a ${email}...</p>
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
            `;

            fetch(enviarUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            .then(response => response.json())
            .then(data => {
                confirmarEnvioBtn.disabled = false;
                confirmarEnvioBtn.innerHTML = 'Confirmar';

                if (data.success) {
                    modalBody.innerHTML = `
                        <div class="text-center text-success">
                            <i class="fas fa-check-circle fa-3x mb-3"></i>
                            <p>¡Correo enviado exitosamente a ${email}!</p>
                        </div>
                    `;
                    document.querySelector('#confirmacionModal .modal-footer').innerHTML = `
                        <button type="button" class="btn btn-success" data-bs-dismiss="modal">Cerrar</button>
                    `;
                } else {
                    modalBody.innerHTML = `
                        <div class="text-center text-danger">
                            <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
                            <p>Error al enviar correo: ${data.message || 'Error desconocido'}</p>
                        </div>
                    `;
                    document.querySelector('#confirmacionModal .modal-footer').innerHTML = `
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-primary" id="reintentar">Reintentar</button>
                    `;
                    document.getElementById('reintentar').addEventListener('click', function() {
                        confirmarModal.hide();
                        setTimeout(() => {
                            enviarModal.show();
                        }, 500);
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                confirmarEnvioBtn.disabled = false;
                confirmarEnvioBtn.innerHTML = 'Confirmar';
                modalBody.innerHTML = `
                    <div class="text-center text-danger">
                        <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                        <p>Error de conexión. Por favor, inténtelo de nuevo.</p>
                    </div>
                `;
                document.querySelector('#confirmacionModal .modal-footer').innerHTML = `
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="reintentar">Reintentar</button>
                `;
                document.getElementById('reintentar').addEventListener('click', function() {
                    confirmarModal.hide();
                    setTimeout(() => {
                        enviarModal.show();
                    }, 500);
                });
            });
        });
    }
});
</script>
@endpush

<!-- {{-- DEBUG: Mostrar IDs de comprobantes asociados a este pedido --}}
@php
    if(isset($comprobantesDivision)) {
        echo '<pre>Comprobantes para el pedido: ';
        foreach($comprobantesDivision as $c) echo $c->id_comprobante . ' ';
        echo '</pre>';
    }
@endphp -->

@endsection