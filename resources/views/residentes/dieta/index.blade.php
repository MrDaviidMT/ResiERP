@use('App\Enums\Permission', 'P')
<x-app-layout :volver="route('residentes.index')" volver-texto="Volver a residentes">
    <x-residente-cabecera :residente="$residente" />

    <div class="tarjeta p-6" x-data="{ editando: false }">

        @if($residente->dieta)
            <div x-show="!editando">
                <div class="cabecera-pagina">
                    <h3 class="text-sm font-bold text-tinta-suave uppercase tracking-wider">Dieta Asignada</h3>
                    @can(P::DietasEditar)
                    <button @click="editando = true" title="Editar plan de dieta" class="boton tono-marca">
                        <x-lucide-pencil />Editar Dieta
                    </button>
                    @endcan
                </div>
                <div class="space-y-4">
                    <div class="aviso tono-aviso">
                        <span class="text-xs text-aviso font-bold uppercase tracking-wider">Tipo de Dieta</span>
                        <p class="text-lg font-bold text-tinta capitalize mt-1">{{ str_replace('_', ' ', $residente->dieta->tipo) }}</p>
                    </div>
                    @if($residente->dieta->alergias_alimentarias)
                        <div class="aviso tono-peligro">
                            <span class="text-xs text-peligro font-bold uppercase tracking-wider"><x-lucide-triangle-alert class="w-3.5 h-3.5 inline" /> Alergias Alimentarias</span>
                            <p class="text-sm text-peligro mt-1 whitespace-pre-wrap">{{ $residente->dieta->alergias_alimentarias }}</p>
                        </div>
                    @endif
                    @if($residente->dieta->preferencias)
                        <div class="aviso tono-exito">
                            <span class="text-xs text-exito font-bold uppercase tracking-wider"><x-lucide-thumbs-up class="w-3.5 h-3.5 inline" /> Preferencias</span>
                            <p class="text-sm text-exito mt-1 whitespace-pre-wrap">{{ $residente->dieta->preferencias }}</p>
                        </div>
                    @endif
                    @if($residente->dieta->observaciones)
                        <div class="p-4 bg-fondo rounded-lg border border-linea">
                            <span class="text-xs text-tinta-suave font-bold uppercase tracking-wider">Observaciones</span>
                            <p class="text-sm text-tinta mt-1 whitespace-pre-wrap">{{ $residente->dieta->observaciones }}</p>
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div x-show="!editando" class="text-center py-8">
                <x-lucide-utensils-crossed class="w-12 h-12 mx-auto text-tinta-tenue mb-3" />
                <p class="text-tinta-tenue mb-4">Este residente no tiene dieta asignada.</p>
                @can(P::DietasEditar)
                <button @click="editando = true" title="Asignar dieta al residente" class="boton boton-primario">
                    <x-lucide-utensils-crossed />Asignar Dieta
                </button>
                @endcan
            </div>
        @endif

        <div x-show="editando" x-cloak>
            @if($residente->dieta)
                <h3 class="text-sm font-bold text-tinta-suave uppercase tracking-wider mb-4">Editar Dieta</h3>
            @endif
            <form method="POST" action="{{ route('dieta.store', $residente) }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="etiqueta-campo block mb-1">Tipo de Dieta</label>
                        <select name="tipo" class="campo">
                            <option value="normal" @selected($residente->dieta?->tipo === 'normal')>Normal</option>
                            <option value="diabetica" @selected($residente->dieta?->tipo === 'diabetica')>Diabética</option>
                            <option value="hipocalorica" @selected($residente->dieta?->tipo === 'hipocalorica')>Hipocalórica</option>
                            <option value="sin_sal" @selected($residente->dieta?->tipo === 'sin_sal')>Sin Sal</option>
                            <option value="triturada" @selected($residente->dieta?->tipo === 'triturada')>Triturada</option>
                            <option value="blanda" @selected($residente->dieta?->tipo === 'blanda')>Blanda</option>
                            <option value="renal" @selected($residente->dieta?->tipo === 'renal')>Renal</option>
                            <option value="otras" @selected($residente->dieta?->tipo === 'otras')>Otras</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="etiqueta-campo block mb-1">Alergias Alimentarias</label>
                    <textarea name="alergias_alimentarias" rows="2" placeholder="Ej: Frutos secos, lactosa..." class="campo">{{ $residente->dieta?->alergias_alimentarias }}</textarea>
                </div>
                <div>
                    <label class="etiqueta-campo block mb-1">Preferencias</label>
                    <textarea name="preferencias" rows="2" class="campo" placeholder="Alimentos que le gustan o no le gustan...">{{ $residente->dieta?->preferencias }}</textarea>
                </div>
                <div>
                    <label class="etiqueta-campo block mb-1">Observaciones</label>
                    <textarea name="observaciones" rows="2" placeholder="Observaciones sobre la dieta..." class="campo">{{ $residente->dieta?->observaciones }}</textarea>
                </div>
                <div class="grupo-acciones grupo-acciones--final">
                    <button type="button" title="Cancelar cambios de dieta" @click="editando = false" class="boton tono-neutro"><x-lucide-x />Cancelar</button>
                    <button type="button" title="Guardar o actualizar dieta" data-swal-submit data-title="{{ $residente->dieta ? 'Actualizar' : 'Guardar' }} dieta" data-text="¿{{ $residente->dieta ? 'Actualizar la' : 'Asignar esta' }} dieta?" class="boton boton-primario">
                        <x-lucide-save />{{ $residente->dieta ? 'Actualizar Dieta' : 'Guardar Dieta' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
