@props(['rol'])
{{-- Etiqueta de un puesto. El color de cada rol está en app.css (.rol-*) --}}
@if($rol)
    <span class="etiqueta rol-{{ $rol->value }}">{{ $rol->label() }}</span>
@endif
