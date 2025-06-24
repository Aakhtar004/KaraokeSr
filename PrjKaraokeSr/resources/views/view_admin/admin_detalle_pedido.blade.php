@extends('view_layout.app')

@section('content')
<x-app-header backUrl="{{ route('vista.admin_historial_ventas') }}" title="Detalle de Pedidos - {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}" />

<div class="container mt-4 mb-5 pb-5">
    @if($pedidos->isEmpty())
        <div class="alert alert-info text-center">
            No hay pedidos para esta fecha.
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID Pedido</th>
                        <th>Mesa</th>
                        <th>Productos</th>
                        <th>Método de Pago</th>
                        <th>Total Pedido</th>
                        <th>Fecha/Hora</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pedidos as $pedido)
                        <tr>
                            <td><strong>#{{ $pedido->id_pedido }}</strong></td>
                            <td>Mesa {{ $pedido->mesa->numero_mesa }}</td>
                            <td>
                                <ul class="list-unstyled mb-0">
                                    @foreach($pedido->detalles as $detalle)
                                        <li>
                                            <small>
                                                {{ $detalle->cantidad }}x {{ $detalle->producto->nombre }} 
                                                - S/ {{ number_format($detalle->precio_unitario_momento, 2) }} c/u
                                                = <strong>S/ {{ number_format($detalle->subtotal, 2) }}</strong>
                                            </small>
                                        </li>
                                    @endforeach
                                </ul>
                            </td>
                            <td>
                                @if($pedido->comprobante)
                                    <span class="badge bg-success">{{ $pedido->comprobante->metodo_pago }}</span>
                                @else
                                    <span class="badge bg-warning">Pendiente</span>
                                @endif
                            </td>
                            <td><strong>S/ {{ number_format($pedido->total_pedido, 2) }}</strong></td>
                            <td>
                                <small>{{ $pedido->fecha_hora_pedido->format('H:i:s') }}</small>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0">Resumen del día - {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center">
                            <h5 class="text-primary">{{ $pedidos->count() }}</h5>
                            <small>Total de pedidos</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h5 class="text-success">S/ {{ number_format($pedidos->sum('total_pedido'), 2) }}</h5>
                            <small>Total vendido</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h5 class="text-info">{{ $pedidos->whereNotNull('comprobante')->count() }}</h5>
                            <small>Pedidos pagados</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h5 class="text-warning">{{ $pedidos->whereNull('comprobante')->count() }}</h5>
                            <small>Pedidos pendientes</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
