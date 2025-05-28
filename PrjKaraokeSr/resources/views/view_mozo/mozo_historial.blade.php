@extends('view_layout.app')

@section('content')
  <!-- saludo/usuario -->
  <x-app-header backUrl="{{ route('vista.user_menu') }}" />

  <div class="container">
    <h2 class="mb-4">Historial de Pedidos del Mozo</h2>
    <div class="row gy-3">
      @foreach($pedidos as $pedido)
        <div class="col-12">
          <div class="card mb-3 p-3" style="border-radius: 20px; background: #fff3;">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <strong>M°{{ $pedido->mesa->numero_mesa ?? $pedido->id_mesa }}</strong>
              </div>
              <div class="d-flex gap-2">
                <a href="{{ route('pedidos.ver', $pedido->id_pedido) }}" class="btn btn-outline-dark">
                  <i class="bi bi-eye"></i> Ver
                </a>
                <a href="{{ route('pedidos.editar', $pedido->id_pedido) }}" class="btn btn-danger">
                  Editar
                </a>
                <form action="{{ route('pedidos.finalizar', $pedido->id_pedido) }}" method="POST" style="display:inline;">
                  @csrf
                  <button type="submit" class="btn btn-danger">Finalizar</button>
                </form>
                <form action="{{ route('pedidos.eliminar', $pedido->id_pedido) }}" method="POST" style="display:inline;">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-dark">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </div>
            </div>
          </div>
        </div>
      @endforeach
    </div>
    <!-- Botón para agregar nuevo pedido -->
    <a href="{{ route('pedidos.crear') }}" class="btn btn-danger rounded-circle position-fixed" style="bottom: 30px; right: 30px; width: 60px; height: 60px; font-size: 2rem; display: flex; align-items: center; justify-content: center;">
      +
    </a>
  </div>
@endsection
