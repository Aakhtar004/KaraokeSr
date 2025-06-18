@extends('view_layout.app')

@push('styles')
<link href="{{ asset('css/mozo_historial.css') }}" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
@endpush

@section('content')
<div class="mozo-header">
    <a href="{{ route('vista.user_menu') }}" class="mozo-header-back">
        <span class="mozo-header-back-icon">&#8592;</span>
    </a>
    <div class="mozo-header-content">
        <div class="mozo-header-title">Historial de Pedidos</div>
        <div class="mozo-header-subtitle">Mozo</div>
    </div>
</div>

<div class="container">
    <div class="row gy-3">
      @foreach($pedidos as $pedido)
        @php
          // Cuenta los detalles listos para entrega
          $detallesListos = $pedido->detalles->where('estado_item', 'LISTO_PARA_ENTREGA')->count();
        @endphp
        <div class="col-12">
          <div class="card mb-3">
            <div class="card-body">
              <div class="mesa-numero">Mesa {{ $pedido->mesa->numero_mesa ?? $pedido->id_mesa }}</div>
              <div class="d-flex align-items-center justify-content-center gap-2">
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
                <a href="{{ route('pedidos.ver', $pedido->id_pedido) }}" class="btn btn-ver" title="Ver pedido">
                  <i class="fas fa-eye"></i>
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
                  <a href="{{ route('pedidos.editar', $pedido->id_pedido) }}" class="btn btn-editar" title="Editar pedido">
                    <i class="fas fa-edit"></i> Editar
                  </a>
                  
                  @if($todosListos)
                    <form action="{{ route('pedidos.finalizar', $pedido->id_pedido) }}" method="POST" style="display:inline;" onsubmit="return confirmarFinalizacion(event)">
                      @csrf
                      <button type="submit" class="btn btn-finalizar" title="Finalizar pedido"><i class="fas fa-check"></i> Finalizar</button>
                    </form>
                  @else
                    <button type="button" class="btn btn-finalizar" disabled title="Algunos productos aún no están listos">
                      <i class="fas fa-check"></i> Finalizar
                    </button>
                  @endif
                  
                  <form action="{{ route('pedidos.eliminar', $pedido->id_pedido) }}" method="POST" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-eliminar" onclick="return confirm('¿Estás seguro de eliminar este pedido?')" title="Eliminar pedido">
                      <i class="fas fa-trash"></i>
                    </button>
                  </form>
                @else
                  <span class="badge badge-pagado">PAGADO</span>
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
    <a href="{{ route('vista.mozo_mesa') }}" class="btn btn-agregar rounded-circle position-fixed" style="bottom: 30px; right: 30px; width: 60px; height: 60px; font-size: 2rem; display: flex; align-items: center; justify-content: center;">
      <i class="fas fa-plus"></i>
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
