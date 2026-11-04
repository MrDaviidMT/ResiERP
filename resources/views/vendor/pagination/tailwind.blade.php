{{-- Paginación con los botones del sistema (.accion y .boton), en lugar de la plantilla de Laravel
     con 111 líneas de utilidades --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Paginación" class="paginacion">
        <p class="text-sm text-tinta-suave">
            Mostrando {{ $paginator->firstItem() }} a {{ $paginator->lastItem() }} de {{ $paginator->total() }}
        </p>
        <div class="grupo-acciones">
            @if ($paginator->onFirstPage())
                <span class="accion tono-neutro" aria-disabled="true" aria-label="Página anterior"><x-lucide-chevron-left /></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="accion tono-neutro" aria-label="Página anterior"><x-lucide-chevron-left /></a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-1 text-tinta-suave">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="accion boton-primario" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="accion tono-neutro" aria-label="Ir a la página {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="accion tono-neutro" aria-label="Página siguiente"><x-lucide-chevron-right /></a>
            @else
                <span class="accion tono-neutro" aria-disabled="true" aria-label="Página siguiente"><x-lucide-chevron-right /></span>
            @endif
        </div>
    </nav>
@endif
