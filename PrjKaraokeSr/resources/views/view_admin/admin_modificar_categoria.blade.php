@extends('view_layout.app')

@section('content')
  <!-- saludo/usuario -->
  <x-app-header backUrl="{{ route('vista.user_menu') }}" />

  <div class="container">
    <h2 class="mb-4">Seleccione una Categoría</h2>
    <div class="row gy-3">
      @foreach($categorias as $cat)
        <div class="col-3">
          <a
            href="{{ route('vista.admin_modificar_producto', $cat->id_categoria_producto) }}"
            class="btn btn-outline-primary w-100"
            style="aspect-ratio:1/1; display:flex; align-items:center; justify-content:center;"
          >
            {{ $cat->nombre }}
          </a>
        </div>
      @endforeach
    </div>
  </div>
@endsection
