@props([
    'name',
    'accept' => '',
    'texto' => 'Elegir archivo',
    'vacio' => 'Ningún archivo seleccionado',
])
{{-- Campo de archivo con el botón en español.

     El botón del <input type="file"> nativo lo escribe el navegador en SU idioma ("Browse",
     "Choose File"...) y no se puede traducir ni desde el HTML ni desde el CSS: no forma parte
     del documento. La solución estándar es ocultar el control (sr-only, que lo mantiene
     accesible al teclado y a los lectores de pantalla) y usar una etiqueta propia.

     El nombre del archivo elegido lo escribe resources/js/app.js con un listener delegado,
     nunca con onchange en el HTML: la CSP lo bloquearía (hallazgo S17). --}}
<div class="mt-1 flex items-center gap-3" data-campo-archivo>
    <label class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 rounded-md bg-marca-50 text-marca-700 text-sm font-semibold hover:bg-marca-100 transition whitespace-nowrap">
        <x-lucide-upload class="w-4 h-4" />
        {{ $texto }}
        <input type="file" name="{{ $name }}" @if($accept) accept="{{ $accept }}" @endif class="sr-only">
    </label>
    <span class="text-sm text-tinta-suave truncate" data-campo-archivo-nombre data-vacio="{{ $vacio }}">{{ $vacio }}</span>
</div>
