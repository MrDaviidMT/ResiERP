@props([
    'icono',
    'titulo',
    'href' => null,
    'tono' => 'neutro',
])
{{-- Acción de una fila de tabla, siempre solo icono. El estilo vive en app.css (.accion).
     aria-label además de title: un botón sin texto necesita nombre accesible, y el title
     no se lo da a un lector de pantalla de forma fiable. --}}
@if($href)
    <a href="{{ $href }}" title="{{ $titulo }}" aria-label="{{ $titulo }}"
       {{ $attributes->class(['accion', 'tono-'.$tono]) }}>
        <x-dynamic-component :component="'lucide-'.$icono" />
    </a>
@else
    {{-- merge y no un type fijo: así una vista puede pasar type="submit" cuando el
         botón envía su propio formulario --}}
    <button title="{{ $titulo }}" aria-label="{{ $titulo }}"
            {{ $attributes->merge(['type' => 'button'])->class(['accion', 'tono-'.$tono]) }}>
        <x-dynamic-component :component="'lucide-'.$icono" />
    </button>
@endif
