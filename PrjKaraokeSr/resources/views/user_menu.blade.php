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
        <div class="button-grid">
            <a href="{{ route('vista.admin_modificar_categoria') }}" class="menu-button">
                <img src="{{ asset('images/factura.png') }}" alt="Precios"> Modificar Precios y Stock
            </a>
            <a href="{{ route('vista.admin_agregar_producto') }}" class="menu-button">
                <img src="{{ asset('images/addproducto.png') }}" alt="Producto"> Agregar Producto
            </a>
            <a href="#" class="menu-button disabled">
                <img src="{{ asset('images/promocion.png') }}" alt="Promociones"> Agregar Promociones
            </a>
            <a href="{{ route('vista.admin_gestion_usuarios') }}" class="menu-button">
                <img src="{{ asset('images/usuarios.png') }}" alt="Usuarios"> Gestionar Usuarios
            </a>
            <a href="{{ route('vista.admin_historial') }}" class="menu-button">
                <img src="{{ asset('images/historial.png') }}" alt="Historial"> Ver Historial de Pedidos
            </a>
            <a href="{{ route('vista.admin_compras') }}" class="menu-button">
                <img src="{{ asset('images/pedido.png') }}" alt="Compras"> Generar Lista de Compras
            </a>
        </div>

        @elseif($user->rol === 'mesero')
            <a class="btn btn-secondary btn-lg" href="{{ route('vista.mozo_historial') }}">Ver Historial de Pedidos</a>

        @elseif($user->rol === 'cocinero')
            <a class="btn btn-secondary btn-lg" href="{{ route('vista.cocina_historial') }}">Ver Historial de Pedidos</a>
            <a class="btn btn-secondary btn-lg" href="{{ route('vista.cocina_inventario') }}">Hacer Control de Inventario</a>
        @endif
    </div>
@endsection
