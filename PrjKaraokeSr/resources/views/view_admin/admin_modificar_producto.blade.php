@extends('view_layout.app')

@push('styles')
<link href="{{ asset('css/modificar_producto.css') }}" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
@endpush

@section('content')
  {{-- botón atrás a categorías --}}
  <x-app-header backUrl="{{ route('vista.admin_modificar_categoria') }}" />

  <!-- NUEVO: Contenedor para mensajes -->
  <div id="mensajes-container"></div>

  <div class="container">
    <h2 class="mb-4">Productos en "{{ $categoria->nombre }}"</h2>

    <ul class="list-group">
      @foreach($productos as $producto)
        <li class="list-group-item d-flex align-items-center">
          @if($producto->imagen_url)
            <img
              src="{{ $producto->imagen_url }}"
              alt="{{ $producto->nombre }}"
              style="width:100px; height:100px; object-fit:cover; margin-right:1rem;"
            >
          @endif

          <div class="flex-grow-1">
            <h5 class="mb-1">{{ $producto->nombre }}</h5>
            <small class="text-muted">{{ $producto->descripcion }}</small>
          </div>

          <form
            action="{{ route('admin.producto.actualizar', $producto->id_producto) }}"
            method="POST"
            class="d-flex align-items-center ms-3"
          >
            @csrf
            @method('PATCH')

            <!-- Precio -->
            <div class="me-2">
              <label class="form-label mb-0">Precio</label>
              <input
                type="number"
                name="precio_unitario"
                class="form-control"
                value="{{ $producto->precio_unitario }}"
                step="0.01"
                min="0"
                required
              >
            </div>

            <!-- NUEVO: Switch de estado universal para todos los productos -->
            <div class="form-check form-switch me-2 align-self-end">
              <input
                class="form-check-input"
                type="checkbox"
                name="estado"
                value="1"
                {{ $producto->estado ? 'checked' : '' }}
                id="estado-{{ $producto->id_producto }}"
              >
              <label class="form-check-label" for="estado-{{ $producto->id_producto }}">
                Activo
              </label>
            </div>

            @if($categoria->nombre !== 'Cocteles')
              <!-- Stock solo para productos que NO son cocteles -->
              <div class="me-2">
                <label class="form-label mb-0">Stock</label>
                <input
                  type="number"
                  name="stock"
                  class="form-control"
                  value="{{ $producto->stock }}"
                  min="0"
                  required
                >
              </div>
            @else
              <!-- Para cocteles: campo oculto para mantener el stock en 0 -->
              <input type="hidden" name="stock" value="0">
              <div class="me-2">
                <small class="text-info">
                  <i class="fas fa-cocktail"></i> Coctel - Sin stock
                </small>
              </div>
            @endif

            <!-- Botón Guardar -->
            <button type="submit" class="btn btn-primary align-self-end me-2">
              <i class="fas fa-save"></i> Guardar
            </button>
          </form>

          <!-- NUEVO: Botón Eliminar -->
          <form
            action="{{ route('admin.producto.eliminar', $producto->id_producto) }}"
            method="POST"
            class="d-inline"
            onsubmit="return confirmarEliminacion('{{ addslashes($producto->nombre) }}')"
          >
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger align-self-end" title="Eliminar producto">
              <i class="fas fa-trash-alt"></i>
            </button>
          </form>
        </li>
      @endforeach
    </ul>

    @if($productos->isEmpty())
      <div class="alert alert-info text-center mt-4">
        <i class="fas fa-info-circle"></i>
        No hay productos en esta categoría.
      </div>
    @endif
  </div>

  <!-- NUEVO: Mensajes usando Bootstrap Alert tradicional -->
  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show position-fixed" 
         style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" 
         id="alert-success">
      <i class="fas fa-check-circle"></i> {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show position-fixed" 
         style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" 
         id="alert-error">
      <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

<script>
function confirmarEliminacion(nombreProducto) {
    return confirm(
        'ÂżEstÃ¡ seguro de eliminar el producto "' + nombreProducto + '"?\n\n' +
        'Esta acciÃ³n:\n' +
        'â€¢ EliminarÃ¡ el producto permanentemente\n' +
        'â€¢ No se podrÃ¡ deshacer\n' +
        'â€¢ Puede afectar pedidos existentes\n\n' +
        'ÂżDesea continuar?'
    );
}

// Auto-ocultar mensajes después de 5 segundos
document.addEventListener('DOMContentLoaded', function() {
    // Para mensaje de éxito
    const alertSuccess = document.getElementById('alert-success');
    if (alertSuccess) {
        setTimeout(function() {
            alertSuccess.style.opacity = '0';
            setTimeout(function() {
                if (alertSuccess.parentNode) {
                    alertSuccess.remove();
                }
            }, 300);
        }, 5000);
    }
    
    // Para mensaje de error
    const alertError = document.getElementById('alert-error');
    if (alertError) {
        setTimeout(function() {
            alertError.style.opacity = '0';
            setTimeout(function() {
                if (alertError.parentNode) {
                    alertError.remove();
                }
            }, 300);
        }, 5000);
    }
});
</script>
@endsection
