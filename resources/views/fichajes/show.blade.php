@use('App\Enums\Permission', 'P')
<x-app-layout :volver="route('fichajes.index')" volver-texto="Volver a fichajes" estrecho>
    <h1 class="titulo-pagina mb-6">Fichaje: {{ $fichaje->user->name }}</h1>


    <div class="tarjeta p-6 mb-6">
        <div class="cabecera-pagina">
            <h2 class="titulo-seccion">Detalle del fichaje</h2>
            <div class="grupo-acciones">
                @can('corregir', $fichaje)
                <a href="{{ route('fichajes.edit', $fichaje) }}" title="Corregir fichaje" class="boton tono-marca">
                    <x-lucide-pencil />Corregir
                </a>
                @endcan
                @if(! $fichaje->salida)
                @can('registrarSalida', $fichaje)
                <form action="{{ route('fichajes.salida', $fichaje) }}" method="POST">
                    @csrf
                    <button type="submit" title="Registrar salida" class="boton tono-aviso">
                        <x-lucide-log-out /> Registrar Salida
                    </button>
                </form>
                @endcan
                @endif
                @can('anular', $fichaje)
                <x-boton-motivo :action="route('fichajes.anular', $fichaje)" titulo="Anular fichaje">Anular</x-boton-motivo>
                @endcan
            </div>
        </div>

        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <dt class="etiqueta-campo">Usuario</dt>
                <dd class="text-sm text-tinta mt-1">
                    @can(P::EmpleadosVer)
                    <a href="{{ route('users.show', $fichaje->user) }}" title="Ver perfil del usuario" class="text-marca-700 hover:text-marca-800 font-medium">{{ $fichaje->user->name }}</a>
                    @else
                    <span class="font-medium text-tinta">{{ $fichaje->user->name }}</span>
                    @endcan
                </dd>
            </div>
            <div>
                <dt class="etiqueta-campo">Email</dt>
                <dd class="text-sm text-tinta mt-1">{{ $fichaje->user->email }}</dd>
            </div>
            <div>
                <dt class="etiqueta-campo">Entrada</dt>
                <dd class="text-sm text-tinta mt-1">{{ $fichaje->entrada->format('d/m/Y H:i:s') }}</dd>
            </div>
            <div>
                <dt class="etiqueta-campo">Salida</dt>
                <dd class="text-sm text-tinta mt-1">
                    @if($fichaje->salida)
                        {{ $fichaje->salida->format('d/m/Y H:i:s') }}
                    @else
                        <span class="text-exito font-semibold">En curso</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="etiqueta-campo">Duración</dt>
                <dd class="text-sm text-tinta mt-1">
                    @if($fichaje->salida)
                        {{ $fichaje->entrada->diffForHumans($fichaje->salida, ['parts' => 3, 'short' => false, 'syntax' => Carbon\CarbonInterface::DIFF_ABSOLUTE]) }}
                    @else
                        <span class="text-exito font-semibold">En curso</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="etiqueta-campo">IP</dt>
                <dd class="text-sm text-tinta mt-1">{{ $fichaje->ip_address ?? '—' }}</dd>
            </div>
            @if($fichaje->notas)
            <div class="md:col-span-2">
                <dt class="etiqueta-campo">Notas</dt>
                <dd class="text-sm text-tinta mt-1">{{ $fichaje->notas }}</dd>
            </div>
            @endif
        </dl>
    </div>

    @if($fichaje->correcciones->isNotEmpty())
    <div class="bg-fondo rounded-lg border border-linea p-4">
        <h3 class="text-sm font-bold text-tinta mb-2"><x-lucide-history class="w-4 h-4 inline mr-1" /> Historial de correcciones</h3>
        <ul class="space-y-2 text-xs text-tinta-suave">
            @foreach($fichaje->correcciones as $correccion)
            <li class="border-l-2 border-marca-200 pl-2">
                <span class="block">
                    @if($correccion->entrada_anterior)
                        Entrada {{ $correccion->entrada_anterior->format('d/m/Y H:i') }} → {{ $correccion->entrada_nueva->format('d/m/Y H:i') }} ·
                        Salida {{ $correccion->salida_anterior?->format('d/m/Y H:i') ?? '—' }} → {{ $correccion->salida_nueva?->format('d/m/Y H:i') ?? '—' }}
                    @else
                        Alta manual: entrada {{ $correccion->entrada_nueva->format('d/m/Y H:i') }} · salida {{ $correccion->salida_nueva?->format('d/m/Y H:i') ?? '—' }}
                    @endif
                </span>
                <span class="block">Por {{ $correccion->corregidoPor?->name ?? '—' }} el {{ $correccion->created_at->format('d/m/Y H:i') }}. Motivo: {{ $correccion->motivo }}</span>
            </li>
            @endforeach
        </ul>
    </div>
    @endif
</x-app-layout>
