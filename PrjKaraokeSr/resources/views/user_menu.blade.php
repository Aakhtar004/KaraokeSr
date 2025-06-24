@extends('view_layout.app')
@section('content')
    <div class="body-overlay"></div>
    <div class="top-header">
        <form action="{{ route('logout') }}" method="POST" style="margin:0; padding:0;">
            @csrf
            <button type="submit" class="logout-btn" title="Cerrar sesión">
                <img src="{{ asset('images/icono-cerrarsesion.png') }}" alt="Cerrar sesión">
            </button>
        </form>
        <h1 class="user-menu-title">¿Qué haremos hoy?</h1>
    </div>

    <div class="user-menu-container">
        <div class="background-logo">
            <img src="{{ asset('images/logo.png') }}" alt="Salon Rojo Logo">
        </div>

        <div class="menu-card-wrapper">
            @php
                $rol = $user->rol ?? null;
            @endphp

            @if($rol === 'bartender')
                <a class="menu-card" href="{{ route('vista.barra_historial') }}">
                    <img src="{{ asset('images/icon_pedidos.png') }}" alt="Pedidos">
                    <span>Ver historial de <br> Pedidos</span>
                </a>
                <a class="menu-card" href="{{ route('vista.barra_inventario') }}">
                    <img src="{{ asset('images/icon_inventario.png') }}" alt="Inventario">
                    <span>Hacer Control de <br> Inventario</span>
                </a>
            @elseif($rol === 'cocinero')
                <a class="menu-card" href="{{ route('vista.cocina_historial') }}">
                    <img src="{{ asset('images/icon_pedidos.png') }}" alt="Pedidos">
                    <span>Ver historial de <br> Pedidos</span>
                </a>
                <a class="menu-card" href="{{ route('vista.cocina_inventario') }}">
                    <img src="{{ asset('images/icon_inventario.png') }}" alt="Inventario">
                    <span>Hacer Control de <br> Inventario</span>
                </a>
            @elseif($rol === 'mesero')
                <a class="menu-card" href="{{ route('vista.mozo_historial') }}">
                    <img src="{{ asset('images/icon_pedidos.png') }}" alt="Pedidos">
                    <span>Ver historial de <br> Pedidos</span>
                </a>
            @endif
        </div>

        @if($user->rol === 'administrador')
            <a class="menu-card btn btn-secondary btn-lg" href="{{ route('vista.admin_modificar_categoria') }}">
                <img src="{{ asset('images/icon_inventario.png') }}" alt="Modificar">
                <br><span>Modificar Precios y Stock</span>
            </a>
            <a class="menu-card btn btn-secondary btn-lg" href="{{ route('vista.admin_agregar_producto') }}">
                <img src="{{ asset('images/icon_inventario.png') }}" alt="Agregar">
                <br><span>Agregar Producto</span>
            </a>
            <a class="menu-card btn btn-secondary btn-lg" href="{{ route('vista.admin_gestion_usuarios') }}">
                <img src="{{ asset('images/icon_inventario.png') }}" alt="Gestionar">
                <br><span>Gestionar Usuarios</span>
            </a>
            <a class="menu-card btn btn-secondary btn-lg " href="{{ route('vista.admin_historial_ventas')}}">
                <img src="{{ asset('images/icon_inventario.png') }}" alt="Historial"> 
                <br><span>Ver Historial de Ventas del Restobar</span>
            </a>
            <a class="menu-card btn btn-secondary btn-lg " href="{{ route('vista.admin_generar_lista_compras') }}">
                <img src="{{ asset('images/icon_inventario.png') }}" alt="Compras"> 
                <br><span>Generar lista de Compras</span>
            </a>
            <a class="menu-card btn btn-secondary btn-lg" href="{{ route('vista.admin_promociones') }}">
                <img src="{{ asset('images/icon_inventario.png') }}" alt="Promociones"> 
                <br><span>Gestionar Promociones</span>
            </a>
        @endif
    </div>
@endsection
