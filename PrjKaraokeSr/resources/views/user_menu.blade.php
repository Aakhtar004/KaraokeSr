@extends('view_layout.app')

@section('content')
    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <!-- 3) Logout: usa POST y @csrf -->
        <li class="nav-item">
            <form action="{{ route('logout') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="nav-link btn btn-link p-0">
                    Cerrar Sesión
                </button>
            </form>
        </li>
    </ul>

    @if($user->rol === 'administrador')
        <h1>Bienvenido Administrador</h1>
        <a class="btn btn-secondary btn-lg" href="{{ route('vista.admin_modificar_categoria') }}">Modificar Precios y Stock</a>
        <a class="btn btn-secondary btn-lg" href="{{ route('vista.admin_historial') }}">Ver Historial de Pedidos</a>
        <a class="btn btn-secondary btn-lg" href="{{ route('vista.admin_compras') }}">Generar lista de Compras</a>

    @elseif($user->rol === 'mesero')
        <h1>Bienvenido Mesero</h1>
        <a class="btn btn-secondary btn-lg" href="{{ route('vista.mozo_historial') }}">Ver Historial de Pedidos</a>

    @elseif($user->rol === 'bartender')
        <h1>Bienvenido Bartender</h1>
        <a class="btn btn-secondary btn-lg" href="{{ route('vista.barra_historial') }}">Ver Historial de Pedidos</a>
        <a class="btn btn-secondary btn-lg" href="{{ route('vista.barra_inventario') }}">Hacer Control de Inventario</a>

    @elseif($user->rol === 'cocinero')
        <h1>Bienvenido Cocinero</h1>
        <a class="btn btn-secondary btn-lg" href="{{ route('vista.cocina_historial') }}">Ver Historial de Pedidos</a>
        <a class="btn btn-secondary btn-lg" href="{{ route('vista.cocina_inventario') }}">Hacer Control de Inventario</a>
    @endif
@endsection
