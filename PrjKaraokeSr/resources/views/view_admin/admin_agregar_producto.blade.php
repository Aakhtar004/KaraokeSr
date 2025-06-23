@extends('view_layout.app')
@section('content')
<link href="{{ asset('css/agregar_producto.css') }}" rel="stylesheet">

<!-- Header personalizado -->
<div class="custom-header">
    <a href="{{ route('vista.user_menu') }}" class="back-button">
    <img src="{{ asset('images/izquierda.png') }}" alt="Regresar">
</a>
    <h1 class="page-title">Agregar Productos</h1>
</div>

<div class="container d-flex justify-content-center align-items-center form-container">
    <div class="card p-4" style="border-radius: 20px; background: rgba(255, 182, 182, 0.8); width: 100%; max-width: 400px;">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form action="{{ route('admin.productos.store') }}" method="POST">
            @csrf
            
            <div class="mb-3">
                <label class="form-label text-muted">Nombre de Producto:</label>
                <input type="text" class="form-control" name="nombre" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label text-muted">Categoría:</label>
                <select class="form-select" name="id_categoria_producto" required>
                    <option value="">Seleccione una categoría</option>
                    @foreach($categorias as $categoria)
                        <option value="{{ $categoria->id_categoria_producto }}">{{ $categoria->nombre }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label text-muted">Precio:</label>
                <div class="input-group">
                    <span class="input-group-text">S/.</span>
                    <input type="number" class="form-control" name="precio_unitario" step="0.01" min="0" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label text-muted">Stock:</label>
                <div class="d-flex align-items-center">
                    <button type="button" class="btn btn-outline-secondary" id="btnRestarStock">-</button>
                    <input type="number" class="form-control mx-2 text-center" name="stock" id="inputStock" value="1" min="0" required style="width: 80px;">
                    <button type="button" class="btn btn-outline-secondary" id="btnSumarStock">+</button>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label text-muted">Disponible:</label>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="estado" value="1" id="switchEstado" checked>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="form-label text-muted">Adjuntar imagen:</label>
                <input type="url" class="form-control" name="imagen_url" placeholder="URL de la imagen">
                <small class="form-text text-muted">Ingrese la URL de la imagen del producto</small>
            </div>
            
            <div class="d-grid">
                <button type="submit" class="btn text-white" style="background-color: #8B4513; border-radius: 20px;">
                    Agregar Producto
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputStock = document.getElementById('inputStock');
    const btnRestarStock = document.getElementById('btnRestarStock');
    const btnSumarStock = document.getElementById('btnSumarStock');
    
    btnRestarStock.addEventListener('click', function() {
        const valorActual = parseInt(inputStock.value);
        if (valorActual > 0) {
            inputStock.value = valorActual - 1;
        }
    });
    
    btnSumarStock.addEventListener('click', function() {
        const valorActual = parseInt(inputStock.value);
        inputStock.value = valorActual + 1;
    });
});
</script>
@endsection