@extends('view_layout.app')

@section('content')
<x-app-header backUrl="{{ route('vista.user_menu') }}" title="Gestión de Usuarios" />

<div class="container mt-4">
    <!-- Botón agregar usuario -->
    <div class="d-flex justify-content-start mb-4">
        <button type="button" class="btn btn-danger rounded-circle d-flex align-items-center justify-content-center" 
                style="width: 50px; height: 50px; font-size: 1.5rem;" 
                data-bs-toggle="modal" data-bs-target="#agregarUsuarioModal">
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
                        <p class="mb-1 text-muted">{{ $usuario->usuario }}@gmail.com</p>
                        <p class="mb-0 text-capitalize">{{ $usuario->rol }}</p>
                    </div>
                    <div class="d-flex flex-column gap-2">
                        <button type="button" class="btn btn-dark btn-sm btn-editar" 
                                data-usuario-id="{{ $usuario->id_usuario }}">
                            Editar
                        </button>
                        <button type="button" class="btn btn-danger btn-sm btn-eliminar" 
                                data-usuario-id="{{ $usuario->id_usuario }}">
                            Eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- Modal Agregar Usuario -->
<div class="modal fade" id="agregarUsuarioModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 20px;">
            <form action="{{ route('admin.usuarios.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="text-end mb-3">
                        <button type="button" class="btn-close bg-danger rounded-circle p-2" 
                                data-bs-dismiss="modal" style="width: 30px; height: 30px;"></button>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-muted">Nombre:</label>
                        <input type="text" class="form-control" name="nombres" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-muted">Número:</label>
                        <input type="text" class="form-control" name="codigo_usuario" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-muted">Contraseña:</label>
                        <input type="password" class="form-control" name="contrasena" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-muted">Confirmar Contraseña:</label>
                        <input type="password" class="form-control" name="contrasena_confirmation" required>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label text-muted">Rol:</label>
                        <select class="form-select" name="rol" required>
                            <option value="">Seleccione un rol</option>
                            <option value="administrador">Administrador</option>
                            <option value="mesero">Mesero</option>
                            <option value="cocinero">Cocinero</option>
                            <option value="bartender">Bartender</option>
                        </select>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-dark">
                            Guardar Usuario
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Manejo de botones editar
    const botonesEditar = document.querySelectorAll('.btn-editar');
    botonesEditar.forEach(function(boton) {
        boton.addEventListener('click', function() {
            const usuarioId = this.getAttribute('data-usuario-id');
            alert('Funcionalidad de edición por implementar para usuario ID: ' + usuarioId);
        });
    });

    // Manejo de botones eliminar
    const botonesEliminar = document.querySelectorAll('.btn-eliminar');
    botonesEliminar.forEach(function(boton) {
        boton.addEventListener('click', function() {
            const usuarioId = this.getAttribute('data-usuario-id');
            
            if (confirm('¿Está seguro de eliminar este usuario?')) {
                fetch('/admin/usuarios/' + usuarioId, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json'
                    }
                })
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error al eliminar usuario: ' + data.message);
                    }
                })
                .catch(function(error) {
                    console.error('Error:', error);
                    alert('Error al eliminar usuario');
                });
            }
        });
    });
});
</script>
@endsection
