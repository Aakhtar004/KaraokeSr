@extends('view_layout.app')

@section('content')
  <!-- saludo/usuario -->
  <x-app-header backUrl="{{ route('vista.user_menu') }}" />

  <div class="container">
    <h2 class="mb-4">Historial de Pedidos del Mozo</h2>
    <div class="row gy-3">
      @foreach($pedidos as $pedido)
        @php
          // Cuenta los detalles listos para entrega
          $detallesListos = $pedido->detalles->where('estado_item', 'LISTO_PARA_ENTREGA')->count();
        @endphp
        <div class="col-12">
          <div class="card mb-3 p-3" style="border-radius: 20px; background: #fff3;">
            <div class="d-flex align-items-center justify-content-between">
              <div class="position-relative" style="min-width: 70px;">
                <strong>Mesa {{ $pedido->mesa->numero_mesa ?? $pedido->id_mesa }}</strong>
                @if($detallesListos > 0)
                  <!-- Bolita de notificacion mas a la derecha -->
                  <span class="position-absolute top-0" style="right:-15px; font-size:0.8rem;">
                    <span class="badge rounded-pill bg-danger">
                      {{ $detallesListos }}
                    </span>
                  </span>
                @endif
              </div>
              <div class="d-flex gap-2">
                <a href="{{ route('pedidos.ver', $pedido->id_pedido) }}" class="btn btn-outline-dark">
                  Ver
                </a>
                @php
                  // Verificar si el pedido tiene comprobante (está realmente pagado)
                  $tienePagos = $pedido->comprobante !== null;
                  // El estado real del pedido debe coincidir con la existencia del comprobante
                  $estadoReal = $tienePagos ? 'PAGADO' : $pedido->estado_pedido;
                  // Verificar si todos los productos están listos para entrega
                  $todosListos = $pedido->detalles->every(function($detalle) {
                      return $detalle->estado_item === 'LISTO_PARA_ENTREGA';
                  });
                @endphp
                
                @if(!$tienePagos && $estadoReal === 'PENDIENTE')
                  <a href="{{ route('pedidos.editar', $pedido->id_pedido) }}" class="btn btn-danger">
                    Editar
                  </a>
                  
                  @if($todosListos)
                    <form action="{{ route('pedidos.finalizar', $pedido->id_pedido) }}" method="POST" style="display:inline;" onsubmit="return confirmarFinalizacion(event)">
                      @csrf
                      <button type="submit" class="btn btn-success">Finalizar</button>
                    </form>
                  @else
                    <button type="button" class="btn btn-secondary" disabled title="Algunos productos aún no están listos">
                      Finalizar
                    </button>
                  @endif
                  
                  <form action="{{ route('pedidos.eliminar', $pedido->id_pedido) }}" method="POST" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-dark" onclick="return confirm('¿Estás seguro de eliminar este pedido?')">
                      Eliminar
                    </button>
                  </form>
                @else
                  <span class="badge bg-success">PAGADO</span>
                  @if($pedido->comprobante)
                    <a href="{{ route('factura.vista_previa', $pedido->comprobante->id_comprobante) }}" class="btn btn-outline-primary btn-sm">
                      Ver Comprobante
                    </a>
                  @endif
                @endif
              </div>
            </div>
          </div>
        </div>
      @endforeach
    </div>
    <!-- Boton para agregar nuevo pedido -->
    <a href="{{ route('vista.mozo_mesa') }}" class="btn btn-danger rounded-circle position-fixed" style="bottom: 30px; right: 30px; width: 60px; height: 60px; font-size: 2rem; display: flex; align-items: center; justify-content: center;">
      +
    </a>
  </div>

  <script>
  function confirmarFinalizacion(event) {
      if (!confirm('Estas seguro?')) {
          event.preventDefault();
          return false;
      }
      return true;
  }
  </script>
@endsection
