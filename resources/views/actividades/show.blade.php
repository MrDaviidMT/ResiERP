@use('App\Enums\Permission', 'P')
<x-app-layout :volver="route('actividades.index')" volver-texto="Volver a actividades" estrecho>
    <div class="tarjeta p-6">
        <div class="cabecera-pagina">
            <div>
                <h1 class="titulo-pagina">{{ $actividad->titulo }}</h1>
                <p class="text-sm text-tinta-suave mt-1">
                    <x-lucide-calendar class="w-4 h-4 inline" /> {{ $actividad->fecha ? $actividad->fecha->format('d/m/Y') : 'Sin fecha' }}
                    @if($actividad->hora) <x-lucide-clock class="w-4 h-4 inline ml-2" /> {{ $actividad->hora->format('H:i') }} @endif
                    &middot; Creado por {{ $actividad->user?->name ?? '—' }}
                </p>
            </div>
            <div class="grupo-acciones">
                @can(P::ActividadesGestionar)
                <a href="{{ route('actividades.edit', $actividad) }}" title="Editar" class="boton tono-marca">
                    <x-lucide-pencil />Editar
                </a>
                <x-boton-motivo :action="route('actividades.anular', $actividad)" titulo="Anular actividad">Anular</x-boton-motivo>
                @endcan
            </div>
        </div>

        @if($actividad->descripcion)
        <div class="mb-6">
            <h3 class="text-sm font-semibold text-tinta-suave uppercase mb-2">Descripción</h3>
            <p class="text-tinta">{{ $actividad->descripcion }}</p>
        </div>
        @endif

        @if($actividad->residentes->isNotEmpty())
        <div class="mb-6">
            <h3 class="text-sm font-semibold text-tinta-suave uppercase mb-2">Participantes</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($actividad->residentes as $res)
                <span class="etiqueta tono-neutro">{{ $res->apellidos }}, {{ $res->nombre }}</span>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</x-app-layout>
