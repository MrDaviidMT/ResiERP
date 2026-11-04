@use('App\Enums\Permission', 'P')
<x-app-layout :volver="route('residentes.index')" volver-texto="Volver a residentes">
    <x-residente-cabecera :residente="$residente" />

    <div class="lg:grid lg:grid-cols-3 lg:gap-6 space-y-6 lg:space-y-0" x-data="visitasApp({
        defaultForm: { familiar_id: '', nombre_visitante: '', documento: '', entrada_date: '{{ now()->format('Y-m-d') }}', entrada_time: '{{ now()->format('H:i') }}', observaciones: '' },
        storeUrl: '{{ route('visitas.store', $residente) }}',
        basePath: '/visitas'
    })">

        @php $puedeRegistrar = auth()->user()->canAny([P::VisitasRegistrar, P::VisitasEditar]); @endphp
        <div class="{{ $puedeRegistrar ? 'lg:col-span-2' : 'lg:col-span-3' }}">
            <div class="tarjeta p-6">
                <div class="cabecera-pagina">
                    <h2 class="text-lg font-bold text-tinta flex items-center">Historial de Visitas
                        <span class="ml-2 text-sm font-normal text-tinta-tenue">({{ $residente->visitas->count() }})</span>
                    </h2>
                </div>

                @if($residente->visitas->count() > 0)
                    <div class="space-y-3">
                        @foreach($residente->visitas as $visita)
                            @php $esFutura = $visita->entrada->isFuture(); @endphp
                            <div class="flex flex-wrap justify-between items-start gap-3 p-3 rounded-lg border transition
                                {{ $visita->en_curso ? 'border-exito bg-exito-suave' : ($esFutura ? 'border-marca-300 bg-marca-50 hover:bg-marca-100' : 'border-linea hover:bg-fondo') }}">
                                <div class="flex-1 min-w-48">
                                    <div class="grupo-acciones">
                                        <span class="font-medium text-tinta">{{ $visita->nombre_visitante }}</span>
                                        @php $parentesco = $residente->familiares->firstWhere('id', $visita->familiar_id)?->pivot->parentesco; @endphp
                                        @if($parentesco)
                                            <span class="etiqueta tono-marca">{{ $parentesco }}</span>
                                        @endif
                                        @if($visita->en_curso)
                                            <span class="etiqueta tono-exito">EN CURSO</span>
                                        @elseif($esFutura)
                                            <span class="etiqueta tono-marca">Pendiente</span>
                                        @elseif($visita->salida)
                                            <span class="etiqueta tono-neutro">Finalizada</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center space-x-3 text-xs text-tinta-suave mt-1">
                                        <x-lucide-clock class="w-4 h-4 inline" /> <span>{{ $visita->entrada->format('d/m/Y H:i') }}</span>
                                        @if($visita->salida)
                                            <span>→ {{ $visita->salida->format('H:i') }}</span>
                                            <span class="text-tinta-tenue">({{ $visita->duracion }})</span>
                                        @endif
                                    </div>
                                    @if($visita->documento)
                                        <span class="text-xs text-tinta-tenue block mt-0.5">Doc: {{ $visita->documento }}</span>
                                    @endif
                                    @if($visita->observaciones)
                                        <p class="text-xs text-tinta-tenue italic mt-1">{{ $visita->observaciones }}</p>
                                    @endif
                                </div>
                                <div class="grupo-acciones">
@php $vBtn = ['id' => $visita->id, 'familiar_id' => $visita->familiar_id, 'nombre_visitante' => $visita->nombre_visitante, 'documento' => $visita->documento, 'entrada_date' => $visita->entrada->format('Y-m-d'), 'entrada_time' => $visita->entrada->format('H:i'), 'observaciones' => $visita->observaciones]; @endphp
                                    @can(P::VisitasEditar)
                                    <button type="button" title="Editar visita" data-json='@json($vBtn)' data-action='/visitas/{{ $visita->id }}' @click="editar(JSON.parse($event.currentTarget.dataset.json), $event.currentTarget.dataset.action)" class="text-xs text-marca-600 hover:text-marca-700 cursor-pointer"><x-lucide-pencil class="w-4 h-4" /></button>
                                    @endcan
                                    @if($visita->en_curso && auth()->user()->can(P::VisitasRegistrar))
                                        <form method="POST" action="{{ route('visitas.salida', $visita) }}">
                                            @csrf
                                            <button type="button" title="Registrar salida del visitante" data-swal-submit data-title="Registrar salida" data-text="¿Registrar la salida de este visitante?" class="boton tono-exito"><x-lucide-log-out />Registrar Salida</button>
                                        </form>
                                    @endif
                                    @can(P::VisitasAnular)
                                    <x-boton-motivo :action="route('visitas.anular', $visita)" titulo="Anular registro de visita">Anular</x-boton-motivo>
                                    @endcan
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-tinta-tenue italic text-center py-8">No hay visitas registradas. Registra la primera usando el formulario.</p>
                @endif
                <x-registros-anulados :registros="$anuladas" />
            </div>
        </div>

        @if($puedeRegistrar)
        <div>
            <div class="tarjeta p-6">
                <h2 class="text-lg font-bold text-tinta flex items-center mb-4"><span x-text="editId ? 'Editar Visita' : 'Registrar Visita'"></span>
                </h2>
                <form method="POST" x-ref="form" action="{{ route('visitas.store', $residente) }}" class="space-y-3">
                    @csrf
                    <input type="hidden" name="_method" value="POST">
                    @if($residente->familiaresAutorizados->count() > 0)
                        <div>
                            <label class="etiqueta-campo block mb-1">Familiar registrado</label>
                            <select name="familiar_id" x-model="form.familiar_id" class="campo">
                                <option value="">— Visitante no registrado —</option>
                                @foreach($residente->familiaresAutorizados as $f)
                                    <option value="{{ $f->id }}">{{ $f->nombre_completo }} ({{ $f->pivot->parentesco }})</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div>
                        <label class="etiqueta-campo block mb-1">Nombre del Visitante <span class="text-peligro">*</span></label>
                        <input type="text" name="nombre_visitante" required placeholder="Nombre completo del visitante" x-model="form.nombre_visitante" class="campo">
                    </div>
                    <div>
                        <label class="etiqueta-campo block mb-1">Documento (DNI) <span class="text-peligro">*</span></label>
                        <input type="text" name="documento" required placeholder="12345678A" x-model="form.documento" class="campo">
                    </div>
                    <div>
                        <label class="etiqueta-campo block mb-1">Fecha Entrada <span class="text-peligro">*</span></label>
                        <input type="date" name="entrada_date" required x-model="form.entrada_date" class="campo">
                    </div>
                    <div>
                        <label class="etiqueta-campo block mb-1">Hora Entrada <span class="text-peligro">*</span></label>
                        <input type="time" name="entrada_time" required x-model="form.entrada_time" class="campo">
                        <input type="hidden" name="entrada" :value="form.entrada_date + 'T' + form.entrada_time">
                    </div>
                    <div>
                        <label class="etiqueta-campo block mb-1">Observaciones</label>
                        <textarea name="observaciones" rows="2" placeholder="Motivo de la visita, notas..." x-model="form.observaciones" class="campo"></textarea>
                    </div>
                    <div class="grupo-acciones">
                        <button type="button" title="Cancelar edición" @click="cancelarEdit()" x-show="editId" class="boton tono-neutro flex-1"><x-lucide-x />Cancelar</button>
                        <button type="button" title="Registrar o actualizar visita" data-swal-submit :data-title="editId ? 'Actualizar visita' : 'Registrar visita'" :data-text="editId ? '¿Actualizar esta visita?' : '¿Registrar la entrada de este visitante?'" class="boton boton-primario flex-1"><x-lucide-log-in /><span x-show="!editId">Registrar Entrada</span><span x-show="editId">Actualizar Visita</span></button>
                    </div>
                </form>
            </div>
        </div>
        @endif

    </div>
</x-app-layout>
