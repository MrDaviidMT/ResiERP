@use('App\Enums\Permission', 'P')
<div class="space-y-6" x-data="crudForm({
    defaultForm: { nombre: '', tipo: 'cronico', fecha_diagnostico: '{{ date('Y-m-d') }}', descripcion: '', observaciones: '', tratamiento: '', medico: '', especialidad: '' },
    storeUrl: '{{ route('diagnosticos.store', $residente) }}',
    basePath: '/diagnosticos'
})">
    <div class="flex justify-between items-center">
        <h3 class="text-lg font-bold text-tinta flex items-center">
            <x-lucide-heart class="w-5 h-5 mr-1" /> Diagnósticos Registrados
            <span class="ml-2 text-sm font-normal text-tinta-tenue">({{ $residente->diagnosticos->count() }})</span>
        </h3>
        @can(P::DiagnosticosEditar)
        <button @click="iniciarNuevo()" title="Añadir nuevo diagnóstico" class="boton boton-primario">
            <x-lucide-plus />Nuevo Diagnóstico
        </button>
        @endcan
    </div>

    <div class="hidden bg-fondo p-4 rounded-lg border border-linea">
        <form method="POST" action="{{ route('diagnosticos.store', $residente) }}" x-ref="form" class="space-y-3">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="etiqueta-campo block mb-1">Nombre del Diagnóstico <span class="text-peligro">*</span></label>
                    <input type="text" name="nombre" required placeholder="Ej: Hipertensión arterial" x-model="form.nombre" class="campo">
                </div>
                <div>
                    <label class="etiqueta-campo block mb-1">Tipo</label>
                    <select name="tipo" x-model="form.tipo" class="campo">
                        <option value="cronico">Crónico</option>
                        <option value="agudo">Agudo</option>
                        <option value="quirurgico">Quirúrgico</option>
                        <option value="infeccioso">Infeccioso</option>
                        <option value="degenerativo">Degenerativo</option>
                        <option value="congenito">Congénito</option>
                        <option value="autoinmune">Autoinmune</option>
                        <option value="metabolico">Metabólico</option>
                        <option value="traumatico">Traumático</option>
                        <option value="funcional">Funcional</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
                <div>
                    <label class="etiqueta-campo block mb-1">Fecha Diagnóstico <span class="text-peligro">*</span></label>
                    <input type="date" name="fecha_diagnostico" required x-model="form.fecha_diagnostico" class="campo">
                </div>
                <div>
                    <label class="etiqueta-campo block mb-1">Descripción</label>
                    <textarea name="descripcion" rows="2" x-model="form.descripcion" placeholder="Breve descripción del diagnóstico" class="campo"></textarea>
                </div>
            </div>
            <div>
                <label class="etiqueta-campo block mb-1">Tratamiento</label>
                <textarea name="tratamiento" rows="2" x-model="form.tratamiento" placeholder="Tratamiento indicado..." class="campo"></textarea>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="etiqueta-campo block mb-1">Médico</label>
                    <input type="text" name="medico" x-model="form.medico" placeholder="Dr. Apellido" class="campo">
                </div>
                <div>
                    <label class="etiqueta-campo block mb-1">Especialidad</label>
                    <input type="text" name="especialidad" x-model="form.especialidad" placeholder="Cardiología" class="campo">
                </div>
            </div>
            <div>
                <label class="etiqueta-campo block mb-1">Observaciones</label>
                <textarea name="observaciones" rows="2" x-model="form.observaciones" placeholder="Notas adicionales..." class="campo"></textarea>
            </div>
            <div class="grupo-acciones grupo-acciones--final">
                <button type="button" @click="cancelar()" title="Descartar cambios" class="boton tono-neutro"><x-lucide-x />Cancelar</button>
                <button type="button" data-swal-submit title="Guardar o actualizar diagnóstico" :data-title="editId ? 'Actualizar diagnóstico' : 'Guardar diagnóstico'" :data-text="editId ? '¿Actualizar este diagnóstico?' : '¿Registrar este diagnóstico?'" class="boton boton-primario"><x-lucide-save /><span x-show="!editId">Guardar Diagnóstico</span><span x-show="editId">Actualizar Diagnóstico</span></button>
            </div>
        </form>
    </div>

    @if($residente->diagnosticos->count() > 0)
        <div class="overflow-x-auto">
            <table class="tabla ">
                <thead>
                    <tr>
                        <th class="pb-2 font-semibold">Diagnóstico</th>
                        <th class="pb-2 font-semibold">Tipo</th>
                        <th class="pb-2 font-semibold">Fecha</th>
                        <th class="pb-2 font-semibold">Estado</th>
                        <th class="pb-2 font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-linea">
                    @foreach($residente->diagnosticos->sortByDesc('fecha_diagnostico') as $diag)
                        <tr>
                            <td data-label="Diagnóstico">
                                <span class="font-medium text-tinta">{{ $diag->nombre }}</span>
                                @if($diag->descripcion)
                                    <span class="text-xs text-tinta-tenue block">{{ $diag->descripcion }}</span>
                                @endif
                            </td>
                            <td data-label="Tipo">
                                <span class="etiqueta tono-neutro">{{ ucfirst($diag->tipo) }}</span>
                            </td>
                            <td class="text-tinta-suave text-xs" data-label="Fecha">{{ $diag->fecha_diagnostico->format('d/m/Y') }}</td>
                            <td data-label="Estado">
                                <form method="POST" action="{{ route('diagnosticos.toggle', $diag) }}">
                                    @csrf
                                    <button type="submit" title="Cambiar estado del diagnóstico" class="inline-flex items-center space-x-1 cursor-pointer">
                                        @if($diag->activo)
                                            <span class="w-2 h-2 rounded-full bg-exito"></span>
                                            <span class="text-xs text-exito font-medium">Activo</span>
                                        @else
                                            <span class="w-2 h-2 rounded-full bg-linea-fuerte"></span>
                                            <span class="text-xs text-tinta-tenue font-medium">Inactivo</span>
                                        @endif
                                    </button>
                                </form>
                            </td>
                            <td class="acciones" data-label="Acciones">
                                <div class="grupo-acciones">
@php $diagBtn = ['id' => $diag->id, 'nombre' => $diag->nombre, 'tipo' => $diag->tipo, 'fecha_diagnostico' => $diag->fecha_diagnostico->format('Y-m-d'), 'descripcion' => $diag->descripcion, 'observaciones' => $diag->observaciones, 'tratamiento' => $diag->tratamiento, 'medico' => $diag->medico, 'especialidad' => $diag->especialidad]; @endphp
                                    <button type="button" title="Ver detalle del diagnóstico" data-json='@json($diagBtn + ['activo' => $diag->activo])' @click="detalle = JSON.parse($event.currentTarget.dataset.json)" class="accion tono-neutro">
                                        <x-lucide-eye class="w-4 h-4" />
                                    </button>
                                    @can(P::DiagnosticosEditar)
                                    @can('update', $diag)
                                    <button type="button" title="Editar diagnóstico" aria-label="Editar diagnóstico" class="accion tono-marca" data-json='@json($diagBtn)' @click="editar(JSON.parse($event.currentTarget.dataset.json))"><x-lucide-pencil /></button>
                                    @endcan
                                    @can(P::DiagnosticosAnular)
                                    <x-boton-motivo :action="route('diagnosticos.anular', $diag)" titulo="Anular diagnóstico" />
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
        <p class="text-sm text-tinta-tenue italic text-center py-8">No hay diagnósticos registrados para este residente.</p>
    @endif

    <div x-show="detalle" class="modal-fondo" x-cloak @click="detalle = null">
        <div class="modal" @click.stop>
            <div class="cabecera-pagina">
                <h2 class="titulo-seccion">Detalle del Diagnóstico</h2>
                <button @click="detalle = null" title="Cerrar" class="text-tinta-tenue hover:text-tinta-suave text-2xl leading-none cursor-pointer">&times;</button>
            </div>
            <template x-if="detalle">
                <div class="space-y-4">
                    <div>
                        <label class="etiqueta-campo block">Nombre</label>
                        <p class="text-sm text-tinta" x-text="detalle.nombre"></p>
                    </div>
                    <div x-show="detalle.descripcion">
                        <label class="etiqueta-campo block">Descripción</label>
                        <p class="text-sm text-tinta whitespace-pre-line" x-text="detalle.descripcion"></p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="etiqueta-campo block">Tipo</label>
                            <p class="text-sm font-medium" x-text="detalle.tipo ? detalle.tipo.charAt(0).toUpperCase() + detalle.tipo.slice(1) : ''"></p>
                        </div>
                        <div>
                            <label class="etiqueta-campo block">Fecha Diagnóstico</label>
                            <p class="text-sm text-tinta" x-text="detalle.fecha_diagnostico"></p>
                        </div>
                    </div>
                    <div x-show="detalle.tratamiento">
                        <label class="etiqueta-campo block">Tratamiento</label>
                        <p class="text-sm text-tinta whitespace-pre-line" x-text="detalle.tratamiento"></p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4" x-show="detalle.medico || detalle.especialidad">
                        <div x-show="detalle.medico">
                            <label class="etiqueta-campo block">Médico</label>
                            <p class="text-sm text-tinta" x-text="detalle.medico"></p>
                        </div>
                        <div x-show="detalle.especialidad">
                            <label class="etiqueta-campo block">Especialidad</label>
                            <p class="text-sm text-tinta" x-text="detalle.especialidad"></p>
                        </div>
                    </div>
                    <div x-show="detalle.observaciones">
                        <label class="etiqueta-campo block">Observaciones</label>
                        <p class="text-sm text-tinta whitespace-pre-line" x-text="detalle.observaciones"></p>
                    </div>
                    <div>
                        <label class="etiqueta-campo block">Estado</label>
                        <p class="text-sm font-medium" x-text="detalle.activo ? 'Activo' : 'Inactivo'" :class="detalle.activo ? 'text-exito' : 'text-tinta-tenue'"></p>
                    </div>
                </div>
            </template>
            <div class="mt-6 text-right">
                <button @click="detalle = null" title="Cerrar detalle" class="boton tono-neutro"><x-lucide-x />Cerrar</button>
            </div>
        </div>
    </div>
</div>

<x-registros-anulados :registros="$anulados['diagnosticos']" />
