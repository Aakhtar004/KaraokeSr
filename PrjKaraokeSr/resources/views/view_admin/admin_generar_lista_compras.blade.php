@extends('view_layout.app')

@section('content')
<div class="body-overlay"></div>

<link href="{{ asset('css/admin_generar_lista_compras.css') }}" rel="stylesheet">
<!-- Header personalizado -->
<div class="custom-header">
    <a href="{{ route('vista.user_menu') }}" class="back-button">
        <img src="{{ asset('images/izquierda.png') }}" alt="Regresar">
    </a>
    <div class="header-title">
        <h1>Historial de Compras</h1>
    </div>
</div>

<div class="admin-container">
    <div class="lista-compras-wrapper">
        
        <!-- Formulario para marcar productos como reabastecidos -->
        <form id="reabastecerForm" action="{{ route('admin.productos.reabastecer') }}" method="POST">
            @csrf
            
            <!-- Mensajes de éxito/error -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            
            <!-- Resumen con controles -->
            @if($productosCocina->count() > 0 || $productosBar->count() > 0)
                <div class="resumen-compras">
                    <h3>Lista de Compras Pendientes</h3>
                    <div class="stats">
                        <div class="stat-item">
                            <span class="numero">{{ $productosCocina->count() }}</span>
                            <span class="label">Productos de cocina</span>
                        </div>
                        <div class="stat-item">
                            <span class="numero">{{ $productosBar->count() }}</span>
                            <span class="label">Productos de bar</span>
                        </div>
                        <div class="stat-item">
                            <span class="numero">{{ $productosCocina->count() + $productosBar->count() }}</span>
                            <span class="label">Total productos</span>
                        </div>
                    </div>
                    
                    <!-- ✨ CONTROLES PARA SELECCIÓN Y REABASTECIMIENTO -->
                    <div class="controles-reabastecimiento mt-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="controles-seleccion">
                                <button type="button" class="btn btn-outline-light btn-sm" id="seleccionarTodos">
                                    <i class="bi bi-check-all"></i> Seleccionar Todos
                                </button>
                                <button type="button" class="btn btn-outline-light btn-sm" id="limpiarSeleccion">
                                    <i class="bi bi-x-circle"></i> Limpiar Selección
                                </button>
                                <span class="badge bg-light text-dark ms-2" id="contadorSeleccionados">0 seleccionados</span>
                            </div>
                            
                            <button type="submit" class="btn btn-success" id="btnReabastecer" disabled>
                                <i class="bi bi-check-circle"></i> Marcar como Reabastecidos
                            </button>
                        </div>
                    </div>
                </div>
            @else
                <div class="resumen-compras">
                    <h3>¡Excelente!</h3>
                    <p class="mb-0">No hay productos pendientes de reabastecimiento.</p>
                </div>
            @endif
            
            <br>
            
            <!-- Sección Cocina -->
            <div class="seccion-compras">
                <div class="seccion-header cocina">
                    <h2>
                        <img src="{{ asset('images/icon_cocina.png') }}" alt="Cocina" class="seccion-icon">
                        COCINA
                    </h2>
                    <span class="contador-productos">{{ $productosCocina->count() }} productos</span>
                </div>
                
                @if($productosCocina->count() > 0)
                    <div class="productos-grid">
                        @foreach($productosCocina as $producto)
                            <div class="producto-item pedido-pendiente">
                                <!-- ✨ CHECKBOX PARA SELECCIONAR PRODUCTO -->
                                <div class="producto-checkbox-container">
                                    <input type="checkbox" 
                                           class="form-check-input producto-checkbox" 
                                           name="productos[]" 
                                           value="{{ $producto->id_producto }}" 
                                           id="producto_{{ $producto->id_producto }}">
                                </div>
                                
                                <div class="producto-info">
                                    <h4>
                                        <label for="producto_{{ $producto->id_producto }}" class="producto-label">
                                            {{ $producto->nombre }}
                                        </label>
                                    </h4>
                                    <span class="categoria">{{ $producto->categoria->nombre }}</span>
                                </div>
                                <div class="stock-info">
                                    <span class="stock-actual">Estado: Pendiente de pedido</span>
                                    <span class="pendiente">¡PEDIDO REQUERIDO!</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="sin-productos">
                        <img src="{{ asset('images/icon_check.png') }}" alt="Todo bien">
                        <p>No hay productos pendientes de pedido en cocina</p>
                    </div>
                @endif
            </div>

            <!-- Sección Bar -->
            <div class="seccion-compras">
                <div class="seccion-header bar">
                    <h2>
                        <img src="{{ asset('images/icon_bar.png') }}" alt="Bar" class="seccion-icon">
                        BAR
                    </h2>
                    <span class="contador-productos">{{ $productosBar->count() }} productos</span>
                </div>
                
                @if($productosBar->count() > 0)
                    <div class="productos-grid">
                        @foreach($productosBar as $producto)
                            <div class="producto-item pedido-pendiente">
                                <!-- ✨ CHECKBOX PARA SELECCIONAR PRODUCTO -->
                                <div class="producto-checkbox-container">
                                    <input type="checkbox" 
                                           class="form-check-input producto-checkbox" 
                                           name="productos[]" 
                                           value="{{ $producto->id_producto }}" 
                                           id="producto_{{ $producto->id_producto }}">
                                </div>
                                
                                <div class="producto-info">
                                    <h4>
                                        <label for="producto_{{ $producto->id_producto }}" class="producto-label">
                                            {{ $producto->nombre }}
                                        </label>
                                    </h4>
                                    <span class="categoria">{{ $producto->categoria->nombre }}</span>
                                </div>
                                <div class="stock-info">
                                    <span class="stock-actual">Estado: Pendiente de pedido</span>
                                    <span class="pendiente">¡PEDIDO REQUERIDO!</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="sin-productos">
                        <img src="{{ asset('images/icon_check.png') }}" alt="Todo bien">
                        <p>No hay productos pendientes de pedido en bar</p>
                    </div>
                @endif
            </div>
        </form>
    </div>
</div>

<style>
.lista-compras-wrapper {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.seccion-compras {
    background: white;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.seccion-header {
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.seccion-header.cocina {
    background: linear-gradient(135deg, #ff6b6b, #ee5a24);
    color: white;
}

.seccion-header.bar {
    background: linear-gradient(135deg, #4834d4, #686de0);
    color: white;
}

.seccion-header h2 {
    display: flex;
    align-items: center;
    margin: 0;
    font-size: 24px;
    font-weight: 600;
}

.seccion-icon {
    width: 30px;
    height: 30px;
    margin-right: 12px;
    filter: brightness(0) invert(1);
}

.contador-productos {
    background: rgba(255,255,255,0.2);
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 500;
}

.productos-grid {
    padding: 20px;
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 15px;
}

.producto-item {
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 15px;
    display: flex;
    align-items: center;
    gap: 15px;
    transition: all 0.3s ease;
    position: relative;
}

.producto-item.pedido-pendiente {
    border-color: #dc3545;
    background: #fff5f5;
}

/* ✨ ESTILOS PARA CHECKBOXES */
.producto-checkbox-container {
    flex-shrink: 0;
}

.producto-checkbox {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.producto-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.producto-item.seleccionado {
    border-color: #28a745;
    background: #f8fff9;
}

.producto-info {
    flex-grow: 1;
}

.producto-info h4 {
    margin: 0 0 5px 0;
    font-size: 16px;
    font-weight: 600;
    color: #2c3e50;
}

.producto-label {
    cursor: pointer;
    margin: 0;
}

.categoria {
    font-size: 12px;
    color: #6c757d;
    text-transform: uppercase;
    font-weight: 500;
}

.stock-info {
    text-align: right;
    flex-shrink: 0;
}

.stock-actual {
    display: block;
    font-size: 14px;
    color: #495057;
    margin-bottom: 5px;
}

.pendiente {
    background: #dc3545;
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 10px;
    font-weight: 600;
    text-transform: uppercase;
}

.sin-productos {
    padding: 60px 20px;
    text-align: center;
    color: #6c757d;
}

.sin-productos img {
    width: 64px;
    height: 64px;
    opacity: 0.5;
    margin-bottom: 15px;
}

.sin-productos p {
    font-size: 18px;
    margin: 0;
}

.resumen-compras {
    background: linear-gradient(135deg, #2c3e50, #34495e);
    color: white;
    padding: 30px;
    margin-top: 20px;
    border-radius: 12px;
    text-align: center;
}

.resumen-compras h3 {
    margin: 0 0 25px 0;
    font-size: 24px;
    font-weight: 600;
}

.stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.stat-item .numero {
    font-size: 32px;
    font-weight: 700;
    color: #3498db;
    line-height: 1;
}

.stat-item .label {
    font-size: 14px;
    margin-top: 5px;
    opacity: 0.9;
}

/* ✨ ESTILOS PARA CONTROLES DE REABASTECIMIENTO */
.controles-reabastecimiento {
    border-top: 1px solid rgba(255,255,255,0.2);
    padding-top: 20px;
}

.controles-seleccion {
    display: flex;
    align-items: center;
    gap: 10px;
}

#btnReabastecer:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

@media (max-width: 768px) {
    .productos-grid {
        grid-template-columns: 1fr;
    }
    
    .seccion-header {
        flex-direction: column;
        text-align: center;
        gap: 10px;
    }
    
    .stats {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .controles-reabastecimiento .d-flex {
        flex-direction: column;
        gap: 15px;
        align-items: stretch;
    }
    
    .controles-seleccion {
        justify-content: center;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.producto-checkbox');
    const btnReabastecer = document.getElementById('btnReabastecer');
    const contadorSeleccionados = document.getElementById('contadorSeleccionados');
    const btnSeleccionarTodos = document.getElementById('seleccionarTodos');
    const btnLimpiarSeleccion = document.getElementById('limpiarSeleccion');
    const form = document.getElementById('reabastecerForm');

    // ✨ ACTUALIZAR CONTADOR Y ESTADO DEL BOTÓN
    function actualizarContador() {
        const seleccionados = document.querySelectorAll('.producto-checkbox:checked').length;
        contadorSeleccionados.textContent = `${seleccionados} seleccionados`;
        btnReabastecer.disabled = seleccionados === 0;
        
        // Actualizar clases visuales de productos seleccionados
        checkboxes.forEach(checkbox => {
            const productoItem = checkbox.closest('.producto-item');
            if (checkbox.checked) {
                productoItem.classList.add('seleccionado');
            } else {
                productoItem.classList.remove('seleccionado');
            }
        });
    }

    // ✨ EVENT LISTENERS PARA CHECKBOXES
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', actualizarContador);
    });

    // ✨ SELECCIONAR TODOS
    btnSeleccionarTodos.addEventListener('click', function() {
        checkboxes.forEach(checkbox => {
            checkbox.checked = true;
        });
        actualizarContador();
    });

    // ✨ LIMPIAR SELECCIÓN
    btnLimpiarSeleccion.addEventListener('click', function() {
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        actualizarContador();
    });

    // ✨ CONFIRMACIÓN ANTES DE ENVIAR
    form.addEventListener('submit', function(e) {
        const seleccionados = document.querySelectorAll('.producto-checkbox:checked').length;
        
        if (seleccionados === 0) {
            e.preventDefault();
            alert('Por favor seleccione al menos un producto para reabastecer.');
            return;
        }

        const confirmacion = confirm(
            `¿Está seguro de marcar como reabastecidos ${seleccionados} producto(s)?\n\n` +
            'Esto cambiará su estado a "disponible" y desaparecerán de esta lista.'
        );

        if (!confirmacion) {
            e.preventDefault();
        }
    });

    // Inicializar contador
    actualizarContador();
});
</script>
@endsection

