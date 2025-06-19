@extends('view_layout.app')

@section('content')
<x-app-header backUrl="{{ route('vista.user_menu') }}" title="Gestión de Promociones" />

<div class="container mt-4 mb-5 pb-5">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <!-- Lista de promociones -->
    @if($promociones->isEmpty())
        <div class="alert alert-info text-center">
            No hay promociones creadas aún.
        </div>
    @else
        @foreach($promociones as $promocion)
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h5 class="card-title">{{ $promocion->nombre_promocion }}</h5>
                            <p class="card-text">
                                <strong>Tipo:</strong> {{ $promocion->descripcion_promocion }}<br>
                                <strong>Precio:</strong> S/ {{ number_format($promocion->precio_promocion, 2) }}<br>
                                <strong>Vigencia:</strong> {{ \Carbon\Carbon::parse($promocion->fecha_inicio)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($promocion->fecha_fin)->format('d/m/Y') }}
                            </p>
                            <div class="mt-2">
                                <strong>Productos incluidos:</strong>
                                @foreach($promocion->productos as $promoProducto)
                                    <span class="badge bg-secondary me-1">{{ $promoProducto->producto->nombre }}</span>
                                @endforeach
                            </div>
                        </div>
                        <div class="d-flex flex-column align-items-end gap-2">
                            <!-- Switch para activar/desactivar -->
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" 
                                       id="promocion{{ $promocion->id_promocion }}" 
                                       {{ $promocion->estado_promocion === 'activa' ? 'checked' : '' }}
                                       onchange="togglePromocion({{ $promocion->id_promocion }})">
                                <label class="form-check-label" for="promocion{{ $promocion->id_promocion }}">
                                    Disponible
                                </label>
                            </div>
                            <!-- Botones de acción -->
                            <div class="d-flex gap-2">
                                <a href="{{ route('admin.editar_promocion', $promocion->id_promocion) }}" class="btn btn-warning btn-sm">Editar</a>
                                <button class="btn btn-danger btn-sm btn-eliminar" data-promocion-id="{{ $promocion->id_promocion }}">Borrar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @endif
    
    <!-- Botón flotante para nueva promoción -->
    <a href="{{ route('admin.agregar_promocion') }}" class="btn btn-primary position-fixed" style="bottom: 20px; right: 20px; border-radius: 50%; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; font-size: 24px;">
        +
    </a>
</div>

<script>
function togglePromocion(id) {
    fetch(`/admin/promociones/${id}/toggle`, {
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
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al cambiar estado de la promoción');
        location.reload();
    });
}

// Cambiar el onclick por event listener
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-eliminar').forEach(button => {
        button.addEventListener('click', function() {
            const promocionId = this.dataset.promocionId;
            confirmarEliminar(promocionId);
        });
    });
});

function confirmarEliminar(id) {
    if (confirm('¿Estás seguro de que quieres eliminar esta promoción?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/admin/promociones/${id}/eliminar`;
        
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
</script>
@endsection
