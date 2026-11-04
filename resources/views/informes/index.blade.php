@use('App\Enums\Permission', 'P')
<x-app-layout>
    <div class="cabecera-pagina">
            <h1 class="titulo-pagina">Gestión de Informes</h1>
            @can('create', \App\Models\Informe::class)
            <a href="{{ route('informes.create') }}" title="Crear nuevo informe" class="boton boton-primario">
                <x-lucide-plus />Nuevo Informe
            </a>
            @endcan
        </div>

    <div class="tarjeta p-6">
        <form method="GET" action="{{ route('informes.index') }}" class="filtros space-y-4" x-data="{ filtersOpen: true }">
            <button @click="filtersOpen = !filtersOpen" type="button" class="flex items-center gap-2 text-sm font-medium text-tinta-suave hover:text-tinta cursor-pointer mb-2">
                <span x-show="!filtersOpen" x-cloak><x-lucide-funnel-plus class="w-4 h-4" /></span>
                <span x-show="filtersOpen" x-cloak><x-lucide-funnel-x class="w-4 h-4" /></span>
                <span>Filtros</span>
            </button>
            <div :class="filtersOpen ? 'filter-expanded' : 'filter-collapsed'" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="tipo" class="etiqueta-campo block">Tipo</label>
                    <select name="tipo" id="tipo" class="campo mt-1">
                        <option value="">-- Todos --</option>
                        @foreach($tipos as $tipo)
                            <option value="{{ $tipo }}" {{ request('tipo') == $tipo ? 'selected' : '' }}>{{ ucfirst($tipo) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="user_id" class="etiqueta-campo block">Autor</label>
                    <select name="user_id" id="user_id" class="campo mt-1">
                        <option value="">-- Todos --</option>
                        @foreach($usuarios as $usuario)
                            <option value="{{ $usuario->id }}" {{ request('user_id') == $usuario->id ? 'selected' : '' }}>{{ $usuario->name }}</option>
                        @endforeach
                    </select>
                </div>

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
                <a href="{{ route('informes.index') }}" title="Limpiar todos los filtros" class="boton tono-neutro">
                    <x-lucide-eraser />Limpiar Filtros
                </a>
                <button type="submit" title="Buscar informes" class="boton tono-neutro">
                    <x-lucide-search />Buscar
                </button>
            </div>
            </div>
        </form>

        @if($informes->count() > 0)
            <div class="overflow-x-auto">
                <table class="tabla tabla--media">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Tipo</th>
                            <th>Autor</th>
                            <th>Residentes</th>
                            <th class="col-secundaria">Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($informes as $informe)
                            <tr>
                                <td class="font-medium" data-label="Título">
                                    <a href="{{ route('informes.show', $informe) }}" title="Ver informe" class="enlace-fila">
                                        {{ $informe->titulo }}
                                    </a>
                                </td>
                                <td data-label="Tipo">
                                    <span class="etiqueta tono-neutro">{{ ucfirst($informe->tipo) }}</span>
                                </td>
                                <td class="text-tinta-suave" data-label="Autor">{{ $informe->user->name }}</td>
                                <td class="text-tinta-suave" data-label="Residentes">
                                    @php
                                        $nombres = $informe->residentes->pluck('nombre')->toArray();
                                    @endphp
                                    @if(count($nombres) > 0)
                                        {{ implode(', ', array_slice($nombres, 0, 2)) }}
                                        @if(count($nombres) > 2)
                                            <span class="text-xs text-tinta-tenue">+{{ count($nombres) - 2 }} más</span>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-tinta-suave col-secundaria" data-label="Fecha">{{ $informe->created_at->format('d/m/Y') }}</td>
                                <td class="acciones" data-label="Acciones">
                                    <div class="grupo-acciones">
                                        <x-accion icono="eye" titulo="Ver informe" href="{{ route('informes.show', $informe) }}" />
                                        @can('update', $informe)
                                        <x-accion icono="pencil" titulo="Editar informe" tono="marca" href="{{ route('informes.edit', $informe) }}" />
                                        @endcan
                                        @can('anular', $informe)
                                        <x-boton-motivo :action="route('informes.anular', $informe)" titulo="Anular informe" />
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-linea">
                {{ $informes->appends(request()->query())->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <x-lucide-file-text class="w-12 h-12 mx-auto block mb-3 text-tinta-tenue" />
                <p class="text-sm text-tinta-tenue italic">No hay informes registrados.</p>
                @can('create', \App\Models\Informe::class)
                <a href="{{ route('informes.create') }}" title="Crear el primer informe" class="mt-2 inline-block text-sm text-marca-700 hover:text-marca-800 font-medium"><x-lucide-plus class="w-4 h-4 inline" /> <span class="hidden sm:inline">Crear primer informe</span></a>
                @endcan
            </div>
        @endif
        <div class="px-4 pb-4"><x-registros-anulados :registros="$anulados" /></div>

    </div>
</x-app-layout>
