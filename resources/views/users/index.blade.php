<x-app-layout>
    <div class="cabecera-pagina">
            <h1 class="titulo-pagina">Gestión de Usuarios</h1>
            <a href="{{ route('users.create') }}" title="Crear nuevo usuario" class="boton boton-primario">
                <x-lucide-plus />Nuevo Usuario
            </a>
        </div>

    <div class="tarjeta p-6">
        <form method="GET" action="{{ route('users.index') }}" class="filtros space-y-4" x-data="{ filtersOpen: true }">
            <button @click="filtersOpen = !filtersOpen" type="button" class="flex items-center gap-2 text-sm font-medium text-tinta-suave hover:text-tinta cursor-pointer mb-2">
                <span x-show="!filtersOpen" x-cloak><x-lucide-funnel-plus class="w-4 h-4" /></span>
                <span x-show="filtersOpen" x-cloak><x-lucide-funnel-x class="w-4 h-4" /></span>
                <span>Filtros</span>
            </button>
            <div :class="filtersOpen ? 'filter-expanded' : 'filter-collapsed'" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="name" class="etiqueta-campo block">Nombre</label>
                    <input type="text" name="name" id="name" value="{{ request('name') }}" placeholder="Nombre..."
                           class="campo mt-1">
                </div>

                <div>
                    <label for="email" class="etiqueta-campo block">Email</label>
                    <input type="text" name="email" id="email" value="{{ request('email') }}" placeholder="Email..."
                           class="campo mt-1">
                </div>

                <div>
                    <label for="role" class="etiqueta-campo block">Rol</label>
                    <select name="role" id="role" class="campo mt-1">
                        <option value="">-- Todos --</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->value }}" {{ request('role') == $role->value ? 'selected' : '' }}>{{ $role->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="verified" class="etiqueta-campo block">Verificado</label>
                    <select name="verified" id="verified" class="campo mt-1">
                        <option value="">-- Todos --</option>
                        <option value="si" {{ request('verified') == 'si' ? 'selected' : '' }}>Verificado</option>
                        <option value="no" {{ request('verified') == 'no' ? 'selected' : '' }}>No verificado</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="estado" class="etiqueta-campo block">Estado</label>
                    <select name="estado" id="estado" class="campo mt-1">
                        <option value="activos" {{ request('estado', 'activos') == 'activos' ? 'selected' : '' }}>Activos</option>
                        <option value="desactivados" {{ request('estado') == 'desactivados' ? 'selected' : '' }}>Desactivados</option>
                    </select>
                </div>
                <div>
                    <label for="desde" class="etiqueta-campo block">Creado Desde</label>
                    <input type="date" name="desde" id="desde" value="{{ request('desde') }}"
                           class="campo mt-1">
                </div>
                <div>
                    <label for="hasta" class="etiqueta-campo block">Creado Hasta</label>
                    <input type="date" name="hasta" id="hasta" value="{{ request('hasta') }}"
                           class="campo mt-1">
                </div>
            </div>

            <div class="grupo-acciones grupo-acciones--final">
                <a href="{{ route('users.index') }}" title="Limpiar todos los filtros" class="boton tono-neutro">
                    <x-lucide-eraser />Limpiar Filtros
                </a>
                <button type="submit" title="Buscar usuarios" class="boton tono-neutro">
                    <x-lucide-search />Buscar
                </button>
            </div>
            </div>
        </form>

        @if($users->count() > 0)
            <div class="overflow-x-auto">
                <table class="tabla tabla--media">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Verificado</th>
                            <th class="col-secundaria">Creado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                            <tr>
                                <td class="font-medium" data-label="Nombre">
                                    <a href="{{ route('users.show', $user) }}" title="Ver perfil del usuario" class="enlace-fila">
                                        {{ $user->name }}
                                    </a>
                                </td>
                                <td class="text-tinta-suave" data-label="Email">{{ $user->email }}</td>
                                <td data-label="Rol">
                                    <x-etiqueta-rol :rol="$user->rol()" />
                                </td>
                                <td data-label="Verificado">
                                    @if($user->hasVerifiedEmail())
                                        <span class="text-exito flex items-center gap-1"><x-lucide-circle-check-big class="w-4 h-4" /> Sí</span>
                                    @else
                                        <span class="text-peligro flex items-center gap-1"><x-lucide-x class="w-4 h-4" /> No</span>
                                    @endif
                                </td>
                                <td class="text-tinta-suave col-secundaria" data-label="Creado">{{ $user->created_at->format('d/m/Y') }}</td>
                                <td class="acciones" data-label="Acciones">
                                    <div class="grupo-acciones">
                                        <x-accion icono="eye" titulo="Ver perfil" href="{{ route('users.show', $user) }}" />
                                        @can('update', $user)
                                        <x-accion icono="pencil" titulo="Editar usuario" tono="marca" href="{{ route('users.edit', $user) }}" />
                                        @endcan
                                        @if(!$user->hasVerifiedEmail() && auth()->user()->hasVerifiedEmail())
                                        <form action="{{ route('users.verify', $user) }}" method="POST">
                                            @csrf
                                            <x-accion icono="check-circle" titulo="Verificar manualmente" tono="exito" type="submit" />
                                        </form>
                                        @endif
                                        @can('desactivar', $user)
                                        @if($user->activo)
                                        <form action="{{ route('users.desactivar', $user) }}" method="POST" id="form-baja-{{ $user->id }}">
                                            @csrf
                                            <x-accion icono="user-x" titulo="Dar de baja al usuario" tono="peligro" type="button" data-swal-baja data-title="¿Dar de baja?" data-text="No podrá iniciar sesión. Se conservan sus fichajes, informes y auditoría, y podrás reactivarlo desde el filtro Desactivados." data-form-id="form-baja-{{ $user->id }}" />
                                        </form>
                                        @else
                                        <form action="{{ route('users.reactivar', $user) }}" method="POST">
                                            @csrf
                                            <x-accion icono="user-check" titulo="Reactivar usuario" tono="exito" type="button" data-swal-submit data-title="¿Reactivar usuario?" data-text="Podrá volver a iniciar sesión." />
                                        </form>
                                        @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-linea">
                {{ $users->appends(request()->query())->links() }}
            </div>
        @else
            <div class="text-center py-12">
                <x-lucide-shield class="w-12 h-12 mx-auto block mb-3 text-tinta-tenue" />
                <p class="text-sm text-tinta-tenue italic">No hay usuarios registrados.</p>
                <a href="{{ route('users.create') }}" title="Crear el primer usuario" class="mt-2 inline-block text-sm text-marca-700 hover:text-marca-800 font-medium"><x-lucide-plus class="w-4 h-4 inline" /> <span class="hidden sm:inline">Crear primer usuario</span></a>
            </div>
        @endif

    </div>
</x-app-layout>
