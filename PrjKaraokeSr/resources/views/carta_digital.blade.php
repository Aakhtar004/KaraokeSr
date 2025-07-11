<!DOCTYPE html>
<html lang="es" class="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carta Digital - Salón Rojo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>    
    <link rel="stylesheet" href="{{ asset('css/carta_digital.css') }}">
</head>
<body class="relative">
    <!-- Header -->
    <header class="sticky top-0 z-50 shadow-lg border-b-2 border-[#d05e4a]" style="background-color: var(--color-header-bg);">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <div class="theme-toggle" id="themeToggle" aria-label="Cambiar tema">
                    <i class="fas fa-moon"></i>
                </div>
                
                <div class="flex items-center justify-center">
                    <div class="dragon-logo mr-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-[#c4361d]">
                            <path d="M13 2.5V5c0 1.1-.9 2-2 2h-1a2 2 0 0 0-2 2v1a2 2 0 0 1-2 2h-.5"></path>
                            <path d="M7 16.5H4.5a2 2 0 0 1-2-2v-1c0-1.1.9-2 2-2H7a2 2 0 0 0 2-2v-1a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v1a2 2 0 0 0 2 2h4.5"></path>
                            <path d="M19 7v9"></path>
                            <path d="M22 10h-5.5a2 2 0 0 0-2 2v1a2 2 0 0 1-2 2h-1a2 2 0 0 0-2 2v2.5"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="header-title text-3xl md:text-4xl font-bold text-center text-[#c4361d]">
                            SALÓN ROJO
                        </h1>
                        <p class="text-center text-[#d05e4a] text-sm mt-1">Carta Digital</p>
                    </div>
                </div>
                
                <div class="w-10"></div>
            </div>
        </div>
    </header>

    <!-- Categories Navigation -->
    <div class="sticky top-[84px] z-40 shadow-md" style="background-color: var(--color-header-bg);">
        <div class="category-scroll overflow-x-auto py-3 px-2">
            <div class="flex space-x-2 min-w-max px-2">
                @if(!empty($promocionesParaCarta))
                <button class="category-btn active px-4 py-2 rounded-full text-sm font-medium bg-[#d05e4a] text-[#fffbe8] border border-transparent hover:border-[#c4361d] transition-all whitespace-nowrap" data-category="promociones">
                    <i class="fas fa-percentage mr-1"></i> Promociones
                </button>
                @endif
                
                @if($categorias && $categorias->count() > 0)
                    @foreach($categorias as $categoria)
                    @if(isset($productosPorCategoria[$categoria->nombre]) && count($productosPorCategoria[$categoria->nombre]) > 0)
                    <button class="category-btn px-4 py-2 rounded-full text-sm font-medium border border-[#d05e4a] hover:border-[#c4361d] transition-all whitespace-nowrap {{ empty($promocionesParaCarta) && $loop->first ? 'active bg-[#d05e4a] text-[#fffbe8] border-transparent' : '' }}" 
                            data-category="{{ strtolower(str_replace([' ', 'á', 'é', 'í', 'ó', 'ú'], ['', 'a', 'e', 'i', 'o', 'u'], $categoria->nombre)) }}"
                            @if(empty($promocionesParaCarta) && $loop->first)
                                style=""
                            @else
                                style="background-color: var(--color-cream); color: var(--color-red-dark);"
                            @endif>
                        <i class="{{ isset($iconos[$categoria->nombre]) ? $iconos[$categoria->nombre] : 'fas fa-utensils' }} mr-1"></i>
                        {{ $categoria->nombre }}
                    </button>
                    @endif
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-6 pb-20">
        @if(!empty($promocionesParaCarta))
        <!-- Promociones Section -->
        <section id="promociones" class="menu-section active">
            <div class="flex items-center mb-6 section-title pb-2">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-[#d05e4a] to-[#c4361d] flex items-center justify-center mr-3 shadow-md">
                    <i class="fas fa-percentage text-[#fffbe8] text-xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-[#c4361d]">Promociones Especiales</h2>
                <span class="ml-3 bg-[#c4361d] text-[#fffbe8] px-2 py-1 rounded-full text-sm">{{ count($promocionesParaCarta) }} ofertas</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($promocionesParaCarta as $promocion)
                    <div class="product-card rounded-lg overflow-hidden shadow-md relative {{ $promocion->agotada ? 'out-of-stock' : '' }}">
                        @if($promocion->promo_badge)
                            <div class="promo-badge">{{ $promocion->promo_badge }}</div>
                        @endif
                        @if($promocion->imagen_url)
                            <div class="product-image" style="background-image: url('{{ $promocion->imagen_url }}')"></div>
                        @else
                            <div class="product-image bg-gradient-to-br from-[#d05e4a] to-[#c4361d] flex items-center justify-center">
                                <i class="fas fa-percentage text-[#fffbe8] text-4xl"></i>
                            </div>
                        @endif
                        <div class="p-4">
                            <div class="flex justify-content-between items-start">
                                <h3 class="font-semibold text-lg mb-2 text-[#2d1a12]">{{ $promocion->nombre }}</h3>
                                <span class="text-[#c4361d] font-bold text-lg">{{ $promocion->promo_badge }}</span>
                            </div>
                            @if($promocion->descripcion)
                                <p class="text-secondary text-sm mb-2">{{ $promocion->descripcion }}</p>
                            @endif
                            <div class="text-xs text-gray-500">
                                Incluye: {{ collect($promocion->productos_incluidos)->pluck('nombre')->join(', ') }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
        @endif

        @if($categorias && $categorias->count() > 0)
            @foreach($categorias as $categoria)
            @if(isset($productosPorCategoria[$categoria->nombre]) && count($productosPorCategoria[$categoria->nombre]) > 0)
            <!-- {{ $categoria->nombre }} Section -->
            <section id="{{ strtolower(str_replace([' ', 'á', 'é', 'í', 'ó', 'ú'], ['', 'a', 'e', 'i', 'o', 'u'], $categoria->nombre)) }}" class="menu-section {{ (empty($promocionesParaCarta) && $loop->first) ? 'active' : '' }}">
                <div class="flex items-center mb-6 section-title pb-2">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-[#d05e4a] to-[#c4361d] flex items-center justify-center mr-3 shadow-md">
                        <i class="{{ isset($iconos[$categoria->nombre]) ? $iconos[$categoria->nombre] : 'fas fa-utensils' }} text-[#fffbe8] text-xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-[#c4361d]">{{ $categoria->nombre }}</h2>
                    <span class="ml-3 bg-gray-200 text-gray-700 px-2 py-1 rounded-full text-sm">{{ count($productosPorCategoria[$categoria->nombre]) }} productos</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($productosPorCategoria[$categoria->nombre] as $producto)
                        <div class="product-card rounded-lg overflow-hidden shadow-md relative {{ ($categoria->nombre !== 'Cocteles' && isset($producto->stock) && $producto->stock == 0) ? 'out-of-stock' : '' }}">
                            {{-- Estado de stock --}}
                            @if($categoria->nombre !== 'Cocteles' && isset($producto->stock) && $producto->stock == 0)
                                <div class="agotado-overlay">
                                    <span>AGOTADO</span>
                                </div>
                            @elseif($categoria->nombre === 'Cocteles' && isset($producto->estado) && $producto->estado == 0)
                                <div class="agotado-overlay">
                                    <span>NO DISPONIBLE</span>
                                </div>
                            @endif
                            
                            {{-- Imagen MODIFICADA --}}
                            @if($categoria->nombre === 'Baldes')
                                {{-- Para baldes: siempre mostrar el icono del balde --}}
                                <div class="product-image bg-gradient-to-br from-[#d05e4a] to-[#c4361d] flex items-center justify-center">
                                    <i class="fas fa-ice-cream text-[#fffbe8] text-4xl"></i>
                                </div>
                            @elseif(isset($producto->imagen_url) && $producto->imagen_url)
                                {{-- Para otras categorías: mostrar imagen si existe --}}
                                <div class="product-image" style="background-image: url('{{ $producto->imagen_url }}')"></div>
                            @else
                                {{-- Para otras categorías: mostrar icono de la categoría si no hay imagen --}}
                                <div class="product-image bg-gradient-to-br from-[#d05e4a] to-[#c4361d] flex items-center justify-center">
                                    <i class="{{ isset($iconos[$categoria->nombre]) ? $iconos[$categoria->nombre] : 'fas fa-utensils' }} text-[#fffbe8] text-4xl"></i>
                                </div>
                            @endif
                            
                            <div class="p-4">
                                <div class="flex justify-content-between items-start">
                                    <h3 class="font-semibold text-lg mb-2 text-[#2d1a12]">{{ $producto->nombre ?? 'Producto sin nombre' }}</h3>
                                    @if(isset($producto->en_promocion) && $producto->en_promocion)
                                        <div class="flex flex-col items-end">
                                            <span class="text-gray-500 line-through text-sm">S/ {{ number_format($producto->precio_original ?? 0, 2) }}</span>
                                            <span class="text-[#c4361d] font-bold text-lg">S/ {{ number_format($producto->precio_promocion ?? 0, 2) }}</span>
                                            <span class="bg-red-500 text-white px-2 py-1 rounded text-xs">-{{ $producto->porcentaje_descuento ?? 0 }}%</span>
                                        </div>
                                    @elseif(isset($producto->es_personalizado) && $producto->es_personalizado)
                                        <span class="text-[#c4361d] font-bold text-lg">A consultar</span>
                                    @else
                                        <span class="text-[#c4361d] font-bold text-lg">S/ {{ number_format($producto->precio_unitario ?? 0, 2) }}</span>
                                    @endif
                                </div>
                                @if(isset($producto->descripcion) && $producto->descripcion)
                                    <p class="text-secondary text-sm mb-2">{{ $producto->descripcion }}</p>
                                @endif
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-gray-500">{{ $producto->unidad_medida ?? 'Unidad' }}</span>
                                    @if($categoria->nombre === 'Cocteles')
                                        <span class="text-{{ isset($producto->estado) && $producto->estado == 1 ? 'green' : 'red' }}-600">
                                            {{ isset($producto->estado) && $producto->estado == 1 ? 'Disponible' : 'No disponible' }}
                                        </span>
                                    @elseif(isset($producto->es_personalizado) && $producto->es_personalizado)
                                        <span class="text-green-600">Disponible</span>
                                    @else
                                        <span class="text-{{ isset($producto->stock) && $producto->stock > 5 ? 'green' : (isset($producto->stock) && $producto->stock > 0 ? 'yellow' : 'red') }}-600">
                                            @if(isset($producto->stock) && $producto->stock > 0)
                                                {{ $producto->stock }} disponibles
                                            @else
                                                Agotado
                                            @endif
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
            @endif
            @endforeach
        @endif
    </main>

    <!-- Footer -->
    <footer class="bg-[#c4361d] text-[#fffbe8] py-4 mt-10">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row md:justify-between">
                <div class="mb-4 md:mb-0">
                    <h3 class="text-lg font-semibold mb-2">Contáctanos</h3>
                    <p class="text-sm"><i class="fas fa-phone-alt mr-2"></i>Teléfono: +51 123 456 789</p>
                    <p class="text-sm"><i class="fas fa-envelope mr-2"></i>Email: contacto@salonrojo.com</p>
                </div>
                <div class="mb-4 md:mb-0">
                    <h3 class="text-lg font-semibold mb-2">Síguenos en redes sociales</h3>
                    <div class="flex space-x-4">
                        <a href="#" class="text-[#fffbe8] hover:text-[#d05e4a] transition-colors">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-[#fffbe8] hover:text-[#d05e4a] transition-colors">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="text-[#fffbe8] hover:text-[#d05e4a] transition-colors">
                            <i class="fab fa-twitter"></i>
                        </a>
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-2">Horarios</h3>
                    <p class="text-sm">Lunes a Viernes: 10:00 AM - 10:00 PM</p>
                    <p class="text-sm">Sábados: 11:00 AM - 11:00 PM</p>
                    <p class="text-sm">Domingos: Cerrado</p>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Theme toggle script
        const themeToggle = document.getElementById('themeToggle');
        themeToggle.addEventListener('click', () => {
            document.documentElement.classList.toggle('dark');
            themeToggle.querySelector('i').classList.toggle('fa-sun');
            themeToggle.querySelector('i').classList.toggle('fa-moon');
        });

        // Category filter script
        const categoryBtns = document.querySelectorAll('.category-btn');
        const menuSections = document.querySelectorAll('.menu-section');

        categoryBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const category = btn.getAttribute('data-category');

                // Update active button style
                categoryBtns.forEach(b => b.classList.remove('active', 'bg-[#d05e4a]', 'text-[#fffbe8]', 'border-transparent'));
                btn.classList.add('active', 'bg-[#d05e4a]', 'text-[#fffbe8]', 'border-transparent');

                // Show/Hide menu sections
                menuSections.forEach(section => {
                    const sectionId = section.getAttribute('id');
                    
                    if (category === 'promociones') {
                        // Si se selecciona promociones, solo mostrar la sección de promociones
                        if (sectionId === 'promociones') {
                            section.classList.add('active');
                        } else {
                            section.classList.remove('active');
                        }
                    } else {
                        // Para otras categorías, mostrar solo la sección correspondiente
                        if (sectionId === category) {
                            section.classList.add('active');
                        } else {
                            section.classList.remove('active');
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>