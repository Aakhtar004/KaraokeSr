@props(['backUrl' => null, 'userType' => null])

<nav class="navbar navbar-light bg-body-tertiary mb-3">
  <div class="container-fluid">
    @if($backUrl)
      <a href="{{ $backUrl }}" class="btn btn-outline-secondary">← Atrás</a>
    @else
      @php
        $h = now()->hour;
        $greeting = $h < 12
          ? 'Buenos días'
          : ($h < 18
             ? 'Buenas tardes'
             : 'Buenas noches');
      @endphp
      <span class="navbar-text">
        {{ $greeting }}{{ $userType ? ', '.$userType : '' }}
      </span>
    @endif
  </div>
</nav>
