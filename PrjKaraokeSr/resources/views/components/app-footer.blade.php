@props(['tipo' => 'default', 'backUrl' => null])

<div class="fixed-bottom bg-white py-3 px-4 border-top">
    <div class="d-flex justify-content-between align-items-center">
        @switch($tipo)
            {{-- Caso 1: Agregar y Confirmar --}}
            @case('agregar-confirmar')
                <button type="button" class="btn btn-danger px-4 py-2 rounded-pill" style="background-color: #C8422E;">
                    Agregar
                </button>
                <button type="submit" class="btn btn-dark px-4 py-2 rounded-pill" style="background-color: #2E1B1B;">
                    Confirmar
                </button>
                @break

            {{-- Caso 2: Regresar y Confirmar --}}
            @case('regresar-confirmar')
                <a href="{{ $backUrl }}" class="btn btn-dark px-4 py-2 rounded-pill" style="background-color: #2E1B1B;">
                    Regresar
                </a>
                <button type="submit" class="btn btn-danger px-4 py-2 rounded-pill" style="background-color: #C8422E;">
                    Confirmar
                </button>
                @break

            {{-- Caso 3: Limpiar y Enviar con contador --}}
            @case('limpiar-enviar')
                <button type="button" class="btn btn-dark px-4 py-2 rounded-pill" style="background-color: #2E1B1B;">
                    Limpiar
                </button>
                <div class="d-flex align-items-center gap-3">
                    <div class="position-relative">
                        <i class="bi bi-list-ul fs-4"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            0
                        </span>
                    </div>
                    <button type="submit" class="btn btn-dark px-4 py-2 rounded-pill" style="background-color: #2E1B1B;">
                        Enviar
                    </button>
                </div>
                @break

            {{-- Caso por defecto --}}
            @default
                <div class="text-center w-100">
                    <p class="m-0">Footer por defecto</p>
                </div>
        @endswitch
    </div>
</div>