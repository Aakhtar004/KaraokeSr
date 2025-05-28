@extends('view_layout.app_login')

@section('content')
<div class="d-flex justify-content-center align-items-center vh-100">
    <div class="card shadow p-4" style="width: 100%; max-width: 400px;">
        <div class="card-header text-center">
            <h4 class="mb-0">Bienvenido al Salón ROJO</h4>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('verificar.login') }}">
                @csrf
                <div class="form-group mb-3">
                    <label for="usuario">Nombre de Usuario</label>
                    <input type="text" id="usuario" name="usuario" class="form-control" required autofocus>
                </div>
                <div class="form-group mb-3">
                    <label for="contrasena">Contraseña</label>
                    <input type="password" id="contrasena" name="contrasena" class="form-control" required>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">Iniciar Sesión</button>
                </div>
            </form>

            @if ($errors->any())
                <div class="alert alert-danger mt-3 mb-0">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
