<x-app-layout :volver="route('familiares.show', $familiar)" volver-texto="Volver al familiar" estrecho>
    <h1 class="titulo-pagina mb-6">Editar: {{ $familiar->nombre_completo }}</h1>


@php
$resData = $residentes->map(fn($r) => [
    'id' => $r->id,
    'nombre' => $r->nombre,
    'apellidos' => $r->apellidos,
    'habitacion_numero' => $r->habitacion?->numero,
])->values();
$editFamiliarData = [
    'vinculaciones' => $vinculaciones,
    'residentesList' => $resData,
];
@endphp

<div class="tarjeta p-6">
        <form method="POST" action="{{ route('familiares.update', $familiar) }}" class="space-y-4">
            @csrf @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="etiqueta-campo block mb-1">Nombre <span class="text-peligro">*</span></label>
                    <input type="text" name="nombre" value="{{ old('nombre', $familiar->nombre) }}" required placeholder="Nombre" class="campo">
                </div>
                <div>
                    <label class="etiqueta-campo block mb-1">Apellidos <span class="text-peligro">*</span></label>
                    <input type="text" name="apellidos" value="{{ old('apellidos', $familiar->apellidos) }}" required placeholder="Apellidos" class="campo">
                </div>
                <div>
                    <label class="etiqueta-campo block mb-1">DNI <span class="text-peligro">*</span></label>
                    <input type="text" name="dni" value="{{ old('dni', $familiar->dni) }}" required placeholder="12345678A" class="campo">
                </div>
                <div>
                    <label class="etiqueta-campo block mb-1">Teléfono <span class="text-peligro">*</span></label>
                    <input type="text" name="telefono" value="{{ old('telefono', $familiar->telefono) }}" required placeholder="+34 600 000 000" class="campo">
                </div>
                <div>
                    <label class="etiqueta-campo block mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email', $familiar->email) }}" placeholder="correo@ejemplo.com" class="campo">
                </div>
                <div>
                    <label class="etiqueta-campo block mb-1">Dirección</label>
                    <input type="text" name="direccion" value="{{ old('direccion', $familiar->direccion) }}" placeholder="Calle, número, ciudad..." class="campo">
                </div>
            </div>

            <div class="border-t border-linea pt-4"
                 x-data="editFamiliar(@js($editFamiliarData))">

                <label class="etiqueta-campo block mb-2">Vincular a residentes <span class="text-peligro">*</span></label>

                <div class="space-y-3 mb-4 p-3 bg-fondo rounded border border-linea">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="etiqueta-campo block mb-1">Residente</label>
                            <select x-model="selectedResidenteId" class="campo">
                                <option value="">-- Seleccionar --</option>
                                <template x-for="r in disponibles" :key="r.id">
                                    <option :value="r.id" x-text="r.nombre + ' ' + r.apellidos + (r.habitacion_numero ? ' (Hab. ' + r.habitacion_numero + ')' : '')"></option>
                                </template>
                            </select>
                        </div>
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
                        <button type="button" title="Añadir vinculación" @click="addVinculacion" :disabled="!selectedResidenteId"
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
                <a href="{{ route('familiares.show', $familiar) }}" title="Cancelar y volver al perfil" class="boton tono-neutro"><x-lucide-x /> Cancelar</a>
                <button type="button" title="Guardar cambios del familiar" data-swal-submit data-title="Actualizar familiar" data-text="¿Guardar los cambios?" class="boton boton-primario"><x-lucide-save />Guardar Cambios</button>
            </div>

        </form>
    </div>
</x-app-layout>
