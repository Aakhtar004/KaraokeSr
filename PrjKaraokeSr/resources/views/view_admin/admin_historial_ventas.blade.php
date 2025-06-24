@extends('view_layout.app')

@section('content')
<link href="{{ asset('css/admin_historial.css') }}" rel="stylesheet">
<!-- Header personalizado -->
<div class="custom-header">
    <a href="{{ route('vista.user_menu') }}" class="back-button">
        <img src="{{ asset('images/izquierda.png') }}" alt="Regresar">
    </a>
    <div class="header-title">
        <h1>Historial de Ventas Realizadas</h1>
    </div>
</div>

<div class="container mt-4 mb-5 pb-5">
    <!-- Filtros de fecha -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0">Filtrar por:</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.filtrar_historial') }}">
                <div class="row">
                    <div class="col-md-4">
                        <select name="tipo" class="form-select" onchange="this.form.submit()">
                            <option value="dia" {{ ($tipo ?? 'dia') == 'dia' ? 'selected' : '' }}>Por Día</option>
                            <option value="semana" {{ ($tipo ?? '') == 'semana' ? 'selected' : '' }}>Por Semana</option>
                            <option value="mes" {{ ($tipo ?? '') == 'mes' ? 'selected' : '' }}>Por Mes</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="date" name="fecha" class="form-control" value="{{ $fecha ?? $hoy }}" onchange="this.form.submit()">
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-secondary" onclick="cambiarFecha(-1)">← Anterior</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="cambiarFecha(1)">Siguiente →</button>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="tipo_hidden" value="{{ $tipo ?? 'dia' }}">
            </form>
        </div>
    </div>

    <!-- Lista de pedidos por fecha -->
    @if($pedidos->isEmpty())
        <div class="alert alert-info text-center">
            No hay pedidos para la fecha seleccionada.
        </div>
    @else
        @php
            $pedidosAgrupados = $pedidos->groupBy(function($pedido) {
                return $pedido->fecha_hora_pedido->format('Y-m-d');
            });
        @endphp
        
        @foreach($pedidosAgrupados as $fecha => $pedidosDia)
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0">{{ \Carbon\Carbon::parse($fecha)->format('l, d/m/Y') }}</h6>
                        <small class="text-muted">{{ $pedidosDia->count() }} pedido(s)</small>
                    </div>
                    <a href="{{ route('admin.detalle_pedido', $fecha) }}" class="btn btn-primary btn-sm">Ver Detalle</a>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($pedidosDia->take(3) as $pedido)
                            <div class="col-md-4 mb-2">
                                <div class="border rounded p-2">
                                    <small><strong>Mesa {{ $pedido->mesa->numero_mesa }}</strong></small><br>
                                    <small>S/ {{ number_format($pedido->total_pedido, 2) }}</small><br>
                                    <small class="text-muted">{{ $pedido->fecha_hora_pedido->format('H:i') }}</small>
                                </div>
                            </div>
                        @endforeach
                        @if($pedidosDia->count() > 3)
                            <div class="col-md-4 mb-2">
                                <div class="border rounded p-2 text-center text-muted">
                                    +{{ $pedidosDia->count() - 3 }} más
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    @endif
</div>

<script>
function cambiarFecha(direccion) {
    const fechaInput = document.querySelector('input[name="fecha"]');
    const tipoFiltro = document.querySelector('select[name="tipo"]').value;
    const fechaActual = new Date(fechaInput.value);
    
    let nuevaFecha = new Date(fechaActual);
    
    if (tipoFiltro === 'dia') {
        nuevaFecha.setDate(fechaActual.getDate() + direccion);
    } else if (tipoFiltro === 'semana') {
        nuevaFecha.setDate(fechaActual.getDate() + (direccion * 7));
    } else if (tipoFiltro === 'mes') {
        nuevaFecha.setMonth(fechaActual.getMonth() + direccion);
    }
    
    fechaInput.value = nuevaFecha.toISOString().split('T')[0];
    
    // Update the form action to include tipo parameter
    const form = fechaInput.closest('form');
    const tipoHidden = form.querySelector('input[name="tipo_hidden"]');
    tipoHidden.value = tipoFiltro;
    
    // Submit form with new date and tipo
    const formData = new FormData(form);
    const params = new URLSearchParams();
    for (let [key, value] of formData.entries()) {
        params.append(key, value);
    }
    
    window.location.href = '{{ route("admin.filtrar_historial") }}?' + params.toString();
}
</script>
@endsection
