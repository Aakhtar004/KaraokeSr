@extends('view_layout.app')

@section('content')
  <!-- saludo/usuario -->
  <x-app-header backUrl="{{ route('vista.user_menu') }}" />

  <div class="container">
    <h2 class="mb-4">Historial de Cocina</h2>
    <div class="row gy-3">
      @forelse($pedidos as $detalle)
        <div class="col-12">
          <div class="card p-3" style="border-radius: 20px; background: #fff3;">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h4 class="mb-1">Mesa {{ $detalle->pedido->mesa->numero_mesa ?? 'N/A' }}</h4>
                <small>Tiempo Aprox.: {{ $detalle->pedido->tiempo_aproximado ?? '20 min' }}</small>
              </div>
              <form action="{{ route('cocina.pedido.listo', $detalle->id_pedido_detalle) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-success">Listo</button>
              </form>
            </div>
            <table class="table table-bordered mt-3">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Cant.</th>
                  <th>Pedido</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>1</td>
                  <td>{{ $detalle->cantidad }}</td>
                  <td>{{ $detalle->producto->nombre ?? 'Producto no encontrado' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      @empty
        <p class="text-center">No hay pedidos asignados.</p>
      @endforelse
    </div>
  </div>
@endsection
