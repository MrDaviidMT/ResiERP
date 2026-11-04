@use('App\Enums\Permission', 'P')
<x-app-layout>
    <div x-data="incidenciasApp()">


        <div class="cabecera-pagina">
            <div>
                <h1 class="titulo-pagina">Centro de Control e Incidencias</h1>
                <p class="text-sm text-tinta-suave mt-1">Gestión global de avisos sanitarios, averías de infraestructura, limpieza y alertas.</p>
            </div>
            <button @click="modalCrear = true" title="Crear nueva incidencia" class="boton boton-primario">
                <x-lucide-plus />Nueva Incidencia
            </button>
        </div>

        <div class="tarjeta p-6">
            <form action="{{ route('incidencias.index') }}" method="GET" class="filtros space-y-4" x-data="{ filtersOpen: true }">
                <button @click="filtersOpen = !filtersOpen" type="button" class="flex items-center gap-2 text-sm font-medium text-tinta-suave hover:text-tinta cursor-pointer mb-2">
                    <span x-show="!filtersOpen" x-cloak><x-lucide-funnel-plus class="w-4 h-4" /></span>
                    <span x-show="filtersOpen" x-cloak><x-lucide-funnel-x class="w-4 h-4" /></span>
                    <span>Filtros</span>
                </button>
                <div :class="filtersOpen ? 'filter-expanded' : 'filter-collapsed'" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="filter_categoria" class="etiqueta-campo block">Categoría</label>
                        <select name="categoria" id="filter_categoria" class="mt-1 block w-full campo text-sm font-medium text-tinta">
                            <option value="">-- Todas --</option>
                            <option value="sanitaria" {{ request('categoria') == 'sanitaria' ? 'selected' : '' }}>Sanitaria</option>
                            <option value="infraestructura" {{ request('categoria') == 'infraestructura' ? 'selected' : '' }}>Infraestructura</option>
                            <option value="limpieza" {{ request('categoria') == 'limpieza' ? 'selected' : '' }}>Limpieza</option>
                            <option value="personal" {{ request('categoria') == 'personal' ? 'selected' : '' }}>Personal</option>
                            <option value="otros" {{ request('categoria') == 'otros' ? 'selected' : '' }}>Otros</option>
                        </select>
                    </div>
                    <div>
                        <label for="filter_estado" class="etiqueta-campo block">Estado</label>
                        <select name="estado" id="filter_estado" class="mt-1 block w-full campo text-sm font-medium text-tinta">
                            <option value="">-- Todos --</option>
                            <option value="pendiente" {{ request('estado') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                            <option value="en_proceso" {{ request('estado') == 'en_proceso' ? 'selected' : '' }}>En Proceso</option>
                            <option value="resuelta" {{ request('estado') == 'resuelta' ? 'selected' : '' }}>Resuelta</option>
                        </select>
                    </div>
                    <div>
                        <label for="filter_prioridad" class="etiqueta-campo block">Prioridad</label>
                        <select name="prioridad" id="filter_prioridad" class="mt-1 block w-full campo text-sm font-medium text-tinta">
                            <option value="">-- Todas --</option>
                            <option value="leve" {{ request('prioridad') == 'leve' ? 'selected' : '' }}>Leve</option>
                            <option value="grave" {{ request('prioridad') == 'grave' ? 'selected' : '' }}>Grave</option>
                            <option value="urgente" {{ request('prioridad') == 'urgente' ? 'selected' : '' }}>Urgente</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 pt-2 border-t border-linea">
                    <div>
                        <label for="filter_registrada_desde" class="etiqueta-campo block">Registrada Desde</label>
                        <input type="date" name="registrada_desde" id="filter_registrada_desde" value="{{ request('registrada_desde') }}" class="mt-1 block w-full campo">
                    </div>
                    <div>
                        <label for="filter_registrada_hasta" class="etiqueta-campo block">Registrada Hasta</label>
                        <input type="date" name="registrada_hasta" id="filter_registrada_hasta" value="{{ request('registrada_hasta') }}" class="mt-1 block w-full campo">
                    </div>
                    <div>
                        <label for="filter_resuelta_desde" class="etiqueta-campo block">Resuelta Desde</label>
                        <input type="date" name="resuelta_desde" id="filter_resuelta_desde" value="{{ request('resuelta_desde') }}" class="mt-1 block w-full campo">
                    </div>
                    <div>
                        <label for="filter_resuelta_hasta" class="etiqueta-campo block">Resuelta Hasta</label>
                        <input type="date" name="resuelta_hasta" id="filter_resuelta_hasta" value="{{ request('resuelta_hasta') }}" class="mt-1 block w-full campo">
                    </div>
                </div>
                <div class="grupo-acciones grupo-acciones--final">
                    <a href="{{ route('incidencias.index') }}" title="Limpiar todos los filtros" class="boton tono-neutro">
                        <x-lucide-eraser />Limpiar Filtros
                    </a>
                    <button type="submit" title="Buscar incidencias" class="boton tono-neutro">
                        <x-lucide-search />Buscar
                    </button>
                </div>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="tabla tabla--media">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Categoría</th>
                            <th class="centrado">Estado</th>
                            <th class="centrado">Prioridad</th>
                            <th class="centrado col-secundaria">Registrada</th>
                            <th class="centrado col-secundaria">Resuelta</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($incidencias as $inc)
                            <tr>
                                <td data-label="Título">
                                    <a href="{{ route('incidencias.show', $inc) }}" title="Ver detalle de la incidencia" class="enlace-fila">
                                        {{ $inc->titulo }}
                                    </a>
                                </td>
                                <td data-label="Categoría">
                                    <span class="etiqueta tono-neutro">
                                        @if($inc->categoria === 'sanitaria')
                                            <x-lucide-heart class="w-3 h-3" /> Sanitaria
                                        @elseif($inc->categoria === 'infraestructura')
                                            <x-lucide-wrench class="w-3 h-3" /> Infraestructura
                                        @elseif($inc->categoria === 'limpieza')
                                            <x-lucide-sparkles class="w-3 h-3" /> Limpieza
                                        @elseif($inc->categoria === 'personal')
                                            <x-lucide-users class="w-3 h-3" /> Personal
                                        @else
                                            <x-lucide-inbox class="w-3 h-3" /> Otros
                                        @endif
                                    </span>
                                </td>
                                <td class="centrado" data-label="Estado">
                                    <span class="etiqueta
                                        {{ $inc->estado === 'resuelta' ? 'tono-exito' : ($inc->estado === 'en_proceso' ? 'tono-aviso' : 'tono-neutro') }}">
                                        {{ ucfirst(str_replace('_', ' ', $inc->estado)) }}
                                    </span>
                                </td>
                                <td class="centrado" data-label="Prioridad">
                                    <span class="etiqueta {{ $inc->prioridad === 'urgente' ? 'tono-peligro' : ($inc->prioridad === 'grave' ? 'tono-aviso' : 'tono-marca') }}">
                                        {{ ucfirst($inc->prioridad) }}
                                    </span>
                                </td>
                                <td class="centrado text-sm text-tinta-suave whitespace-nowrap col-secundaria" data-label="Registrada">
                                    {{ $inc->created_at->format('d/m/Y') }}<br>
                                    <span class="text-xs text-tinta-tenue">{{ $inc->created_at->format('H:i') }}</span>
                                </td>
                                <td class="centrado text-sm text-tinta-suave whitespace-nowrap col-secundaria" data-label="Resuelta">
                                    @if($inc->estado === 'resuelta' && $inc->fecha_resolucion)
                                        {{ $inc->fecha_resolucion->format('d/m/Y H:i') }}
                                    @else
                                        <span class="text-tinta-tenue">—</span>
                                    @endif
                                </td>
                                <td class="acciones" data-label="Acciones">
                                    {{-- Todas las acciones con el mismo componente: misma caja, mismo icono y
                                         misma altura. Solo cambia el color según lo que hace cada una. --}}
                                    <div class="grupo-acciones">
                                        <x-accion :href="route('incidencias.show', $inc)" icono="eye" titulo="Ver detalle" />
                                        @can('update', $inc)
                                        <x-accion :href="route('incidencias.edit', $inc)" icono="pencil" titulo="Editar incidencia" tono="marca" />
                                        @endcan
                                        @can(P::IncidenciasAnular)
                                        <x-boton-motivo :action="route('incidencias.anular', $inc)" titulo="Anular incidencia" />
                                        @endcan
                                        @can('update', $inc)
                                        @if($inc->estado === 'pendiente')
                                            <form action="{{ route('incidencias.estado', ['incidencia' => $inc->id, 'estado' => 'en_proceso']) }}" method="POST">
                                                @csrf
                                                <x-accion icono="cog" titulo="Atender incidencia" tono="aviso"
                                                          data-swal-submit data-title="Atender incidencia" data-text="¿Poner esta incidencia en proceso?" />
                                            </form>
                                        @elseif($inc->estado === 'en_proceso')
                                            <form action="{{ route('incidencias.estado', ['incidencia' => $inc->id, 'estado' => 'resuelta']) }}" method="POST">
                                                @csrf
                                                <x-accion icono="circle-check-big" titulo="Resolver incidencia" tono="exito"
                                                          data-swal-submit data-title="Resolver incidencia" data-text="¿Marcar como resuelta?" />
                                            </form>
                                            <form action="{{ route('incidencias.estado', ['incidencia' => $inc->id, 'estado' => 'pendiente']) }}" method="POST">
                                                @csrf
                                                <x-accion icono="rotate-ccw" titulo="Pasar a pendiente"
                                                          data-swal-submit data-title="Pasar a pendiente" data-text="¿Volver a estado pendiente?" />
                                            </form>
                                        @elseif($inc->estado === 'resuelta')
                                            <form action="{{ route('incidencias.estado', ['incidencia' => $inc->id, 'estado' => 'en_proceso']) }}" method="POST">
                                                @csrf
                                                <x-accion icono="rotate-ccw" titulo="Reabrir incidencia" tono="aviso"
                                                          data-swal-submit data-title="Reabrir incidencia" data-text="¿Volver a poner en proceso?" />
                                            </form>
                                        @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="centrado text-tinta-suave italic">
                                    <x-lucide-smile-plus class="w-4 h-4 inline" /> Ninguna incidencia o aviso coincide con el criterio de búsqueda establecido.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $incidencias->appends(request()->query())->links() }}
                <x-registros-anulados :registros="$anuladas" />
            </div>
        </div>

        {{-- Modal crear incidencia --}}
        <div x-show="modalCrear" class="modal-fondo"
             x-cloak @click="modalCrear = false">
            <div class="modal" @click.stop>
                <div class="cabecera-pagina">
                    <h2 class="titulo-seccion">Registrar Incidencia</h2>
                    <button @click="modalCrear = false" title="Cerrar" class="text-tinta-tenue hover:text-tinta-suave text-2xl leading-none cursor-pointer">&times;</button>
                </div>

                <form action="{{ route('incidencias.store') }}" method="POST">
                    @csrf

                    <div class="mb-4">
                        <label class="etiqueta-campo block">Tipo de Categoría <span class="text-peligro">*</span></label>
                        <select name="categoria" id="categoria_selector" required class="w-full mt-1 p-2 border border-linea-fuerte rounded-md bg-superficie text-sm font-semibold text-tinta">
                            <option value="sanitaria">Sanitaria</option>
                            <option value="infraestructura">Infraestructura</option>
                            <option value="limpieza">Limpieza</option>
                            <option value="personal">Personal</option>
                            <option value="otros">Otros</option>
                        </select>
                    </div>

                    <div class="mb-4" id="wrapper_habitacion">
                        <label class="etiqueta-campo block">Ubicación / Habitación (Opcional)</label>
                        <select name="habitacion_id" id="habitacion_select" class="w-full mt-1 p-2 border border-linea-fuerte rounded-md bg-superficie text-sm text-tinta">
                            <option value="">-- No aplica / Zona Común --</option>
                            @foreach($habitaciones as $hab)
                                <option value="{{ $hab->id }}" data-tipo="{{ $hab->tipo }}">Habitación {{ $hab->numero }} ({{ ucfirst($hab->tipo) }})</option>
                            @endforeach
                        </select>
                    </div>

                    @can(P::ResidentesVer)
                    <div class="mb-4 hidden" id="wrapper_residente_modal">
                        <label class="etiqueta-campo block text-peligro">Residente Afectado (Obligatorio para Sanitaria)</label>
                        <select name="residente_id" id="residente_id_input" class="w-full mt-1 p-2 border border-peligro rounded-md bg-superficie text-sm text-tinta">
                            <option value="">-- Seleccionar Residente --</option>
                            @foreach($residentes as $res)
                                <option value="{{ $res->id }}" data-hab="{{ $res->habitacion_id }}">{{ $res->apellidos }}, {{ $res->nombre }}{{ $res->habitacion ? ' (Hab. '.$res->habitacion->numero.')' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endcan

                    <div class="mb-4">
                        <label class="etiqueta-campo block">Título del Suceso <span class="text-peligro">*</span></label>
                        <input type="text" name="titulo" required placeholder="Ej: Rotura de tubería, Caída fortuita en pasillo..." 
                               class="w-full mt-1 p-2 border border-linea-fuerte rounded-md bg-superficie text-sm">
                    </div>

                    <div class="mb-4">
                        <label class="etiqueta-campo block">Descripción</label>
                        <textarea name="descripcion" rows="3" placeholder="Aporta detalles del suceso para agilizar la intervención..." class="w-full mt-1 p-2 border border-linea-fuerte rounded-md bg-superficie text-sm"></textarea>
                    </div>

                    <div class="mb-6">
                        <label class="etiqueta-campo block">Prioridad Operativa</label>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2 mt-1">
                            <label class="border border-linea rounded p-2 text-center text-xs font-bold cursor-pointer hover:bg-fondo block">
                                <input type="radio" name="prioridad" value="leve" checked class="mr-1"> Leve
                            </label>
                            <label class="border border-linea rounded p-2 text-center text-xs font-bold text-aviso cursor-pointer hover:bg-fondo block">
                                <input type="radio" name="prioridad" value="grave" class="mr-1"> Grave
                            </label>
                            <label class="border border-linea rounded p-2 text-center text-xs font-bold text-peligro cursor-pointer hover:bg-fondo block">
                                <input type="radio" name="prioridad" value="urgente" class="mr-1"> Urgente
                            </label>
                        </div>
                    </div>

                    <button type="submit" title="Registrar nueva incidencia" class="boton boton-primario w-full">
                        <x-lucide-save />Registrar
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
