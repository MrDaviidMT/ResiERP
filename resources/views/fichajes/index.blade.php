@use('App\Enums\Permission', 'P')
<x-app-layout>
    <div class="cabecera-pagina">
            <h1 class="titulo-pagina">Gestión de Fichajes</h1>
            <a href="{{ route('fichajes.exportar', request()->only(['user_id', 'desde', 'hasta'])) }}" title="Descargar el registro de jornada en CSV"
               class="boton tono-neutro">
                <x-lucide-download />Exportar
            </a>
            @can(P::FichajesCrearOtros)
            <a href="{{ route('fichajes.create') }}" title="Nuevo fichaje manual" class="boton boton-primario">
                <x-lucide-plus />Nuevo Fichaje
            </a>
            @endcan
        </div>

    <div class="tarjeta p-6">
        <form method="GET" action="{{ route('fichajes.index') }}" class="filtros space-y-4" x-data="{ filtersOpen: true }">
            <button @click="filtersOpen = !filtersOpen" type="button" class="flex items-center gap-2 text-sm font-medium text-tinta-suave hover:text-tinta cursor-pointer mb-2">
                <span x-show="!filtersOpen" x-cloak><x-lucide-funnel-plus class="w-4 h-4" /></span>
                <span x-show="filtersOpen" x-cloak><x-lucide-funnel-x class="w-4 h-4" /></span>
                <span>Filtros</span>
            </button>
            <div :class="filtersOpen ? 'filter-expanded' : 'filter-collapsed'" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-{{ $usuarios->isNotEmpty() ? 3 : 2 }} gap-4">
                @if($usuarios->isNotEmpty())
                <div>
                    <label for="user_id" class="etiqueta-campo block">Usuario</label>
                    <select name="user_id" id="user_id" class="campo mt-1">
                        <option value="">-- Todos --</option>
                        @foreach($usuarios as $usuario)
                            <option value="{{ $usuario->id }}" {{ request('user_id') == $usuario->id ? 'selected' : '' }}>{{ $usuario->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div>
                    <label for="desde" class="etiqueta-campo block">Desde</label>
                    <input type="date" name="desde" id="desde" value="{{ request('desde') }}"
                           class="campo mt-1">
                </div>

                <div>
                    <label for="hasta" class="etiqueta-campo block">Hasta</label>
                    <input type="date" name="hasta" id="hasta" value="{{ request('hasta') }}"
                           class="campo mt-1">
                </div>
            </div>

            <div class="grupo-acciones grupo-acciones--final">
                <a href="{{ route('fichajes.index') }}" title="Limpiar todos los filtros" class="boton tono-neutro">
                    <x-lucide-eraser />Limpiar Filtros
                </a>
                <button type="submit" title="Buscar fichajes" class="boton tono-neutro">
                    <x-lucide-search />Buscar
                </button>
            </div>
            </div>
        </form>

        @if($fichajes->count() > 0)
            <div class="overflow-x-auto">
                <table class="tabla ">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Entrada</th>
                            <th>Salida</th>
                            <th>Duración</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($fichajes as $fichaje)
                            <tr>
                                <td data-label="Usuario">
                                    @can(P::EmpleadosVer)
                                    <a href="{{ route('users.show', $fichaje->user) }}" title="Ver perfil del usuario" class="enlace-fila">
                                        {{ $fichaje->user->name }}
                                    </a>
                                    @else
                                    <span class="font-medium text-tinta">{{ $fichaje->user->name }}</span>
                                    @endcan
                                </td>
                                <td data-label="Entrada">{{ $fichaje->entrada->format('d/m/Y H:i') }}</td>
                                <td data-label="Salida">{{ $fichaje->salida ? $fichaje->salida->format('d/m/Y H:i') : '—' }}</td>
                                <td data-label="Duración">
                                    @if($fichaje->salida)
                                        <span class="text-tinta-suave">{{ $fichaje->entrada->diffForHumans($fichaje->salida, ['parts' => 2, 'short' => false, 'syntax' => Carbon\CarbonInterface::DIFF_ABSOLUTE]) }}</span>
                                    @else
                                        <span class="text-exito font-semibold">En curso</span>
                                    @endif
                                </td>
                                <td class="acciones" data-label="Acciones">
                                    <div class="grupo-acciones">
                                        <x-accion icono="eye" titulo="Ver detalle" href="{{ route('fichajes.show', $fichaje) }}" />
                                        @if(! $fichaje->salida)
                                        @can('registrarSalida', $fichaje)
                                        <form action="{{ route('fichajes.salida', $fichaje) }}" method="POST">
                                            @csrf
                                            <button type="submit" title="Registrar salida" class="accion tono-aviso"><x-lucide-log-out  /></button>
                                        </form>
                                        @endcan
                                        @endif
                                        @can('corregir', $fichaje)
                                        <x-accion icono="pencil" titulo="Corregir fichaje" tono="marca" href="{{ route('fichajes.edit', $fichaje) }}" />
                                        @endcan
                                        @can('anular', $fichaje)
                                        <x-boton-motivo :action="route('fichajes.anular', $fichaje)" titulo="Anular fichaje" />
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-linea">
                {{ $fichajes->appends(request()->query())->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <x-lucide-clock class="w-12 h-12 mx-auto block mb-3 text-tinta-tenue" />
                <p class="text-sm text-tinta-tenue italic">No hay fichajes registrados.</p>
            </div>
        @endif
        <div class="px-4 pb-4"><x-registros-anulados :registros="$anulados" /></div>

    </div>
</x-app-layout>
