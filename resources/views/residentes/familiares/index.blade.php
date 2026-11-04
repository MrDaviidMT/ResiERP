@use('App\Enums\Permission', 'P')
<x-app-layout :volver="route('residentes.index')" volver-texto="Volver a residentes">
    <x-residente-cabecera :residente="$residente" />

    @php
        $famData = $todosFamiliares->map(fn($f) => ['id' => $f->id, 'nombre' => $f->nombre_completo, 'dni' => $f->dni])->values();
        $vinculadosIds = $residente->familiares->pluck('id')->toArray();
        $vincularData = [
            'familiares' => $famData,
            'idsVinculados' => $vinculadosIds,
        ];
    @endphp

    <div class="tarjeta p-6"
         x-data="vincularFamiliar(@js($vincularData))">

        <div class="cabecera-pagina">
            <h2 class="titulo-seccion">
                Contactos Registrados
                <span class="ml-2 text-sm font-normal text-tinta-tenue">({{ $residente->familiares->count() }})</span>
            </h2>
            <div class="grupo-acciones">
                @can(P::FamiliaresCrear)
                <a href="{{ route('familiares.create', ['residente_id' => $residente->id]) }}"
                   title="Añadir nuevo familiar" class="boton boton-primario">
                    <x-lucide-user-plus />Añadir Familiar
                </a>
                <button @click="toggleForm" title="Vincular un familiar ya registrado" class="boton tono-neutro">
                    <x-lucide-link />Vincular existente
                </button>
                @endcan
            </div>
        </div>

        <div x-show="mostrarForm" class="mb-6 p-4 bg-fondo rounded-lg border border-linea">
            <form method="POST" action="{{ route('familiares.vincular-existente', $residente) }}" class="space-y-3">
                @csrf

                <template x-if="!familiarSeleccionado">
                    <div class="relative">
                        <label class="etiqueta-campo block mb-1">Buscar familiar</label>
                        <input type="text" x-model="searchQuery" @click.away="searchQuery = ''" placeholder="Nombre o DNI..."
                               class="campo">
                        <template x-if="searchQuery && resultados.length > 0">
                            <div class="absolute z-10 left-0 right-0 mt-1 bg-superficie border border-linea rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                <template x-for="f in resultados" :key="f.id">
                                    <button type="button" title="Seleccionar este familiar" @click="seleccionar(f)"
                                            class="w-full text-left px-3 py-2 text-sm hover:bg-marca-50 border-b border-linea last:border-0 flex items-center justify-between cursor-pointer">
                                        <span x-text="f.nombre"></span>
                                        <span class="text-xs text-tinta-tenue" x-text="f.dni"></span>
                                    </button>
                                </template>
                            </div>
                        </template>
                        <template x-if="searchQuery && resultados.length === 0">
                            <p class="text-xs text-tinta-tenue italic mt-1">Sin resultados.</p>
                        </template>
                    </div>
                </template>

                <template x-if="familiarSeleccionado">
                    <div>
                        <div class="flex items-center justify-between p-3 bg-superficie border border-marca-200 rounded-lg">
                            <div>
                                <span class="text-sm font-medium" x-text="familiarSeleccionado.nombre"></span>
                                <span class="text-xs text-tinta-tenue ml-2" x-text="'(' + familiarSeleccionado.dni + ')'"></span>
                            </div>
                            <button type="button" title="Cambiar selección" @click="cancelarSeleccion" class="text-xs text-peligro hover:text-peligro cursor-pointer"><x-lucide-rotate-cw class="w-4 h-4 inline" /><span class="hidden sm:inline">Cambiar</span></button>
                        </div>
                        <input type="hidden" name="familiar_id" :value="familiarSeleccionado.id">

                        <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="etiqueta-campo block mb-1">Parentesco <span class="text-peligro">*</span></label>
                                <select name="parentesco" required class="campo">
                                    @foreach(['Hijo/a','Hija','Cónyuge','Hermano/a','Nieto/a','Sobrino/a','Primo/a','Tutor legal','Otro'] as $p)
                                        <option value="{{ $p }}">{{ $p }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4 mt-3">
                            <label class="flex items-center space-x-1 text-xs">
                                <input type="checkbox" name="es_emergencia" value="1" class="rounded border-linea-fuerte text-peligro">
                                <span class="text-peligro font-medium"><x-lucide-bell-ring class="w-3 h-3 inline" /> Emergencia</span>
                            </label>
                            <label class="flex items-center space-x-1 text-xs">
                                <input type="checkbox" name="autorizado" value="1" checked class="rounded border-linea-fuerte text-marca-700">
                                <span class="text-tinta font-medium"><x-lucide-circle-check-big class="w-3 h-3 inline" /> Autorizado</span>
                            </label>
                        </div>
                        <div class="mt-3">
                            <label class="etiqueta-campo block mb-1">Observaciones</label>
                            <textarea name="observaciones" rows="2" class="campo"></textarea>
                        </div>
                        <div class="grupo-acciones grupo-acciones--final mt-3">
                            <button type="button" title="Cancelar vinculación" @click="cancelarSeleccion" class="boton tono-neutro"><x-lucide-x />Cancelar</button>
                            <button type="submit" title="Vincular familiar al residente" class="boton tono-neutro"><x-lucide-user-plus />Vincular</button>
                        </div>
                    </div>
                </template>
            </form>
        </div>

        @if($residente->familiares->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($residente->familiares as $familiar)
                    @php $pivot = $familiar->pivot; @endphp
                    <div x-data @click="window.location='{{ route('familiares.show', $familiar) }}'" class="border border-linea rounded-lg p-4 hover:shadow-md hover:bg-fondo hover:border-marca-200 transition cursor-pointer">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <span class="font-bold text-tinta">{{ $familiar->nombre_completo }}</span>
                                @if($familiar->dni)
                                    <span class="text-xs text-tinta-tenue block">{{ $familiar->dni }}</span>
                                @endif
                                <span class="etiqueta tono-neutro mt-1">
                                    {{ $pivot->parentesco }}
                                </span>
                            </div>
                        </div>
                        <div class="space-y-1 text-sm">
                            <div class="grupo-acciones">
                                <x-lucide-phone class="w-4 h-4 text-tinta-tenue" />
                                <a @click.stop href="tel:{{ $familiar->telefono }}" title="Llamar al {{ $familiar->telefono }}" class="text-marca-700 font-medium">{{ $familiar->telefono }}</a>
                            </div>
                            @if($familiar->email)
                                <div class="grupo-acciones">
                                    <x-lucide-mail class="w-4 h-4 text-tinta-tenue" />
                                    <span class="text-tinta-suave">{{ $familiar->email }}</span>
                                </div>
                            @endif
                            @if($familiar->direccion)
                                <div class="grupo-acciones">
                                    <x-lucide-map-pin class="w-4 h-4 text-tinta-tenue" />
                                    <span class="text-tinta-suave text-xs">{{ $familiar->direccion }}</span>
                                </div>
                            @endif
                        </div>

                        @if($familiar->residentes->count() > 1)
                            <div class="mt-2 pt-2 border-t border-linea">
                                <span class="etiqueta-campo">También vinculado a:</span>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @foreach($familiar->residentes as $otroResidente)
                                        @if($otroResidente->id !== $residente->id)
                                            <a @click.stop href="{{ route('residentes.show', $otroResidente) }}" class="etiqueta tono-neutro hover:text-marca-700">
                                                {{ $otroResidente->nombre }} {{ $otroResidente->apellidos }}
                                            </a>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="flex items-center space-x-3 mt-3 pt-2 border-t border-linea">
                            @if($pivot->es_emergencia)
                                <span class="text-xs font-bold text-peligro flex items-center space-x-1">
                                    <x-lucide-bell-ring class="w-3.5 h-3.5" /> <span>Emergencia</span>
                                </span>
                            @endif
                            @if($pivot->autorizado)
                                <span class="text-xs text-exito flex items-center space-x-1">
                                    <x-lucide-circle-check-big class="w-3.5 h-3.5" /> <span>Autorizado</span>
                                </span>
                            @endif
                        </div>
                        @if($pivot->observaciones)
                            <p class="mt-2 text-xs text-tinta-tenue italic">{!! nl2br(e($pivot->observaciones)) !!}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <x-lucide-users class="w-12 h-12 mx-auto block mb-3 text-tinta-tenue" />
                <p class="text-sm text-tinta-tenue italic">No hay familiares registrados para este residente.</p>
                <p class="text-xs text-tinta-tenue mt-1">Añade el primer contacto usando el botón de arriba.</p>
            </div>
        @endif

    </div>
</x-app-layout>
