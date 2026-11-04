@use('App\Enums\Permission', 'P')
<x-app-layout>
    <div x-data="{ editId: null, detalleId: null }">

        <!-- Modal Detalle Habitación -->
        <div x-show="detalleId" x-cloak class="modal-fondo" @click.self="detalleId = null">
            <div class="modal" @click.stop>
                <div class="cabecera-pagina">
                    <h2 class="titulo-seccion">Detalle de Habitación</h2>
                    <button @click="detalleId = null" title="Cerrar" class="text-tinta-tenue hover:text-tinta-suave text-2xl leading-none cursor-pointer">&times;</button>
                </div>
                @foreach($todas as $habitacion)
                    <div x-show="detalleId === {{ $habitacion->id }}" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <span class="etiqueta-campo block">Número</span>
                                <span class="text-lg font-bold text-tinta">Hab. {{ $habitacion->numero }}</span>
                            </div>
                            <div>
                                <span class="etiqueta-campo block">Tipo</span>
                                <span class="text-sm font-medium capitalize text-tinta">{{ $habitacion->tipo }}</span>
                            </div>
                            <div>
                                <span class="etiqueta-campo block">Ocupación</span>
                                <span class="text-sm font-semibold text-tinta">
                                    {{ $habitacion->residentes_count }} / {{ $habitacion->capacidadMaxima() }} plazas
                                </span>
                            </div>
                            <div>
                                <span class="etiqueta-campo block">Estado</span>
                                @php($llena = $habitacion->residentes_count >= $habitacion->capacidadMaxima())
                                @if($habitacion->estado === 'libre')
                                    <span class="etiqueta tono-exito mt-1">Libre</span>
                                @elseif($habitacion->estado === 'ocupada' && $llena)
                                    <span class="etiqueta tono-peligro mt-1">Completa</span>
                                @elseif($habitacion->estado === 'ocupada')
                                    <span class="etiqueta tono-aviso mt-1">Ocupada</span>
                                @elseif($habitacion->estado === 'mantenimiento')
                                    <span class="etiqueta tono-aviso mt-1">Mantenimiento</span>
                                @endif
                            </div>
                        </div>

                        @can(P::ResidentesVer)
                        @if($habitacion->residentes->count() > 0)
                            <div>
                                <span class="etiqueta-campo block mb-1">Residentes asignados</span>
                                <div class="space-y-1">
                                    @foreach($habitacion->residentes as $res)
                                        <a href="{{ route('residentes.show', $res) }}" title="Ver perfil del residente" class="block text-sm text-marca-700 hover:text-marca-800 font-medium">
                                            <x-lucide-user class="w-3.5 h-3.5 inline" /> {{ $res->nombre }} {{ $res->apellidos }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        @endcan

                        @if($habitacion->observaciones)
                            <div>
                                <span class="etiqueta-campo block mb-1">Observaciones</span>
                                <p class="text-sm text-tinta whitespace-pre-wrap">{!! nl2br(e($habitacion->observaciones)) !!}</p>
                            </div>
                        @endif

                        <div class="grupo-acciones grupo-acciones--final pt-2">
                            <button @click="detalleId = null" title="Cerrar detalle" class="boton tono-neutro"><x-lucide-x /> Cerrar</button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Modal Editar Habitación -->
        <div x-show="editId" x-cloak class="modal-fondo" @click.self="editId = null">
            <div class="modal">
                <div class="cabecera-pagina">
                    <h2 class="titulo-seccion">Editar Habitación</h2>
                    <button @click="editId = null" title="Cerrar" class="text-tinta-tenue hover:text-tinta-suave cursor-pointer text-xl leading-none">&times;</button>
                </div>
                @foreach($todas as $habitacion)
                    <form x-show="editId === {{ $habitacion->id }}" method="POST" action="{{ route('habitaciones.update', $habitacion->id) }}" class="space-y-4">
                        @csrf @method('PUT')
                        <div>
                            <label class="etiqueta-campo block mb-1">Número</label>
                            <input type="text" value="{{ $habitacion->numero }}" disabled class="w-full rounded-md border-linea-fuerte p-2 border bg-fondo text-sm text-tinta-suave cursor-not-allowed">
                        </div>
                        <div>
                            <label class="etiqueta-campo block mb-1">Tipo</label>
                            <select name="tipo" class="campo">
                                <option value="individual" @selected($habitacion->tipo === 'individual')>Individual</option>
                                <option value="doble" @selected($habitacion->tipo === 'doble')>Doble</option>
                            </select>
                        </div>
                        <div>
                            <label class="etiqueta-campo block mb-1">Observaciones</label>
                            <textarea name="observaciones" rows="3" class="campo">{{ $habitacion->observaciones }}</textarea>
                        </div>
                        <div class="grupo-acciones grupo-acciones--final">
                            <button type="button" title="Cancelar edición" @click="editId = null" class="boton tono-neutro">
                                <x-lucide-x />Cancelar
                            </button>
                            <button type="submit" title="Guardar cambios de la habitación" class="boton boton-primario">
                                <x-lucide-save /> Guardar
                            </button>
                        </div>
                    </form>
                @endforeach
            </div>
        </div>

        <div class="cabecera-pagina">
                <h1 class="titulo-pagina">Gestión de Habitaciones</h1>
                @can(P::HabitacionesCrear)
                <a href="{{ route('habitaciones.create') }}" title="Añadir nueva habitación" class="boton boton-primario">
                    <x-lucide-plus />Nueva Habitación
                </a>
                @endcan
            </div>

        <div class="tarjeta p-6">
            <div class="overflow-x-auto">
                <table class="tabla ">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Tipo</th>
                            <th class="centrado">Ocupación Actual</th>
                            <th class="centrado">Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($habitaciones as $habitacion)
                            <tr>
                                <td class="font-bold text-lg text-marca-700" data-label="Número">{{ $habitacion->numero }}</td>
                                <td class="font-medium capitalize" data-label="Tipo">{{ $habitacion->tipo }}</td>
                                
                                <td class="centrado" data-label="Ocupación">
                                    <span class="font-semibold">
                                        {{ $habitacion->residentes_count }} / {{ $habitacion->capacidadMaxima() }}
                                    </span> plazas
                                </td>

                                <td class="centrado" data-label="Estado">
                                    @if($habitacion->estado === 'libre')
                                        <span class="etiqueta tono-exito">Libre</span>
                                    @elseif($habitacion->estado === 'ocupada' && $habitacion->residentes_count >= $habitacion->capacidadMaxima())
                                        <span class="etiqueta tono-peligro">Completa</span>
                                    @elseif($habitacion->estado === 'ocupada')
                                        <span class="etiqueta tono-aviso">Ocupada</span>
                                    @elseif($habitacion->estado === 'mantenimiento')
                                        <span class="etiqueta tono-aviso">Mantenimiento</span>
                                    @endif
                                </td>

                                <td class="acciones" data-label="Acciones">
                                    <div class="grupo-acciones">
                                    <x-accion icono="eye" titulo="Ver detalle" @click="detalleId = {{ $habitacion->id }}" />
                                    @can(P::HabitacionesEditar)
                                    <x-accion icono="pencil" titulo="Editar habitación" tono="marca" @click="editId = {{ $habitacion->id }}" />
                                    @endcan
                                    @can(P::HabitacionesMantenimiento)
                                    <form action="{{ route('habitaciones.mantenimiento', $habitacion->id) }}" method="POST" id="form-mantenimiento-{{ $habitacion->id }}">
                                        @csrf
                                        @if($habitacion->estado === 'mantenimiento')
                                            <x-accion icono="rotate-ccw" titulo="Reactivar habitación" tono="exito" type="button" data-swal-submit data-title="Reactivar habitación" data-text="¿Sacar esta habitación de mantenimiento?" />
                                        @else
                                            <x-accion icono="wrench" titulo="Poner en mantenimiento" tono="aviso" type="button" data-swal-delete data-title="Poner en mantenimiento" data-text="¿Seguro que quieres poner esta habitación en mantenimiento? Se bloqueará para nuevos ingresos." data-form-id="form-mantenimiento-{{ $habitacion->id }}" />
                                        @endif
                                    </form>
                                    @endcan
                                    @can(P::HabitacionesBorrar)
                                    <form action="{{ route('habitaciones.destroy', $habitacion->id) }}" method="POST" id="form-eliminar-hab-{{ $habitacion->id }}">
                                        @csrf @method('DELETE')
                                        <x-accion icono="trash-2" titulo="Eliminar habitación" tono="peligro" type="button" data-swal-delete data-title="Eliminar habitación" data-text="¿Eliminar la habitación {{ $habitacion->numero }}? Esta acción no se puede deshacer." data-form-id="form-eliminar-hab-{{ $habitacion->id }}" />
                                    </form>
                                    @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="centrado text-tinta-suave italic" data-label="">No hay habitaciones configuradas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-linea mt-4">
                {{ $habitaciones->appends(request()->query())->links() }}
            </div>

        </div>
    </div>
</x-app-layout>
