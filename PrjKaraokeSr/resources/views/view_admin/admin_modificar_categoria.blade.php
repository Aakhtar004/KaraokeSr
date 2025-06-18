@extends('view_layout.app')
@section('content')
<link href="{{ asset('css/modificar_categoria.css') }}" rel="stylesheet">

<div class="custom-header">
    <a href="{{ route('vista.user_menu') }}" class="back-button">
        <img src="{{ asset('images/izquierda.png') }}" alt="Regresar">
    </a>
    <div>
        <h1 class="page-title">Productos de la Carta</h1>
        <p class="page-subtitle">Administrador</p>
    </div>
</div>

<div class="button-grid">
    @foreach($categorias as $cat)
        <a href="{{ route('vista.admin_modificar_producto', $cat->id_categoria_producto) }}" 
           class="menu-button">
            <img src="{{ asset('images/logo.png') }}" 
                 alt="Logo">
            {{ $cat->nombre }}
        </a>
    @endforeach
</div>

@endsection