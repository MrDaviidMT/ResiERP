@use('App\Enums\Permission', 'P')
<x-app-layout :volver="route('incidencias.index')" volver-texto="Volver a incidencias" estrecho>
    <div class="tarjeta">
        <div class="p-6 space-y-6">

            <div class="cabecera-pagina">
                <div class="space-y-1">
                    <h1 class="titulo-pagina">{{ $incidencia->titulo }}</h1>
                    <p class="text-sm text-tinta-suave">Creado el {{ $incidencia->created_at->format('d/m/Y H:i') }}</p>
                </div>
                <div class="grupo-acciones">
                    @can('update', $incidencia)
                    <a href="{{ route('incidencias.edit', $incidencia) }}" title="Editar incidencia" class="boton tono-marca">
                        <x-lucide-pencil />Editar
                    </a>
                    @endcan
                    @can(P::IncidenciasAnular)
                    <x-boton-motivo :action="route('incidencias.anular', $incidencia)" titulo="Anular incidencia">Anular</x-boton-motivo>
                    @endcan
                    @can('update', $incidencia)
                    @if($incidencia->estado === 'pendiente')
                        <form action="{{ route('incidencias.estado', ['incidencia' => $incidencia->id, 'estado' => 'en_proceso']) }}" method="POST" id="form-atender">
                            @csrf
                            <button type="button" title="Atender incidencia" data-swal-submit data-title="Atender incidencia" data-text="¿Poner esta incidencia en proceso?" class="boton tono-aviso">
                                <x-lucide-cog />Atender
                            </button>
                        </form>
                    @endif
                    @if($incidencia->estado === 'en_proceso')
                        <form action="{{ route('incidencias.estado', ['incidencia' => $incidencia->id, 'estado' => 'resuelta']) }}" method="POST" id="form-resolver">
                            @csrf
                            <button type="button" title="Resolver incidencia" data-swal-submit data-title="Resolver incidencia" data-text="¿Marcar esta incidencia como resuelta?" class="boton tono-exito">
                                <x-lucide-circle-check-big />Resolver
                            </button>
                        </form>
                        <form action="{{ route('incidencias.estado', ['incidencia' => $incidencia->id, 'estado' => 'pendiente']) }}" method="POST" id="form-pendiente">
                            @csrf
                            <button type="button" title="Pasar a pendiente" data-swal-submit data-title="Pasar a pendiente" data-text="¿Volver a estado pendiente?" class="boton tono-neutro">
                                <x-lucide-rotate-ccw />Pasar a Pendiente
                            </button>
                        </form>
                    @endif
                    @if($incidencia->estado === 'resuelta')
                        <form action="{{ route('incidencias.estado', ['incidencia' => $incidencia->id, 'estado' => 'en_proceso']) }}" method="POST" id="form-reabrir">
                            @csrf
                            <button type="button" title="Reabrir incidencia" data-swal-submit data-title="Reabrir incidencia" data-text="¿Volver a poner en proceso?" class="boton tono-neutro">
                                <x-lucide-rotate-ccw />Reabrir
                            </button>
                        </form>
                    @endif
                    @endcan
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <span class="etiqueta tono-neutro">
                    @if($incidencia->categoria === 'sanitaria')
                        <x-lucide-heart class="w-3 h-3" /><span>Sanitaria</span>
                    @elseif($incidencia->categoria === 'infraestructura')
                        <x-lucide-wrench class="w-3 h-3" /><span>Infraestructura</span>
                    @elseif($incidencia->categoria === 'limpieza')
                        <x-lucide-sparkles class="w-3 h-3" /><span>Limpieza</span>
                    @elseif($incidencia->categoria === 'personal')
                        <x-lucide-users class="w-3 h-3" /><span>Personal</span>
                    @else
                        <x-lucide-inbox class="w-3 h-3" /><span>Otros</span>
                    @endif
                </span>

                <span class="etiqueta {{ $incidencia->estado === 'resuelta' ? 'tono-exito' : ($incidencia->estado === 'en_proceso' ? 'tono-aviso' : 'tono-neutro') }}">
                    <x-lucide-flag class="w-3 h-3" />
                    <span>{{ ucfirst(str_replace('_', ' ', $incidencia->estado)) }}</span>
                </span>

                <span class="etiqueta {{ $incidencia->prioridad === 'urgente' ? 'tono-peligro animate-pulse' : ($incidencia->prioridad === 'grave' ? 'tono-aviso' : 'tono-marca') }}">
                    <x-lucide-triangle-alert class="w-3 h-3" />
                    <span>{{ ucfirst($incidencia->prioridad) }}</span>
                </span>

                @if($incidencia->habitacion)
                    <span class="bg-marca-50 text-marca-800 border border-marca-200 text-xs font-bold px-3 py-1 rounded inline-flex items-center space-x-1">
                        <x-lucide-building-2 class="w-3 h-3" />
                        <span>Hab. {{ $incidencia->habitacion->numero }}</span>
                    </span>
                @endif
            </div>

            @if($incidencia->descripcion)
                <div>
                    <h3 class="etiqueta-campo mb-2">Descripción</h3>
                    <p class="text-tinta whitespace-pre-wrap">{{ $incidencia->descripcion }}</p>
                </div>
            @endif

            @can(P::ResidentesVer)
            @if($incidencia->residente)
                <div class="aviso tono-peligro items-center">
                    <x-lucide-user class="w-5 h-5 text-peligro shrink-0" />
                    <div>
                        <span class="etiqueta-campo">Residente implicado</span>
                        <a href="{{ route('residentes.show', $incidencia->residente) }}" title="Ver perfil del residente" class="block text-marca-700 font-bold hover:underline">
                            {{ $incidencia->residente->nombre }} {{ $incidencia->residente->apellidos }}
                        </a>
                    </div>
                </div>
            @endif
            @endcan

            @if($incidencia->fecha_resolucion)
                <div class="aviso tono-exito items-center">
                    <x-lucide-check-circle class="w-5 h-5 text-exito shrink-0" />
                    <div>
                        <span class="etiqueta-campo">Resuelta el</span>
                        <p class="text-sm font-semibold text-tinta">{{ $incidencia->fecha_resolucion->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            @endif

        </div>

        <div class="bg-fondo px-6 py-4 border-t border-linea flex justify-between items-center">
            <span class="text-xs text-tinta-tenue">ID: {{ $incidencia->id }} · Actualizado: {{ $incidencia->updated_at->format('d/m/Y H:i') }}</span>
        </div>
    </div>
</x-app-layout>
