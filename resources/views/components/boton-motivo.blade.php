@props([
    'action',
    'titulo',
    'confirmar' => 'Anular',
    'icono' => 'ban',
    'tono' => 'peligro',
    'texto' => 'El registro se conservará como anulado y dejará de contar como vigente. Indica el motivo (mínimo 10 caracteres).',
])
{{-- Acción que exige motivo (anular, retirar...). El diálogo de app.js (data-swal-motivo)
     lo pide antes de enviar. La forma la decide el contenido, no un parámetro: sin texto es una
     acción de fila (solo icono) y con texto, un botón. Así no hay manera de que dos "Anular"
     salgan distintos. --}}
<form method="POST" action="{{ $action }}">
    @csrf
    <input type="hidden" name="motivo" value="">
    <button type="button" title="{{ $titulo }}" @if($slot->isEmpty()) aria-label="{{ $titulo }}" @endif
            data-swal-motivo data-title="{{ $titulo }}" data-text="{{ $texto }}" data-confirmar="{{ $confirmar }}"
            class="{{ $slot->isEmpty() ? 'accion' : 'boton' }} tono-{{ $tono }}">
        <x-dynamic-component :component="'lucide-'.$icono" />
        {{ $slot }}
    </button>
</form>
