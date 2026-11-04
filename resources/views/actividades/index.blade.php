@use('App\Enums\Permission', 'P')
<x-app-layout>
    <div class="cabecera-pagina">
            <h1 class="titulo-pagina">Gestión de Actividades</h1>
            @can(P::ActividadesGestionar)
            <a href="{{ route('actividades.create') }}" title="Crear nueva actividad" class="boton boton-primario">
                <x-lucide-plus />Nueva Actividad
            </a>
            @endcan
        </div>

    <div class="tarjeta p-6">
        <form method="GET" action="{{ route('actividades.index') }}" class="filtros space-y-4" x-data="{ filtersOpen: true }">
            <button @click="filtersOpen = !filtersOpen" type="button" class="flex items-center gap-2 text-sm font-medium text-tinta-suave hover:text-tinta cursor-pointer mb-2">
                <span x-show="!filtersOpen" x-cloak><x-lucide-funnel-plus class="w-4 h-4" /></span>
                <span x-show="filtersOpen" x-cloak><x-lucide-funnel-x class="w-4 h-4" /></span>
                <span>Filtros</span>
            </button>
            <div :class="filtersOpen ? 'filter-expanded' : 'filter-collapsed'" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="titulo" class="etiqueta-campo block">Título</label>
                    <input type="text" name="titulo" id="titulo" value="{{ request('titulo') }}" placeholder="Buscar título..."
                           class="campo mt-1">
                </div>

                <div>
                    <label for="user_id" class="etiqueta-campo block">Creado por</label>
                    <select name="user_id" id="user_id" class="campo mt-1">
                        <option value="">-- Todos --</option>
                        @foreach($usuarios as $usuario)
                            <option value="{{ $usuario->id }}" {{ request('user_id') == $usuario->id ? 'selected' : '' }}>{{ $usuario->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="desde" class="etiqueta-campo block">Fecha Desde</label>
                    <input type="date" name="desde" id="desde" value="{{ request('desde') }}"
                           class="campo mt-1">
                </div>

                <div>
                    <label for="hasta" class="etiqueta-campo block">Fecha Hasta</label>
                    <input type="date" name="hasta" id="hasta" value="{{ request('hasta') }}"
                           class="campo mt-1">
                </div>
            </div>

            <div class="grupo-acciones grupo-acciones--final">
                <a href="{{ route('actividades.index') }}" title="Limpiar todos los filtros" class="boton tono-neutro">
                    <x-lucide-eraser />Limpiar Filtros
                </a>
                <button type="submit" title="Buscar actividades" class="boton tono-neutro">
                    <x-lucide-search />Buscar
                </button>
            </div>
            </div>
        </form>

        @if($actividades->count() > 0)
            <div class="overflow-x-auto">
                <table class="tabla tabla--media">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Fecha</th>
                            <th>Hora</th>
                            <th>Residentes</th>
                            <th class="col-secundaria">Creado por</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($actividades as $actividad)
                            <tr>
                                <td class="font-medium" data-label="Título">
                                    <a href="{{ route('actividades.show', $actividad) }}" title="Ver actividad" class="enlace-fila">
                                        {{ $actividad->titulo }}
                                    </a>
                                </td>
                                <td data-label="Fecha">{{ $actividad->fecha ? $actividad->fecha->format('d/m/Y') : '—' }}</td>
                                <td data-label="Hora">{{ $actividad->hora ? $actividad->hora->format('H:i') : '—' }}</td>
                                <td class="text-tinta-suave" data-label="Residentes">
                                    @php $nombres = $actividad->residentes->pluck('nombre')->toArray(); @endphp
                                    @if(count($nombres) > 0)
                                        {{ implode(', ', array_slice($nombres, 0, 2)) }}
                                        @if(count($nombres) > 2)
                                            <span class="text-xs text-tinta-tenue">+{{ count($nombres) - 2 }} más</span>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-tinta-suave col-secundaria" data-label="Creado por">{{ $actividad->user?->name ?? '—' }}</td>
                                <td class="acciones" data-label="Acciones">
                                    <div class="grupo-acciones">
                                        <x-accion icono="eye" titulo="Ver actividad" href="{{ route('actividades.show', $actividad) }}" />
                                        @can(P::ActividadesGestionar)
                                        <x-accion icono="pencil" titulo="Editar actividad" tono="marca" href="{{ route('actividades.edit', $actividad) }}" />
                                        <x-boton-motivo :action="route('actividades.anular', $actividad)" titulo="Anular actividad" />
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-linea">
                {{ $actividades->appends(request()->query())->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <x-lucide-calendar class="w-12 h-12 mx-auto block mb-3 text-tinta-tenue" />
                <p class="text-sm text-tinta-tenue italic">No hay actividades registradas.</p>
                @can(P::ActividadesGestionar)
                <a href="{{ route('actividades.create') }}" title="Crear la primera actividad" class="mt-2 inline-block text-sm text-marca-700 hover:text-marca-800 font-medium"><x-lucide-plus class="w-4 h-4 inline" /> <span class="hidden sm:inline">Crear primera actividad</span></a>
                @endcan
            </div>
        @endif
        <div class="px-4 pb-4"><x-registros-anulados :registros="$anuladas" /></div>

    </div>
</x-app-layout>
