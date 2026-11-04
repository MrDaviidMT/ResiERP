@use('App\Enums\Permission', 'P')
<div class="space-y-6" x-data="crudForm({
    defaultForm: { alergeno: '', severidad: 'leve', fecha_deteccion: '', fecha_resolucion: '', reaccion: '', observaciones: '' },
    storeUrl: '{{ route('alergias.store', $residente) }}',
    basePath: '/alergias'
})">
    <div class="flex justify-between items-center">
        <h3 class="text-lg font-bold text-tinta flex items-center">
            <x-lucide-triangle-alert class="w-5 h-5 mr-1" /> Alergias Registradas
            <span class="ml-2 text-sm font-normal text-tinta-tenue">({{ $residente->alergias->count() }})</span>
        </h3>
        @can(P::AlergiasEditar)
        <button @click="iniciarNuevo()" title="Añadir nueva alergia" class="boton boton-primario">
            <x-lucide-plus />Nueva Alergia
        </button>
        @endcan
    </div>

    <div class="hidden bg-fondo p-4 rounded-lg border border-linea">
        <form method="POST" action="{{ route('alergias.store', $residente) }}" x-ref="form" class="space-y-3">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="etiqueta-campo block mb-1">Alérgeno <span class="text-peligro">*</span></label>
                    <input type="text" name="alergeno" required placeholder="Ej: Penicilina, Frutos secos..." x-model="form.alergeno" class="campo">
                </div>
                <div>
                    <label class="etiqueta-campo block mb-1">Severidad</label>
                    <select name="severidad" x-model="form.severidad" class="campo">
                        <option value="leve">Leve</option>
                        <option value="moderada">Moderada</option>
                        <option value="grave">Grave</option>
                    </select>
                </div>
                <div>
                    <label class="etiqueta-campo block mb-1">Fecha Detección</label>
                    <input type="date" name="fecha_deteccion" x-model="form.fecha_deteccion" class="campo">
                </div>
                <div>
                    <label class="etiqueta-campo block mb-1">Fecha Resolución</label>
                    <input type="date" name="fecha_resolucion" x-model="form.fecha_resolucion" class="campo">
                </div>
            </div>
            <div>
                <label class="etiqueta-campo block mb-1">Reacción</label>
                <textarea name="reaccion" rows="2" x-model="form.reaccion" placeholder="Síntomas o reacciones que produce..." class="campo"></textarea>
            </div>
            <div>
                <label class="etiqueta-campo block mb-1">Observaciones</label>
                <textarea name="observaciones" rows="2" x-model="form.observaciones" placeholder="Notas adicionales..." class="campo"></textarea>
            </div>
            <div class="grupo-acciones grupo-acciones--final">
                <button type="button" @click="cancelar()" title="Descartar cambios" class="boton tono-neutro"><x-lucide-x />Cancelar</button>
                <button type="button" data-swal-submit title="Guardar o actualizar alergia" :data-title="editId ? 'Actualizar alergia' : 'Guardar alergia'" :data-text="editId ? '¿Actualizar esta alergia?' : '¿Registrar esta alergia?'" class="boton boton-primario"><x-lucide-save /><span x-show="!editId">Guardar Alergia</span><span x-show="editId">Actualizar Alergia</span></button>
            </div>
        </form>
    </div>

    @if($residente->alergias->count() > 0)
        <div class="overflow-x-auto">
            <table class="tabla ">
                <thead>
                    <tr>
                        <th class="pb-2 font-semibold">Alérgeno</th>
                        <th class="pb-2 font-semibold">Severidad</th>
                        <th class="pb-2 font-semibold">Detección</th>
                        <th class="pb-2 font-semibold">Estado</th>
                        <th class="pb-2 font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-linea">
                    @foreach($residente->alergias->sortByDesc('fecha_deteccion') as $alergia)
                        <tr>
                            <td data-label="Alérgeno">
                                <span class="font-medium text-tinta">{{ $alergia->alergeno }}</span>
                                @if($alergia->reaccion)
                                    <span class="text-xs text-tinta-tenue block">{{ $alergia->reaccion }}</span>
                                @endif
                            </td>
                            <td data-label="Severidad">
                                <span class="etiqueta {{ $alergia->severidad === 'leve' ? 'tono-neutro' : '' }}
                                    {{ $alergia->severidad === 'moderada' ? 'tono-aviso' : '' }}
                                    {{ $alergia->severidad === 'grave' ? 'tono-peligro' : '' }}">
                                    {{ ucfirst($alergia->severidad) }}
                                </span>
                            </td>
                            <td class="text-tinta-suave text-xs" data-label="Detección">{{ $alergia->fecha_deteccion?->format('d/m/Y') ?? '—' }}</td>
                            <td data-label="Estado">
                                <form method="POST" action="{{ route('alergias.toggle', $alergia) }}">
                                    @csrf
                                    <button type="submit" title="Cambiar estado de la alergia" class="inline-flex items-center space-x-1 cursor-pointer">
                                        @if($alergia->activa)
                                            <span class="w-2 h-2 rounded-full bg-peligro"></span>
                                            <span class="text-xs text-peligro font-medium">Activa</span>
                                        @else
                                            <span class="w-2 h-2 rounded-full bg-linea-fuerte"></span>
                                            <span class="text-xs text-tinta-tenue font-medium">Resuelta</span>
                                        @endif
                                    </button>
                                </form>
                            </td>
                            <td class="acciones" data-label="Acciones">
                                <div class="grupo-acciones">
@php $alerBtn = ['id' => $alergia->id, 'alergeno' => $alergia->alergeno, 'severidad' => $alergia->severidad, 'fecha_deteccion' => $alergia->fecha_deteccion?->format('Y-m-d'), 'fecha_resolucion' => $alergia->fecha_resolucion?->format('Y-m-d'), 'reaccion' => $alergia->reaccion, 'observaciones' => $alergia->observaciones]; @endphp
                                    <button type="button" title="Ver detalle de la alergia" data-json='@json($alerBtn)' @click="detalle = JSON.parse($event.currentTarget.dataset.json)" class="accion tono-neutro"><x-lucide-eye  /></button>
                                    @can(P::AlergiasEditar)
                                    @can('update', $alergia)
                                    <button type="button" title="Editar alergia" aria-label="Editar alergia" class="accion tono-marca" data-json='@json($alerBtn)' @click="editar(JSON.parse($event.currentTarget.dataset.json))"><x-lucide-pencil /></button>
                                    @endcan
                                    @can(P::AlergiasAnular)
                                    <x-boton-motivo :action="route('alergias.anular', $alergia)" titulo="Anular alergia" />
                                    @endcan
                                    @endcan
                                </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-sm text-tinta-tenue italic text-center py-8">No hay alergias registradas para este residente.</p>
    @endif

    <div x-show="detalle" class="modal-fondo" x-cloak @click="detalle = null">
        <div class="modal" @click.stop>
            <div class="cabecera-pagina">
                <h2 class="titulo-seccion">Detalle de la Alergia</h2>
                <button @click="detalle = null" title="Cerrar" class="text-tinta-tenue hover:text-tinta-suave text-2xl leading-none cursor-pointer">&times;</button>
            </div>
            <template x-if="detalle">
                <div class="space-y-4">
                    <div>
                        <label class="etiqueta-campo block">Alérgeno</label>
                        <p class="text-sm text-tinta" x-text="detalle.alergeno"></p>
                    </div>
                    <div x-show="detalle.reaccion">
                        <label class="etiqueta-campo block">Reacción</label>
                        <p class="text-sm text-tinta whitespace-pre-line" x-text="detalle.reaccion"></p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="etiqueta-campo block">Severidad</label>
                            <p class="text-sm" x-text="detalle.severidad === 'leve' ? 'Leve' : detalle.severidad === 'moderada' ? 'Moderada' : 'Grave'" :class="detalle.severidad === 'leve' ? 'text-aviso' : detalle.severidad === 'moderada' ? 'text-aviso' : 'text-peligro'" class="font-medium"></p>
                        </div>
                        <div>
                            <label class="etiqueta-campo block">Fecha Detección</label>
                            <p class="text-sm text-tinta" x-text="detalle.fecha_deteccion || '—'"></p>
                        </div>
                    </div>
                    <div x-show="detalle.fecha_resolucion">
                        <label class="etiqueta-campo block">Fecha Resolución</label>
                        <p class="text-sm text-tinta" x-text="detalle.fecha_resolucion"></p>
                    </div>
                    <div x-show="detalle.observaciones">
                        <label class="etiqueta-campo block">Observaciones</label>
                        <p class="text-sm text-tinta whitespace-pre-line" x-text="detalle.observaciones"></p>
                    </div>
                    <div>
                        <label class="etiqueta-campo block">Estado</label>
                        <p class="text-sm font-medium" x-text="detalle.activa !== false ? 'Activa' : 'Resuelta'" :class="detalle.activa !== false ? 'text-peligro' : 'text-tinta-tenue'"></p>
                    </div>
                </div>
            </template>
            <div class="mt-6 text-right">
                <button @click="detalle = null" title="Cerrar detalle" class="boton tono-neutro"><x-lucide-x />Cerrar</button>
            </div>
        </div>
    </div>
</div>

<x-registros-anulados :registros="$anulados['alergias']" />
