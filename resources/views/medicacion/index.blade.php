@use('App\Enums\Permission', 'P')
<x-app-layout :volver="route('residentes.index')" volver-texto="Volver a residentes">
    <x-residente-cabecera :residente="$residente" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        @can(P::MedicacionEditar)
        <div class="tarjeta p-6 h-fit">
            <h2 class="titulo-seccion mb-4">Recetar Fármaco</h2>
            
            <form action="{{ route('medicacion.store', $residente->id) }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="etiqueta-campo block">Nombre del Medicamento <span class="text-peligro">*</span></label>
                    <input type="text" name="medicamento" required placeholder="Ej: Nolotil, Metformina..." 
                           class="campo mt-1">
                </div>

                <div class="mb-4">
                    <label class="etiqueta-campo block">Dosis <span class="text-peligro">*</span></label>
                    <input type="text" name="dosis" required placeholder="Ej: 1 comprimido, 5ml" 
                           class="campo mt-1">
                </div>

                <div class="mb-4">
                    <label class="etiqueta-campo block mb-2">Horarios de Toma</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <label class="flex items-center gap-1.5 p-2 bg-fondo border border-linea rounded text-sm font-medium cursor-pointer hover:border-linea-fuerte">
                            <input type="checkbox" name="desayuno" class="rounded text-marca-700"> <x-lucide-sun class="w-4 h-4" /> Desayuno
                        </label>
                        <label class="flex items-center gap-1.5 p-2 bg-fondo border border-linea rounded text-sm font-medium cursor-pointer hover:border-linea-fuerte">
                            <input type="checkbox" name="comida" class="rounded text-marca-700"> <x-lucide-sun class="w-4 h-4" /> Comida
                        </label>
                        <label class="flex items-center gap-1.5 p-2 bg-fondo border border-linea rounded text-sm font-medium cursor-pointer hover:border-linea-fuerte">
                            <input type="checkbox" name="cena" class="rounded text-marca-700"> <x-lucide-moon class="w-4 h-4" /> Cena
                        </label>
                        <label class="flex items-center gap-1.5 p-2 bg-fondo border border-linea rounded text-sm font-medium cursor-pointer hover:border-linea-fuerte">
                            <input type="checkbox" name="noche" class="rounded text-marca-700"> <x-lucide-moon class="w-4 h-4" /> Noche
                        </label>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="etiqueta-campo block">Notas Médicas</label>
                    <textarea name="observaciones" rows="2" placeholder="Ej: Tomar en ayunas..." class="campo mt-1"></textarea>
                </div>

                <button type="button" title="Añadir medicamento a la pauta" data-swal-submit data-title="Recetar medicamento" data-text="¿Añadir este medicamento a la pauta?" class="boton boton-primario w-full">
                    <x-lucide-plus />Añadir a la pauta
                </button>
            </form>
        </div>
        @endcan

        <div class="{{ auth()->user()->can(P::MedicacionEditar) ? 'lg:col-span-2' : 'lg:col-span-3' }} space-y-4">
            <h2 class="titulo-seccion">Tratamientos activos</h2>
            
            @forelse($residente->medicaciones as $med)
                <div class="tarjeta p-5 flex flex-wrap justify-between items-center gap-4">
                    <div class="flex-1 min-w-48">
                        <div class="flex flex-wrap items-baseline gap-x-2">
                            <h3 class="titulo-seccion">{{ $med->medicamento }}</h3>
                            <span class="text-sm text-tinta-suave">({{ $med->dosis }})</span>
                        </div>

                        <div class="flex flex-wrap gap-1.5 mt-3">
                            @foreach(['desayuno' => ['Desayuno', 'sun'], 'comida' => ['Comida', 'sun'], 'cena' => ['Cena', 'moon'], 'noche' => ['Noche', 'moon']] as $toma => [$texto, $icono])
                                <span @class(['etiqueta', 'tono-marca' => $med->$toma, 'tono-neutro line-through font-normal' => ! $med->$toma])>
                                    <x-dynamic-component :component="'lucide-'.$icono" class="w-3 h-3" /> {{ $texto }}
                                </span>
                            @endforeach
                        </div>

                        @if($med->observaciones)
                            <p class="text-xs bg-fondo border-l-2 border-linea-fuerte p-2 text-tinta-suave mt-3 italic rounded-r">
                                Indicación: {{ $med->observaciones }}
                            </p>
                        @endif
                    </div>

                    <div class="grupo-acciones">
                        @can(P::MedicacionRetirar)
                        <x-boton-motivo :action="route('medicacion.retirar', $med)" titulo="Retirar pauta" confirmar="Retirar" icono="circle-stop" tono="aviso"
                            texto="La pauta dejará de administrarse y quedará en el historial de medicación. Indica el motivo clínico (mínimo 10 caracteres).">Retirar</x-boton-motivo>
                        @endcan
                        @can(P::MedicacionAnular)
                        <x-boton-motivo :action="route('medicacion.anular', $med)" titulo="Anular pauta (registrada por error)">Anular</x-boton-motivo>
                        @endcan
                    </div>
                </div>
            @empty
                <div class="bg-superficie p-8 text-center text-tinta-suave rounded-lg border border-linea italic text-sm">
                    <x-lucide-ban class="w-4 h-4 inline" /> El residente no tiene medicamentos pautados en este momento.
                </div>
            @endforelse

            @if($residente->medicacionesRetiradas->isNotEmpty())
                <h2 class="text-lg font-bold text-tinta pt-4">Historial de pautas</h2>
                @foreach($residente->medicacionesRetiradas as $med)
                    <div class="bg-fondo p-4 rounded-lg border border-linea text-sm text-tinta-suave">
                        <span class="font-bold text-tinta">{{ $med->medicamento }}</span> ({{ $med->dosis }})
                        <span class="block text-xs mt-1">Retirada el {{ $med->fecha_fin->format('d/m/Y H:i') }} por {{ $med->retiradoPor?->name ?? '—' }}. Motivo: {{ $med->motivo_retirada }}</span>
                    </div>
                @endforeach
            @endif

            <x-registros-anulados :registros="$anuladas" />
        </div>

    </div>
</x-app-layout>
