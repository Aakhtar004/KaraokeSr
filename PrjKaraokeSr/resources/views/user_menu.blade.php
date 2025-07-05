@extends('view_layout.app')
@section('content')
    <div class="body-overlay"></div>
    <div class="top-header">
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="margin:0; padding:0;">
            @csrf
            <button type="button" class="logout-btn" title="Cerrar sesión" onclick="mostrarModalLogout()">
                <img src="{{ asset('images/icono-cerrarsesion.png') }}" alt="Cerrar sesión">
            </button>
        </form>
        <h1 class="user-menu-title">¿Qué haremos hoy?</h1>
    </div>

    <!-- Modal de confirmación de cierre de sesión -->
    <div id="modalLogout" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100vw; height:100vh; background:rgba(0,0,0,0.35);">
        <div style="background:#fff; border-radius:10px; max-width:350px; margin:10% auto; padding:30px 20px; box-shadow:0 2px 16px #0003; text-align:center;">
            <h5>¿Cerrar sesión?</h5>
            <p>¿Estás seguro de que deseas salir de tu cuenta?</p>
            <div style="margin-top:20px;">
                <button type="button" onclick="ocultarModalLogout()" style="margin-right:10px; padding:6px 18px; border:none; border-radius:5px; background:#ccc;">Cancelar</button>
                <button type="button" onclick="document.getElementById('logout-form').submit();" style="padding:6px 18px; border:none; border-radius:5px; background:#c4361d; color:#fff;">Cerrar sesión</button>
            </div>
        </div>
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

    <script>
        function mostrarModalLogout() {
            document.getElementById('modalLogout').style.display = 'block';
        }
        function ocultarModalLogout() {
            document.getElementById('modalLogout').style.display = 'none';
        }
    </script>
@endsection
