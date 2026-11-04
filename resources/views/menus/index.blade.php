<x-app-layout>
    <div class="cabecera-pagina">
        <h1 class="titulo-pagina">Planificación de Menús
        </h1>
        <button @click="$refs.formMenu.classList.toggle('hidden')" title="Crear nuevo menú diario" class="boton boton-primario">
            <x-lucide-plus />Nuevo Menú Diario
        </button>
    </div>




    <div x-ref="formMenu" class="hidden bg-superficie p-4 rounded-lg border border-linea mb-6">
        <form method="POST" action="{{ route('menus.store') }}" class="space-y-3">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="etiqueta-campo block mb-1">Fecha <span class="text-peligro">*</span></label>
                    <input type="date" name="fecha" required value="{{ now()->format('Y-m-d') }}" class="campo">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div>
                        <label class="etiqueta-campo block mb-1"><x-lucide-sun class="w-3.5 h-3.5 inline" /> Desayuno</label>
                    <textarea name="desayuno" rows="2" class="campo"></textarea>
                </div>
                <div>
                        <label class="etiqueta-campo block mb-1"><x-lucide-sun class="w-3.5 h-3.5 inline" /> Comida</label>
                    <textarea name="comida" rows="2" class="campo"></textarea>
                </div>
                <div>
                        <label class="etiqueta-campo block mb-1"><x-lucide-sun class="w-3.5 h-3.5 inline" /> Merienda</label>
                    <textarea name="merienda" rows="2" class="campo"></textarea>
                </div>
                <div>
                        <label class="etiqueta-campo block mb-1"><x-lucide-moon class="w-3.5 h-3.5 inline" /> Cena</label>
                    <textarea name="cena" rows="2" class="campo"></textarea>
                </div>
            </div>
            <div>
                <label class="etiqueta-campo block mb-1">Observaciones</label>
                <textarea name="observaciones" rows="2" class="campo"></textarea>
            </div>
            <div class="grupo-acciones grupo-acciones--final">
                <button type="button" title="Cancelar creación de menú" @click="$refs.formMenu.classList.add('hidden')" class="boton tono-neutro"><x-lucide-x /> Cancelar</button>
                <button type="button" title="Guardar nuevo menú" data-swal-submit data-title="Crear menú" data-text="¿Guardar este menú diario?" class="boton boton-primario"><x-lucide-check-circle />Guardar Menú</button>
            </div>
        </form>
    </div>

    <div class="tarjeta">
        @if($menus->count() > 0)
            <div class="divide-y divide-linea">
                @foreach($menus as $menu)
                    <div class="p-4 hover:bg-fondo transition" x-data="{ editMenu: false }">
                        <template x-if="!editMenu">
                            <div>
                                <div class="cabecera-pagina mb-2">
                                    <span class="text-sm font-bold text-tinta">{{ Str::ucfirst($menu->fecha->translatedFormat('l d/m/Y')) }}</span>
                                    <div class="grupo-acciones">
                                        <button @click="editMenu = true" title="Editar menú" class="boton tono-marca"><x-lucide-pencil />Editar</button>
                                        <form method="POST" action="{{ route('menus.destroy', $menu) }}" id="form-eliminar-menu-{{ $menu->id }}">
                                            @csrf @method('DELETE')
                                            <button type="button" title="Eliminar menú" data-swal-delete data-title="Eliminar menú" data-text="¿Eliminar este menú?" data-form-id="form-eliminar-menu-{{ $menu->id }}" class="boton tono-peligro"><x-lucide-trash-2 />Eliminar</button>
                                        </form>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm">
                                    <div><span class="text-xs text-tinta-tenue block">Desayuno</span><span class="font-medium">{{ $menu->desayuno ?? '—' }}</span></div>
                                    <div><span class="text-xs text-tinta-tenue block">Comida</span><span class="font-medium">{{ $menu->comida ?? '—' }}</span></div>
                                    <div><span class="text-xs text-tinta-tenue block">Merienda</span><span class="font-medium">{{ $menu->merienda ?? '—' }}</span></div>
                                    <div><span class="text-xs text-tinta-tenue block">Cena</span><span class="font-medium">{{ $menu->cena ?? '—' }}</span></div>
                                </div>
                                @if($menu->observaciones)
                                    <p class="text-xs text-tinta-tenue mt-1 italic">{{ $menu->observaciones }}</p>
                                @endif
                            </div>
                        </template>
                        <template x-if="editMenu">
                            <form method="POST" action="{{ route('menus.update', $menu) }}" class="space-y-2">
                                @csrf @method('PUT')
                                <input type="date" name="fecha" value="{{ $menu->fecha->format('Y-m-d') }}" required class="text-sm rounded-md border-linea-fuerte p-2 border bg-superficie w-full mb-2">
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                    <textarea name="desayuno" rows="2" class="text-sm rounded-md border-linea-fuerte p-2 border bg-superficie w-full" placeholder="Desayuno">{{ $menu->desayuno }}</textarea>
                                    <textarea name="comida" rows="2" class="text-sm rounded-md border-linea-fuerte p-2 border bg-superficie w-full" placeholder="Comida">{{ $menu->comida }}</textarea>
                                    <textarea name="merienda" rows="2" class="text-sm rounded-md border-linea-fuerte p-2 border bg-superficie w-full" placeholder="Merienda">{{ $menu->merienda }}</textarea>
                                    <textarea name="cena" rows="2" class="text-sm rounded-md border-linea-fuerte p-2 border bg-superficie w-full" placeholder="Cena">{{ $menu->cena }}</textarea>
                                </div>
                                <textarea name="observaciones" rows="1" class="text-sm rounded-md border-linea-fuerte p-2 border bg-superficie w-full">{{ $menu->observaciones }}</textarea>
                                <div class="grupo-acciones grupo-acciones--final">
                                    <button type="button" title="Cancelar edición de menú" @click="editMenu = false" class="boton tono-neutro"><x-lucide-x /> Cancelar</button>
                                    <button type="button" title="Guardar cambios del menú" data-swal-submit data-title="Actualizar menú" data-text="¿Guardar los cambios en este menú?" class="boton boton-primario"><x-lucide-check />Guardar</button>
                                </div>
                            </form>
                        </template>
                    </div>
                @endforeach
            </div>
            <div class="p-4 border-t border-linea">
                {{ $menus->links() }}
            </div>
        @else
            <p class="text-sm text-tinta-tenue italic text-center py-8">No hay menús planificados.</p>
        @endif
    </div>
</x-app-layout>
