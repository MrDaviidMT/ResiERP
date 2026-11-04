@use('App\Enums\Permission', 'P')
<x-app-layout>
    <div class="cabecera-pagina">
            <h1 class="titulo-pagina">Gestión de Familiares</h1>
            @can(P::FamiliaresCrear)
            <a href="{{ route('familiares.create') }}" title="Añadir nuevo familiar" class="boton boton-primario">
                <x-lucide-user-plus />Nuevo Familiar
            </a>
            @endcan
        </div>

    <div class="tarjeta p-6">
        <form method="GET" action="{{ route('familiares.lista') }}" class="filtros space-y-4" x-data="{ filtersOpen: true }">
            <button @click="filtersOpen = !filtersOpen" type="button" class="flex items-center gap-2 text-sm font-medium text-tinta-suave hover:text-tinta cursor-pointer mb-2">
                <span x-show="!filtersOpen" x-cloak><x-lucide-funnel-plus class="w-4 h-4" /></span>
                <span x-show="filtersOpen" x-cloak><x-lucide-funnel-x class="w-4 h-4" /></span>
                <span>Filtros</span>
            </button>
            <div :class="filtersOpen ? 'filter-expanded' : 'filter-collapsed'" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="etiqueta-campo block">Nombre</label>
                    <input type="text" name="nombre" value="{{ request('nombre') }}" placeholder="Nombre..."
                           class="campo mt-1">
                </div>
                <div>
                    <label class="etiqueta-campo block">Apellidos</label>
                    <input type="text" name="apellidos" value="{{ request('apellidos') }}" placeholder="Apellidos..."
                           class="campo mt-1">
                </div>
                <div>
                    <label class="etiqueta-campo block">DNI</label>
                    <input type="text" name="dni" value="{{ request('dni') }}" placeholder="12345678A..."
                           class="campo mt-1">
                </div>
                <div>
                    <label class="etiqueta-campo block">Teléfono</label>
                    <input type="text" name="telefono" value="{{ request('telefono') }}" placeholder="Teléfono..."
                           class="campo mt-1">
                </div>
                <div>
                    <label class="etiqueta-campo block">Email</label>
                    <input type="text" name="email" value="{{ request('email') }}" placeholder="Email..."
                           class="campo mt-1">
                </div>
            </div>
            <div class="grupo-acciones grupo-acciones--final">
                <a href="{{ route('familiares.lista') }}" title="Limpiar todos los filtros" class="boton tono-neutro">
                    <x-lucide-eraser />Limpiar Filtros
                </a>
                <button type="submit" title="Buscar familiares" class="boton tono-neutro">
                    <x-lucide-search />Buscar
                </button>
            </div>
            </div>
        </form>

        @if($familiares->count() > 0)
            <div class="overflow-x-auto">
                <table class="tabla ">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            @can(P::FamiliaresVerContacto)<th>DNI</th>@endcan
                            @can(P::FamiliaresVerContacto)<th>Teléfono</th>@endcan
                            <th>Vinculado a</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($familiares as $familiar)
                            <tr>
                                <td data-label="Nombre">
                                    <a href="{{ route('familiares.show', $familiar) }}" title="Ver detalle del familiar" class="enlace-fila">
                                        {{ $familiar->nombre_completo }}
                                    </a>
                                </td>
                                @can(P::FamiliaresVerContacto)<td data-label="DNI">{{ $familiar->dni ?? 'No aportado' }}</td>@endcan
                                @can(P::FamiliaresVerContacto)
                                <td data-label="Teléfono">
                                    <a href="tel:{{ $familiar->telefono }}" title="Llamar al {{ $familiar->telefono }}" class="text-marca-700 hover:text-marca-800 font-medium">{{ $familiar->telefono }}</a>
                                </td>
                                @endcan
                                <td data-label="Vinculado a">
                                    @foreach($familiar->residentes as $r)
                                        <a href="{{ route('residentes.show', $r) }}" title="Ver perfil del residente" class="etiqueta tono-marca">
                                            {{ $r->nombre }} {{ $r->apellidos }}
                                        </a>
                                    @endforeach
                                </td>
                                <td class="acciones" data-label="Acciones">
                                    <div class="grupo-acciones">
                                    <x-accion icono="eye" titulo="Ver detalle del familiar" href="{{ route('familiares.show', $familiar) }}" />
                                    @can(P::FamiliaresEditar)
                                    <x-accion icono="pencil" titulo="Editar datos del familiar" tono="marca" href="{{ route('familiares.edit', $familiar) }}" />
                                    @endcan
                                    @can(P::FamiliaresBorrar)
                                    <form method="POST" action="{{ route('familiares.destroy', $familiar) }}" id="form-eliminar-familiar-{{ $familiar->id }}">
                                        @csrf @method('DELETE')
                                        <x-accion icono="trash-2" titulo="Eliminar familiar" tono="peligro" type="button" data-swal-delete data-title="Eliminar familiar" data-text="¿Estás seguro?" data-form-id="form-eliminar-familiar-{{ $familiar->id }}" />
                                    </form>
                                    @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-linea">
                {{ $familiares->appends(request()->query())->links() }}
            </div>

        @else
            <div class="text-center py-12">
                <x-lucide-users class="w-12 h-12 mx-auto block mb-3 text-tinta-tenue" />
                <p class="text-sm text-tinta-tenue italic">No hay familiares registrados.</p>
                @can(P::FamiliaresCrear)
                <a href="{{ route('familiares.create') }}" title="Añadir el primer familiar" class="mt-2 inline-block text-sm text-marca-700 hover:text-marca-800 font-medium"><x-lucide-user-plus class="w-4 h-4 inline" /> <span class="hidden sm:inline">Añadir primer familiar</span></a>
                @endcan
            </div>
        @endif

    </div>
</x-app-layout>
