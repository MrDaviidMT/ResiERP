<x-app-layout :volver="route('familiares.lista')" volver-texto="Volver a familiares" estrecho>
    <h1 class="titulo-pagina mb-6">Nuevo Familiar</h1>


@php
$resData = $residentes->map(fn($r) => [
    'id' => $r->id,
    'nombre' => $r->nombre,
    'apellidos' => $r->apellidos,
    'dni' => $r->dni,
    'habitacion_numero' => $r->habitacion?->numero,
])->values();
$createFamiliarData = [
    'vinculaciones' => $vinculaciones,
    'residentesList' => $resData,
    'preseleccionado' => $preseleccionadoData,
];
@endphp

    <div class="tarjeta p-6">
        <form method="POST" action="{{ route('familiares.storeGeneral') }}" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="etiqueta-campo block mb-1">Nombre <span class="text-peligro">*</span></label>
                    <input type="text" name="nombre" value="{{ old('nombre') }}" required placeholder="Nombre" class="campo">
                </div>
                <div>
                    <label class="etiqueta-campo block mb-1">Apellidos <span class="text-peligro">*</span></label>
                    <input type="text" name="apellidos" value="{{ old('apellidos') }}" required placeholder="Apellidos" class="campo">
                </div>
                <div>
                    <label class="etiqueta-campo block mb-1">DNI <span class="text-peligro">*</span></label>
                    <input type="text" name="dni" value="{{ old('dni') }}" required placeholder="12345678A" class="campo">
                </div>
                <div>
                    <label class="etiqueta-campo block mb-1">Teléfono <span class="text-peligro">*</span></label>
                    <input type="text" name="telefono" value="{{ old('telefono') }}" required class="campo" placeholder="+34 600 000 000">
                </div>
                <div>
                    <label class="etiqueta-campo block mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="correo@ejemplo.com" class="campo">
                </div>
                <div>
                    <label class="etiqueta-campo block mb-1">Dirección</label>
                    <input type="text" name="direccion" value="{{ old('direccion') }}" placeholder="Calle, número, ciudad..." class="campo">
                </div>
            </div>

            <div class="border-t border-linea pt-4"
                 x-data="createFamiliar(@js($createFamiliarData))">

                <label class="etiqueta-campo block mb-2">Vincular a residentes <span class="text-peligro">*</span></label>

                <div class="space-y-2 p-3 bg-fondo rounded border border-linea">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <template x-if="!selectedResidente">
                            <div class="relative">
                                <label class="etiqueta-campo block mb-1">Buscar residente</label>
                                <input type="text" x-model="searchQuery" @click.away="searchQuery = ''" placeholder="Nombre, apellidos o DNI..."
                                       class="campo">
                                <template x-if="searchQuery && resultados.length > 0">
                                    <div class="absolute z-10 left-0 right-0 mt-1 bg-superficie border border-linea rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                        <template x-for="r in resultados" :key="r.id">
                                            <button type="button" title="Seleccionar este residente" @click="seleccionar(r)"
                                                    class="w-full text-left px-3 py-2 text-sm hover:bg-marca-50 border-b border-linea last:border-0 flex items-center justify-between cursor-pointer">
                                                <span x-text="r.nombre + ' ' + r.apellidos"></span>
                                                <span class="text-xs text-tinta-tenue">
                                                    <span x-show="r.habitacion_numero" x-text="'Hab. ' + r.habitacion_numero"></span>
                                                    <span x-show="r.dni" x-text="r.dni"></span>
                                                </span>
                                            </button>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="searchQuery && resultados.length === 0">
                                    <p class="text-xs text-tinta-tenue italic mt-1">Sin resultados.</p>
                                </template>
                            </div>
                        </template>
                        <template x-if="selectedResidente">
                            <div>
                                <label class="etiqueta-campo block mb-1">Residente seleccionado</label>
                                <div class="flex items-center justify-between p-2 bg-superficie border border-marca-200 rounded-lg">
                                    <div class="grupo-acciones">
                                        <span class="text-sm font-medium" x-text="selectedResidente.nombre + ' ' + selectedResidente.apellidos"></span>
                                        <span class="text-xs text-tinta-tenue">
                                            <span x-show="selectedResidente.habitacion_numero" x-text="'Hab. ' + selectedResidente.habitacion_numero"></span>
                                            <span x-show="selectedResidente.dni" x-text="selectedResidente.dni"></span>
                                        </span>
                                    </div>
                                    <button type="button" title="Cambiar selección" @click="cancelarSeleccion" class="text-xs text-peligro hover:text-peligro cursor-pointer"><x-lucide-rotate-cw class="w-4 h-4 inline" /> <span class="hidden sm:inline">Cambiar</span></button>
                                </div>
                            </div>
                        </template>
                        <div>
                            <label class="etiqueta-campo block mb-1">Parentesco</label>
                            <select x-model="selectedParentesco" class="campo">
                                @foreach(['Hijo/a','Hija','Cónyuge','Hermano/a','Nieto/a','Sobrino/a','Primo/a','Tutor legal','Otro'] as $p)
                                    <option value="{{ $p }}">{{ $p }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center space-x-4">
                        <label class="flex items-center space-x-1 text-xs">
                            <input type="checkbox" x-model="selectedEmergencia" class="rounded border-linea-fuerte text-peligro">
                            <span class="text-peligro font-medium"><x-lucide-bell-ring class="w-3 h-3 inline" /> Emergencia</span>
                        </label>
                        <label class="flex items-center space-x-1 text-xs">
                            <input type="checkbox" x-model="selectedAutorizado" class="rounded border-linea-fuerte text-marca-700">
                            <span class="text-tinta font-medium"><x-lucide-circle-check-big class="w-3 h-3 inline" /> Autorizado</span>
                        </label>
                    </div>

                    <div>
                        <label class="etiqueta-campo block mb-1">Observaciones</label>
                        <textarea x-model="selectedObservaciones" placeholder="Notas adicionales..." rows="2" class="campo"></textarea>
                    </div>

                    <div class="grupo-acciones grupo-acciones--final">
                        <button type="button" title="Añadir vinculación" @click="addVinculacion" :disabled="!selectedResidente"
                                class="boton boton-primario">
                            <x-lucide-plus />Añadir
                        </button>
                    </div>
                </div>

                <template x-if="vinculaciones.length === 0">
                    <p class="text-sm text-tinta-tenue italic mb-2">Ningún residente vinculado todavía.</p>
                </template>

                <template x-for="(v, index) in vinculaciones" :key="index">
                    <div class="flex items-center justify-between p-2 mb-1 rounded border border-linea bg-superficie">
                        <div class="flex-1 flex items-center space-x-2">
                            <input type="hidden" :name="'vinculaciones[' + index + '][residente_id]'" :value="v.residente_id">
                            <input type="hidden" :name="'vinculaciones[' + index + '][parentesco]'" :value="v.parentesco">
                            <input type="hidden" :name="'vinculaciones[' + index + '][es_emergencia]'" :value="v.es_emergencia ? 1 : 0">
                            <input type="hidden" :name="'vinculaciones[' + index + '][autorizado]'" :value="v.autorizado ? 1 : 0">
                            <input type="hidden" :name="'vinculaciones[' + index + '][observaciones]'" :value="v.observaciones">
                            <span class="text-sm font-medium" x-text="v.nombre_completo"></span>
                            <span class="text-xs text-tinta-suave font-medium" x-text="'(' + v.parentesco + ')'"></span>
                            <template x-if="v.es_emergencia">
                                <x-lucide-bell-ring class="w-3.5 h-3.5 text-peligro" />
                            </template>
                            <template x-if="v.autorizado">
                                <x-lucide-circle-check-big class="w-3.5 h-3.5 text-exito" />
                            </template>
                            <span class="text-xs text-tinta-tenue italic" x-show="v.observaciones" x-text="v.observaciones"></span>
                        </div>
                        <button type="button" @click="removeVinculacion(index)" class="text-peligro hover:text-peligro cursor-pointer" title="Eliminar">
                            <x-lucide-trash-2 class="w-4 h-4" />
                        </button>
                    </div>
                </template>
            </div>

            <div class="grupo-acciones grupo-acciones--final pt-2">
                <a href="{{ route('familiares.lista') }}" title="Cancelar y volver al listado" class="boton tono-neutro"><x-lucide-x /> Cancelar</a>
                <button type="button" title="Añadir nuevo familiar" data-swal-submit data-title="Guardar familiar" data-text="¿Registrar este familiar?" class="boton boton-primario"><x-lucide-user-plus />Añadir Familiar</button>
            </div>

        </form>
    </div>
</x-app-layout>
