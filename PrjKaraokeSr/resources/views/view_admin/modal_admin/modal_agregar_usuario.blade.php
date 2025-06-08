<div class="modal-overlay position-fixed top-0 start-0 w-100 h-100" style="background: rgba(0,0,0,0.5); z-index: 1050;">
    <div class="modal-container d-flex align-items-center justify-content-center h-100">
        <div class="modal-content bg-white rounded-4 p-4 mx-3" style="max-width: 500px; width: 100%;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Agregar Nuevo Usuario</h5>
                <a href="{{ route('vista.admin_gestion_usuarios') }}" class="btn-close" style="text-decoration: none; font-size: 1.5rem;">&times;</a>
            </div>
            
            <!-- CAMBIAR LA ACCIÓN DEL FORMULARIO A LA RUTA CORRECTA -->
            <form action="{{ route('admin.usuarios.store') }}" method="POST">
                @csrf
                
                <div class="mb-3">
                    <label class="form-label text-muted">Nombre:</label>
                    <input type="text" class="form-control @error('nombres') is-invalid @enderror" 
                           name="nombres" value="{{ old('nombres') }}" required maxlength="255">
                    @error('nombres')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label class="form-label text-muted">Código Usuario:</label>
                    <input type="text" class="form-control @error('codigo_usuario') is-invalid @enderror" 
                           name="codigo_usuario" value="{{ old('codigo_usuario') }}" required maxlength="50">
                    @error('codigo_usuario')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label class="form-label text-muted">Contraseña:</label>
                    <input type="password" class="form-control @error('contrasena') is-invalid @enderror" 
                           name="contrasena" required minlength="6">
                    @error('contrasena')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label class="form-label text-muted">Confirmar Contraseña:</label>
                    <input type="password" class="form-control" name="contrasena_confirmation" required minlength="6">
                </div>
                
                <div class="mb-4">
                    <label class="form-label text-muted">Rol:</label>
                    <select class="form-select @error('rol') is-invalid @enderror" name="rol" required>
                        <option value="">Seleccione un rol</option>
                        <option value="administrador" {{ old('rol') == 'administrador' ? 'selected' : '' }}>Administrador</option>
                        <option value="mesero" {{ old('rol') == 'mesero' ? 'selected' : '' }}>Mesero</option>
                        <option value="cocinero" {{ old('rol') == 'cocinero' ? 'selected' : '' }}>Cocinero</option>
                        <option value="bartender" {{ old('rol') == 'bartender' ? 'selected' : '' }}>Bartender</option>
                    </select>
                    @error('rol')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="d-flex justify-content-between">
                    <a href="{{ route('vista.admin_gestion_usuarios') }}" class="btn btn-danger">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-dark">
                        Guardar Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
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

    // Validar confirmación de contraseña
    const confirmPasswordInput = document.querySelector('input[name="contrasena_confirmation"]');
    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', function() {
            const passwordInput = document.querySelector('input[name="contrasena"]');
            if (passwordInput && passwordInput.value !== this.value) {
                this.setCustomValidity('Las contraseñas no coinciden');
            } else {
                this.setCustomValidity('');
            }
        });
    }
});
</script>
