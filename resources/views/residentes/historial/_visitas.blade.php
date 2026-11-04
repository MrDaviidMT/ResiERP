@use('App\Enums\Permission', 'P')
<div class="space-y-6" x-data="crudForm({
    defaultForm: { medico: '', especialidad: '', motivo: '', fecha_visita_date: '{{ now()->format('Y-m-d') }}', fecha_visita_time: '{{ now()->format('H:i') }}' },
    storeUrl: '{{ route('visitas_medicas.store', $residente) }}',
    basePath: '/visitas-medicas'
})">
    <div class="flex justify-between items-center">
        <h3 class="text-lg font-bold text-tinta flex items-center">
            <x-lucide-building-2 class="w-5 h-5 mr-1" /> Visitas Médicas
            <span class="ml-2 text-sm font-normal text-tinta-tenue">({{ $residente->visitasMedicas->count() }})</span>
        </h3>
        @can(P::VisitasMedicasGestionar)
        <button @click="iniciarNuevo()" title="Registrar nueva visita médica" class="boton boton-primario">
            <x-lucide-plus />Registrar Visita
        </button>
        @endcan
    </div>

    <div class="hidden bg-fondo p-4 rounded-lg border border-linea">
        <form method="POST" action="{{ route('visitas_medicas.store', $residente) }}" x-ref="form" class="space-y-3">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="etiqueta-campo block mb-1">Médico <span class="text-peligro">*</span></label>
                    <input type="text" name="medico" required x-model="form.medico" class="campo" placeholder="Dr. Apellido">
                </div>
                <div>
                    <label class="etiqueta-campo block mb-1">Especialidad</label>
                    <input type="text" name="especialidad" x-model="form.especialidad" class="campo" placeholder="Cardiología">
                </div>
                <div>
                    <label class="etiqueta-campo block mb-1">Motivo <span class="text-peligro">*</span></label>
                    <input type="text" name="motivo" required x-model="form.motivo" class="campo" placeholder="Revisión rutinaria">
                </div>
                <div>
                    <label class="etiqueta-campo block mb-1">Fecha Visita <span class="text-peligro">*</span></label>
                    <div class="flex space-x-2 items-center">
                        <input type="date" name="fecha_visita_date" required x-model="form.fecha_visita_date" class="flex-1 min-w-0 campo">
                        <input type="time" name="fecha_visita_time" required x-model="form.fecha_visita_time" class="w-28 shrink-0 campo">
                    </div>
                    <input type="hidden" name="fecha_visita" :value="form.fecha_visita_date + 'T' + form.fecha_visita_time">
                </div>
            </div>

            <div class="grupo-acciones grupo-acciones--final">
                <button type="button" @click="cancelar()" title="Descartar cambios" class="boton tono-neutro"><x-lucide-x />Cancelar</button>
                <button type="button" data-swal-submit title="Guardar o actualizar visita" :data-title="editId ? 'Actualizar visita' : 'Guardar visita'" :data-text="editId ? '¿Actualizar esta visita médica?' : '¿Registrar esta visita médica?'" class="boton boton-primario"><x-lucide-save /><span x-show="!editId">Guardar Visita</span><span x-show="editId">Actualizar Visita</span></button>
            </div>
        </form>
    </div>

    @if($residente->visitasMedicas->count() > 0)
        <div class="overflow-x-auto">
            <table class="tabla tabla--media">
                <thead>
                    <tr>
                        <th class="pb-2 font-semibold">Médico</th>
                        <th class="pb-2 font-semibold col-secundaria">Especialidad</th>
                        <th class="pb-2 font-semibold">Motivo</th>
                        <th class="pb-2 font-semibold">Fecha Visita</th>
                        <th class="pb-2 font-semibold">Estado</th>
                        <th class="pb-2 font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-linea">
                    @foreach($residente->visitasMedicas as $visita)
                        @php $esFutura = $visita->fecha_visita->isFuture(); $vBtn = ['id' => $visita->id, 'medico' => $visita->medico, 'especialidad' => $visita->especialidad, 'motivo' => $visita->motivo, 'fecha_visita_date' => $visita->fecha_visita->format('Y-m-d'), 'fecha_visita_time' => $visita->fecha_visita->format('H:i')]; $detalleVisita = $vBtn + ['fecha_visita_fmt' => $visita->fecha_visita->format('d/m/Y H:i')]; @endphp
                        <tr>
                            <td data-label="Médico">
                                <span class="font-medium text-tinta">{{ $visita->medico }}</span>
                            </td>
                            <td class="col-secundaria" data-label="Especialidad">
                                @if($visita->especialidad)
                                    <span class="etiqueta tono-neutro">{{ $visita->especialidad }}</span>
                                @else
                                    <span class="text-tinta-tenue">—</span>
                                @endif
                            </td>
                            <td data-label="Motivo">
                                <span class="text-tinta-suave">{{ $visita->motivo }}</span>
                            </td>
                            <td class="text-tinta-suave text-xs" data-label="Fecha Visita">{{ $visita->fecha_visita->format('d/m/Y H:i') }}</td>
                            <td data-label="Estado">
                                <span class="etiqueta {{ $esFutura ? 'tono-marca' : 'tono-neutro' }}">
                                    {{ $esFutura ? 'Pendiente' : 'Realizada' }}
                                </span>
                            </td>
                            <td class="acciones" data-label="Acciones">
                                <div class="grupo-acciones">
                                    <button type="button" title="Ver detalle de visita" data-json='@json($detalleVisita)' @click="detalle = JSON.parse($event.currentTarget.dataset.json)" class="accion tono-neutro"><x-lucide-eye  /></button>
                                    @can(P::VisitasMedicasGestionar)
                                    @can('update', $visita)
                                    <button type="button" title="Editar visita médica" aria-label="Editar visita médica" class="accion tono-marca" data-json='@json($vBtn)' @click="editar(JSON.parse($event.currentTarget.dataset.json))"><x-lucide-pencil /></button>
                                    @endcan
                                    @can(P::VisitasMedicasAnular)
                                    <x-boton-motivo :action="route('visitas_medicas.anular', $visita)" titulo="Anular visita médica" />
                                    @endcan
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-sm text-tinta-tenue italic text-center py-8">No hay visitas médicas registradas.</p>
    @endif

    {{-- Modal de detalle de visita --}}
    <div x-show="detalle" class="modal-fondo" x-cloak @click="detalle = null">
        <div class="modal" @click.stop>
            <div class="cabecera-pagina">
                <h2 class="titulo-seccion">Detalle de Visita Médica</h2>
                <button @click="detalle = null" title="Cerrar" class="text-tinta-tenue hover:text-tinta-suave text-2xl leading-none cursor-pointer">&times;</button>
            </div>
            <template x-if="detalle">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="etiqueta-campo">Médico</p>
                            <p class="text-sm text-tinta" x-text="detalle.medico"></p>
                        </div>
                        <div>
                            <p class="etiqueta-campo">Especialidad</p>
                            <p class="text-sm text-tinta" x-text="detalle.especialidad"></p>
                        </div>
                        <div>
                            <p class="etiqueta-campo">Fecha de la visita</p>
                            <p class="text-sm text-tinta" x-text="detalle.fecha_visita_fmt"></p>
                        </div>
                    </div>
                    <div>
                        <p class="etiqueta-campo">Motivo</p>
                        <p class="text-sm text-tinta" x-text="detalle.motivo"></p>
                    </div>
                </div>
            </template>
            <div class="mt-6 text-right">
                <button @click="detalle = null" title="Cerrar detalle" class="boton tono-neutro"><x-lucide-x />Cerrar</button>
            </div>
        </div>
    </div>
</div>

<x-registros-anulados :registros="$anulados['visitas']" />
