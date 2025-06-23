@extends('view_layout.app')
@section('content')
<link href="{{ asset('css/gestion_usuarios.css') }}" rel="stylesheet">

<!-- Header personalizado -->
<div class="custom-header">
    <a href="{{ route('vista.user_menu') }}" class="back-button">
        <img src="{{ asset('images/izquierda.png') }}" alt="Regresar">
    </a>
    <div class="header-title">
        <h1>Vista Gestión de Usuarios</h1>
    </div>
</div>

<button class="add-user-btn">
        <img src="{{ asset('images/add.png') }}" alt="Agregar">
        Agregar Usuario
    </button>

<div class="container mt-4">
    <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if(session('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Botón agregar usuario (oculto) -->
    <div class="d-flex justify-content-start mb-4" style="display: none !important;">
        <button class="btn btn-danger rounded-circle d-flex align-items-center justify-content-center" 
                style="width: 50px; height: 50px; font-size: 1.5rem;"
                data-bs-toggle="modal" data-bs-target="#modalAgregarUsuario">
            +
        </button>
        <span class="ms-3 align-self-center text-muted">Agregar Usuario</span>
    </div>

    <!-- Lista de usuarios -->
    <div class="row">
        @foreach($usuarios as $usuario)
        <div class="col-md-6 mb-3">
            <div class="card p-3" style="border-radius: 15px; background: rgba(255, 255, 255, 0.9);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1 text-danger">{{ $usuario->nombres }}</h6>
                        <p class="mb-1 text-muted">{{ $usuario->codigo_usuario }}</p>
                        <p class="mb-0 text-capitalize">
                            {{ $usuario->rol }}
                            <?php if($usuario->estado == 0): ?>
                                <span class="badge bg-secondary ms-1">Inactivo</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="d-flex flex-column gap-2">
                        <!-- Botón para editar -->
                        <button class="btn btn-dark btn-sm" 
                                data-bs-toggle="modal" 
                                data-bs-target="#modalEditarUsuario"
                                data-usuario-id="{{ $usuario->id_usuario }}"
                                data-usuario-nombres="{{ $usuario->nombres }}"
                                data-usuario-codigo="{{ $usuario->codigo_usuario }}"
                                data-usuario-rol="{{ $usuario->rol }}"
                                data-usuario-estado="{{ $usuario->estado }}">
                            Editar
                        </button>
                        
                        <?php if($usuario->rol !== 'administrador'): ?>
                        <form action="{{ route('admin.usuarios.delete', $usuario->id_usuario) }}" method="POST" 
                              style="display: inline;" 
                              onsubmit="return confirm('¿Estás seguro de eliminar al usuario {{ $usuario->nombres }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger btn-sm">
                                Eliminar
                            </button>
                        </form>
                        <?php else: ?>
                        <button type="button" class="btn btn-secondary btn-sm" disabled title="Los administradores no pueden eliminarse">
                            Protegido
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- Modal Agregar Usuario -->
<div class="modal fade" id="modalAgregarUsuario" tabindex="-1" aria-labelledby="modalAgregarUsuarioLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAgregarUsuarioLabel">Agregar Nuevo Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formAgregarUsuario" action="{{ route('admin.usuarios.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label text-muted">Nombre:</label>
                        <input type="text" class="form-control @if(session('modal_type') === 'add') @error('nombres') is-invalid @enderror @endif" 
                               name="nombres" value="{{ session('modal_type') === 'add' ? old('nombres') : '' }}" required maxlength="255">
                        @if(session('modal_type') === 'add')
                            @error('nombres')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Código Usuario:</label>
                        <input type="text" class="form-control @if(session('modal_type') === 'add') @error('codigo_usuario') is-invalid @enderror @endif" 
                               name="codigo_usuario" value="{{ session('modal_type') === 'add' ? old('codigo_usuario') : '' }}" required maxlength="50">
                        @if(session('modal_type') === 'add')
                            @error('codigo_usuario')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Contraseña:</label>
                        <input type="password" class="form-control @if(session('modal_type') === 'add') @error('contrasena') is-invalid @enderror @endif" 
                               name="contrasena" required minlength="6">
                        @if(session('modal_type') === 'add')
                            @error('contrasena')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Confirmar Contraseña:</label>
                        <input type="password" class="form-control" name="contrasena_confirmation" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Rol:</label>
                        <select class="form-select @if(session('modal_type') === 'add') @error('rol') is-invalid @enderror @endif" name="rol" required>
                            <option value="">Seleccione un rol</option>
                            <option value="administrador" {{ session('modal_type') === 'add' && old('rol') === 'administrador' ? 'selected' : '' }}>Administrador</option>
                            <option value="mesero" {{ session('modal_type') === 'add' && old('rol') === 'mesero' ? 'selected' : '' }}>Mesero</option>
                            <option value="cocinero" {{ session('modal_type') === 'add' && old('rol') === 'cocinero' ? 'selected' : '' }}>Cocinero</option>
                            <option value="bartender" {{ session('modal_type') === 'add' && old('rol') === 'bartender' ? 'selected' : '' }}>Bartender</option>
                        </select>
                        @if(session('modal_type') === 'add')
                            @error('rol')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="formAgregarUsuario" class="btn btn-dark">Guardar Usuario</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Usuario -->
<div class="modal fade" id="modalEditarUsuario" tabindex="-1" aria-labelledby="modalEditarUsuarioLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarUsuarioLabel">Editar Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarUsuario" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label text-muted">Nombre:</label>
                        <input type="text" class="form-control @if(session('modal_type') === 'edit') @error('nombres') is-invalid @enderror @endif" 
                               name="nombres" id="edit-nombres" required maxlength="255"
                               value="{{ session('modal_type') === 'edit' ? old('nombres') : '' }}">
                        @if(session('modal_type') === 'edit')
                            @error('nombres')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Código Usuario:</label>
                        <input type="text" class="form-control @if(session('modal_type') === 'edit') @error('codigo_usuario') is-invalid @enderror @endif" 
                               name="codigo_usuario" id="edit-codigo-usuario" required maxlength="50"
                               value="{{ session('modal_type') === 'edit' ? old('codigo_usuario') : '' }}">
                        @if(session('modal_type') === 'edit')
                            @error('codigo_usuario')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Nueva Contraseña (opcional):</label>
                        <input type="password" class="form-control @if(session('modal_type') === 'edit') @error('contrasena') is-invalid @enderror @endif" 
                               name="contrasena" id="edit-contrasena" minlength="6">
                        <small class="form-text text-muted">Déjalo vacío si no quieres cambiar la contraseña</small>
                        @if(session('modal_type') === 'edit')
                            @error('contrasena')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Confirmar Nueva Contraseña:</label>
                        <input type="password" class="form-control" name="contrasena_confirmation" id="edit-contrasena-confirmation">
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Rol:</label>
                        <select class="form-select @if(session('modal_type') === 'edit') @error('rol') is-invalid @enderror @endif" name="rol" id="edit-rol" required>
                            <option value="administrador" {{ session('modal_type') === 'edit' && old('rol') === 'administrador' ? 'selected' : '' }}>Administrador</option>
                            <option value="mesero" {{ session('modal_type') === 'edit' && old('rol') === 'mesero' ? 'selected' : '' }}>Mesero</option>
                            <option value="cocinero" {{ session('modal_type') === 'edit' && old('rol') === 'cocinero' ? 'selected' : '' }}>Cocinero</option>
                            <option value="bartender" {{ session('modal_type') === 'edit' && old('rol') === 'bartender' ? 'selected' : '' }}>Bartender</option>
                        </select>
                        @if(session('modal_type') === 'edit')
                            @error('rol')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Estado:</label>
                        <select class="form-select @if(session('modal_type') === 'edit') @error('estado') is-invalid @enderror @endif" name="estado" id="edit-estado" required>
                            <option value="1" {{ session('modal_type') === 'edit' && old('estado') === '1' ? 'selected' : '' }}>Activo</option>
                            <option value="0" {{ session('modal_type') === 'edit' && old('estado') === '0' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                        @if(session('modal_type') === 'edit')
                            @error('estado')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        @endif
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="formEditarUsuario" class="btn btn-dark">Actualizar Usuario</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mostrar solo el modal correspondiente según el tipo de error
    <?php if(session('modal_type') === 'add' && (session('show_modal_add') || ($errors->any() && old('_token')))): ?>
        var modalAgregar = new bootstrap.Modal(document.getElementById('modalAgregarUsuario'));
        modalAgregar.show();
    <?php elseif(session('modal_type') === 'edit' && session('show_modal_edit')): ?>
        var modalEditar = new bootstrap.Modal(document.getElementById('modalEditarUsuario'));
        modalEditar.show();
    <?php endif; ?>

    // Configurar modal de editar cuando se hace clic en el botón (solo si no hay error de validación)
    const modalEditarUsuario = document.getElementById('modalEditarUsuario');
    modalEditarUsuario.addEventListener('show.bs.modal', function (event) {
        <?php if(!session('modal_type')): ?>
            const button = event.relatedTarget;
            if (button) {
                const usuarioId = button.getAttribute('data-usuario-id');
                const nombres = button.getAttribute('data-usuario-nombres');
                const codigo = button.getAttribute('data-usuario-codigo');
                const rol = button.getAttribute('data-usuario-rol');
                const estado = button.getAttribute('data-usuario-estado');
                document.getElementById('edit-nombres').value = nombres || '';
                document.getElementById('edit-codigo-usuario').value = codigo || '';
                document.getElementById('edit-rol').value = rol || '';
                document.getElementById('edit-estado').value = estado || '';
                document.getElementById('edit-contrasena').value = '';
                document.getElementById('edit-contrasena-confirmation').value = '';
                const baseUrl = "{{ url('') }}";
                document.getElementById('formEditarUsuario').action = `${baseUrl}/view_admin/admin_usuarios/${usuarioId}`;
            }
        <?php endif; ?>
    });

    // Validación de texto en tiempo real
    const textInputs = document.querySelectorAll('input[type="text"]');
    textInputs.forEach(function(input) {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/[<>\"'&]/g, '');
            if (this.hasAttribute('maxlength')) {
                const maxLength = parseInt(this.getAttribute('maxlength'));
                if (this.value.length > maxLength) {
                    this.value = this.value.substring(0, maxLength);
                }
            }
        });
    });

    // Validación de contraseñas
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach(function(input) {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/\s/g, '');
        });
    });

    // Validar confirmación de contraseña para agregar
    const confirmPasswordInput = document.querySelector('#modalAgregarUsuario input[name="contrasena_confirmation"]');
    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', function() {
            const passwordInput = document.querySelector('#modalAgregarUsuario input[name="contrasena"]');
            if (passwordInput && passwordInput.value !== this.value) {
                this.setCustomValidity('Las contraseñas no coinciden');
            } else {
                this.setCustomValidity('');
            }
        });
    }

    // Validar confirmación de contraseña para editar
    const editConfirmPasswordInput = document.getElementById('edit-contrasena-confirmation');
    if (editConfirmPasswordInput) {
        editConfirmPasswordInput.addEventListener('input', function() {
            const passwordInput = document.getElementById('edit-contrasena');
            if (passwordInput && passwordInput.value !== this.value) {
                this.setCustomValidity('Las contraseñas no coinciden');
            } else {
                this.setCustomValidity('');
            }
        });
    }

    // Configurar modal de agregar al hacer clic en el botón personalizado
    const addUserBtn = document.querySelector('.add-user-btn');
    addUserBtn.addEventListener('click', function() {
        const modalAgregar = new bootstrap.Modal(document.getElementById('modalAgregarUsuario'));
        modalAgregar.show();
    });
});
</script>
@endsection