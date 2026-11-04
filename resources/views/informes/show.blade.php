@use('App\Enums\Permission', 'P')
<x-app-layout :volver="route('informes.index')" volver-texto="Volver a informes" estrecho>
    <div class="tarjeta p-6">
        <div class="cabecera-pagina">
            <div>
                <h1 class="titulo-pagina">{{ $informe->titulo }}</h1>
                <p class="text-sm text-tinta-suave mt-1">
                    <span class="etiqueta tono-neutro">{{ ucfirst($informe->tipo) }}</span>
                    &middot; {{ $informe->user->name }} &middot; {{ $informe->created_at->format('d/m/Y H:i') }}
                </p>
            </div>
            <div class="grupo-acciones">
                @if($informe->pdf_path)
                <a href="{{ route('informes.pdf', $informe) }}" target="_blank" title="Descargar PDF" class="boton tono-neutro">
                    <x-lucide-file />PDF
                </a>
                @endif
                @can('update', $informe)
                <a href="{{ route('informes.edit', $informe) }}" title="Editar" class="boton tono-marca">
                    <x-lucide-pencil />Editar
                </a>
                @endcan
                @can('anular', $informe)
                <x-boton-motivo :action="route('informes.anular', $informe)" titulo="Anular informe"
                   >Anular</x-boton-motivo>
                @endcan
            </div>
        </div>

        @if($informe->residentes->isNotEmpty())
        <div class="mb-6">
            <h3 class="text-sm font-semibold text-tinta-suave uppercase mb-2">Residentes relacionados</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($informe->residentes as $res)
                <span class="etiqueta tono-neutro">{{ $res->apellidos }}, {{ $res->nombre }}</span>
                @endforeach
            </div>
        </div>
        @endif

        <div class="prose max-w-none border-t border-linea pt-6">
            {!! nl2br(e($informe->contenido)) !!}
        </div>
    </div>
</x-app-layout>
