@extends('view_layout.app')
@section('content')
<link href="{{ asset('css/modificar_producto.css') }}" rel="stylesheet">

<!-- Header con título dinámico -->
<div class="custom-header">
    <a href="{{ route('vista.admin_modificar_categoria') }}" class="back-button">
        <img src="{{ asset('images/izquierda.png') }}" alt="Regresar" class="img-back">
    </a>
    <div>
        <!-- Título dinámico basado en la categoría -->
        <h1 class="page-title">Productos de {{ $categoria->nombre }}</h1>
        <p class="page-subtitle">{{ $categoria->nombre }}</p>
    </div>
</div>


<div class="container">

    <!-- Barra de búsqueda (estilo pero no funcional) -->
    <div class="search-bar">
        <input type="text" class="search-input" placeholder="Buscar Productos...">
    </div>

    <ul class="list-group">
        @foreach($productos as $producto)
            <li class="list-group-item d-flex align-items-center">
                @if($producto->imagen_url)
                    <img
                        src="{{ $producto->imagen_url }}"
                        alt="{{ $producto->nombre }}"
                        style="width:80px; height:80px; object-fit:cover; margin-right:1rem; border-radius:10px;"
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
                        >
                    </div>

                    @if($categoria->nombre === 'Cocteles')
                        <!-- Switch de estado -->
                        <div class="form-check form-switch me-2 align-self-end">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                name="estado"
                                value="1"
                                {{ $producto->estado ? 'checked' : '' }}
                            >
                            <label class="form-check-label">Activo</label>
                        </div>
                    @else
                        <!-- Stock -->
                        <div class="me-2">
                            <label class="form-label mb-0">Stock</label>
                            <input
                                type="number"
                                name="stock"
                                class="form-control"
                                value="{{ $producto->stock }}"
                            >
                        </div>
                    @endif

                    <button type="submit" class="btn btn-primary align-self-end">
                        Guardar
                    </button>
                </form>
            </li>
        @endforeach
    </ul>
</div>
@endsection