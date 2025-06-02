@extends('view_layout.app')

@section('content')
  <!-- saludo/usuario -->
<x-app-header backUrl="{{ route('vista.mozo_historial') }}" />

<div class="container py-20">
    <h5>Elija una mesa:</h5>
    <div class="d-flex justify-content-center">
        <div class="bg-dark rounded-4 p-4" style="width: 400px;">
            <div class="row row-cols-3 g-3">
                @foreach($mesas as $mesa)
                    @php
                        $info = $mesasInfo[$mesa->id_mesa] ?? ['ocupada' => false, 'cantidadDetalles' => 0];
                    @endphp
                    <div class="col d-flex justify-content-center">
                        @if(!$info['ocupada'])
                            <a href="{{ route('vista.mozo_pedido_mesa', $mesa->id_mesa) }}" class="btn p-0 border-0 bg-transparent" style="outline:none;">
                        @else
                            <button class="btn p-0 border-0 bg-transparent" disabled style="outline:none;">
                        @endif
                            <div class="rounded-circle d-flex flex-column align-items-center justify-content-center position-relative"
                                 style="width:70px; height:70px; background:#fffbe9; color:#4a2323; font-weight:bold; font-size:1.3rem;">
                                {{ $mesa->numero_mesa }}
                                @if($info['ocupada'])
                                    <span class="position-absolute top-0 end-0 translate-middle badge rounded-pill bg-danger">
                                        {{ $info['cantidadDetalles'] }}
                                    </span>
                                @endif
                            </div>
                        @if(!$info['ocupada'])
                            </a>
                        @else
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@endsection
