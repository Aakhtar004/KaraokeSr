@extends('view_layout.app_login')

@section('content')
<!-- Font Awesome CDN -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
    body {
        background: url("{{ asset('images/dragon.png') }}") no-repeat center center fixed;
        background-size: cover;
        font-family: 'Arial', sans-serif;
    }

    .container-login {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100vh;
    }

    .login-header {
        text-align: center;
        margin-bottom: 1rem;
        color: white;
    }

    .login-header img {
        width: 140px;
        margin-bottom: 10px;
    }

    .login-header h5 {
        margin: 0;
        font-size: 1.2rem;
        font-weight: bold;
        color: #ffffff;
    }

    .form-group {
        position: relative;
        width: 100%;
        max-width: 400px;
    }

    .form-control {
        width: 100%;
        padding: 0.75rem 2.5rem 0.75rem 0.75rem;
        border: none;
        border-bottom: 2px solid #a94442;
        border-radius: 8px;
        background-color: transparent;
        font-weight: bold;
        color: #5c0d0d;
    }

    .form-control::placeholder {
        color: #c99;
    }

    .form-group i {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #a94442;
        font-size: 1rem;
    }

    .login-card {
        background-color: white;
        border-radius: 25px;
        padding: 2rem;
        max-width: 400px;
        width: 100%;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }

    .btn-login {
        background-color: #5c0d0d;
        color: white;
        border: none;
        border-radius: 25px;
        padding: 0.5rem 1rem;
        margin-top: 1rem;
        width: 100%;
    }

    .btn-login:hover {
        background-color: #7a1313;
    }
</style>

<div class="container-login">
    <div class="login-header">
        <img src="{{ asset('images/salonred.png') }}" alt="Logo Karaoke">
        <h5>Bienvenido de nuevo a Salón Rojo</h5>
    </div>

    <form method="POST" action="{{ route('verificar.login') }}">
        @csrf

        <div class="login-card">
            <div class="form-group mb-3">
                <input type="text" id="usuario" name="usuario" class="form-control" placeholder="User / e - mail" required>
                <i class="fas fa-user"></i>
            </div>

            <div class="form-group mb-3">
                <input type="password" id="contrasena" name="contrasena" class="form-control" placeholder="Password" required>
                <i class="fas fa-key"></i>
            </div>

            <button type="submit" class="btn btn-login">Ingresar</button>

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
    </form>
</div>
@endsection
