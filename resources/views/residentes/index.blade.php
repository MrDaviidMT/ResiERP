@use('App\Enums\Permission', 'P')
<x-app-layout>
    <div class="cabecera-pagina">
            <h1 class="titulo-pagina">Gestión de Residentes</h1>
            @can(P::ResidentesCrear)
            <a href="{{ route('residentes.create') }}" title="Dar de alta un nuevo residente" class="boton boton-primario">
                <x-lucide-user-plus />Nuevo Residente
            </a>
            @endcan
        </div>

    <div class="tarjeta p-6">
        <form action="{{ route('residentes.index') }}" method="GET" class="filtros space-y-4" x-data="{ filtersOpen: true }">
            <button @click="filtersOpen = !filtersOpen" type="button" class="flex items-center gap-2 text-sm font-medium text-tinta-suave hover:text-tinta cursor-pointer mb-2">
                <span x-show="!filtersOpen" x-cloak><x-lucide-funnel-plus class="w-4 h-4" /></span>
                <span x-show="filtersOpen" x-cloak><x-lucide-funnel-x class="w-4 h-4" /></span>
                Filtros
            </button>
            <div :class="filtersOpen ? 'filter-expanded' : 'filter-collapsed'" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="buscar" class="etiqueta-campo block">Buscar Residente</label>
                    <input type="text" name="buscar" id="buscar" value="{{ request('buscar') }}" 
                        placeholder="Nombre, apellidos o DNI..." 
                        class="mt-1 block w-full campo">
                </div>

                <div>
                    <label for="habitacion_id" class="etiqueta-campo block">Habitación</label>
                    <select name="habitacion_id" id="habitacion_id" class="mt-1 block w-full campo">
                        <option value="">-- Todas --</option>
                        @foreach($habitaciones as $hab)
                            <option value="{{ $hab->id }}" {{ request('habitacion_id') == $hab->id ? 'selected' : '' }}>
                                Hab. {{ $hab->numero }} ({{ ucfirst($hab->tipo) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="estado" class="etiqueta-campo block">Estado Clínico</label>
                    <select name="estado" id="estado" class="mt-1 block w-full campo font-medium text-tinta">
                        <option value="activos" {{ request('estado', 'activos') == 'activos' ? 'selected' : '' }}>Activos / En Planta</option>
                        <option value="archivados" {{ request('estado') == 'archivados' ? 'selected' : '' }}>Bajas / Histórico</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 pt-2 border-t border-linea">
                <div>
                    <label for="ingreso_desde" class="etiqueta-campo block">Ingreso Desde</label>
                    <input type="date" name="ingreso_desde" id="ingreso_desde" value="{{ request('ingreso_desde') }}" class="mt-1 block w-full campo">
                </div>
                <div>
                    <label for="ingreso_hasta" class="etiqueta-campo block">Ingreso Hasta</label>
                    <input type="date" name="ingreso_hasta" id="ingreso_hasta" value="{{ request('ingreso_hasta') }}" class="mt-1 block w-full campo">
                </div>
                <div>
                    <label for="nac_desde" class="etiqueta-campo block">Nacimiento Desde</label>
                    <input type="date" name="nac_desde" id="nac_desde" value="{{ request('nac_desde', $nacimientoDesde) }}" class="mt-1 block w-full campo">
                </div>
                <div>
                    <label for="nac_hasta" class="etiqueta-campo block">Nacimiento Hasta</label>
                    <input type="date" name="nac_hasta" id="nac_hasta" value="{{ request('nac_hasta', $nacimientoHasta) }}" class="mt-1 block w-full campo">
                </div>
            </div>

            <div class="grupo-acciones grupo-acciones--final pt-2">
                <a href="{{ route('residentes.index') }}" title="Limpiar todos los filtros" class="boton tono-neutro">
                    <x-lucide-eraser />Limpiar Filtros
                </a>
                <button type="submit" title="Buscar residentes" class="boton tono-neutro">
                    <x-lucide-search />Buscar
                </button>
            </div>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="tabla ">
                <thead>
                    <tr>
                        <th>Nombre Completo</th>
                        @can(P::ResidentesVerDocumento)<th>DNI</th>@endcan
                        <th>Habitación Asignada</th>
                        <th>Acciones</th> 
                    </tr>
                </thead>
                <tbody>
                    @forelse($residentes as $residente)
                        <tr>
                            
                            <!-- Nombre enlazado al perfil -->
                            <td data-label="Nombre Completo">
                                <a href="{{ route('residentes.show', $residente->id) }}" title="Ver perfil del residente" class="enlace-fila">
                                    {{ $residente->nombre }} {{ $residente->apellidos }}
                                </a>
                            </td>
                            
                            @can(P::ResidentesVerDocumento)<td data-label="DNI">{{ $residente->dni ?? 'No aportado' }}</td>@endcan
                            <td data-label="Habitación">
                                @if($residente->trashed())
                                    <span class="etiqueta tono-peligro">
                                        Baja / Histórico
                                    </span>
                                @elseif($residente->habitacion)
                                    <span class="etiqueta tono-marca">
                                        Hab. {{ $residente->habitacion->numero }} ({{ ucfirst($residente->habitacion->tipo) }})
                                    </span>
                                @else
                                    <span class="text-tinta-tenue italic">Sin habitación</span>
                                @endif
                            </td>
                            <td class="acciones" data-label="Acciones">
                                <div class="grupo-acciones">
                                    @if($residente->trashed())
                                        @can(P::ResidentesReincorporar)
                                        <form action="{{ route('residentes.restaurar', $residente->id) }}" method="POST" id="form-restaurar-{{ $residente->id }}">
                                            @csrf
                                            <x-accion icono="rotate-ccw" titulo="Restaurar alta del residente" tono="exito" type="button" data-swal-submit data-title="Reincorporar residente" data-text="¿Restaurar el alta de este residente? Se le puede asignar una habitación desde su perfil." />
                                        </form>
                                        @endcan
                                    @else
                                        <x-accion icono="eye" titulo="Ver perfil del residente" href="{{ route('residentes.show', $residente->id) }}" />
                                        @can(P::ResidentesEditar)
                                        <x-accion icono="pencil" titulo="Editar datos del residente" tono="marca" href="{{ route('residentes.edit', $residente->id) }}" />

                                        <form action="{{ route('residentes.destroy', $residente->id) }}" method="POST" id="form-baja-{{ $residente->id }}">
                                            @csrf
                                            @method('DELETE')
                                            <x-accion icono="user-x" titulo="Dar de baja al residente" tono="peligro" type="button" data-swal-baja data-title="¿Confirmar baja?" data-text="Se liberará su cama en el sistema. Podrás reincorporarlo desde el filtro *Bajas / Histórico* del listado." data-form-id="form-baja-{{ $residente->id }}" />
                                        </form>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                                <td colspan="4" class="centrado text-tinta-suave italic" data-label="">
                                Ningún residente coincide con los criterios de búsqueda establecidos.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $residentes->appends(request()->query())->links() }}
        </div>

    </div>
</x-app-layout>