@use('App\Enums\Permission', 'P')
<div class="space-y-6" x-data="crudForm({
    defaultForm: { fecha: '{{ date('Y-m-d') }}', comio_bien: '1', se_baño: '1', observaciones: '' },
    storeUrl: '{{ route('cuidados_diarios.store', $residente) }}',
    basePath: '/cuidados-diarios'
})">
    <div class="flex justify-between items-center">
        <h3 class="text-lg font-bold text-tinta flex items-center">
            <x-lucide-clipboard-check class="w-5 h-5 mr-1" /> Cuidados Diarios
            <span class="ml-2 text-sm font-normal text-tinta-tenue">({{ $residente->cuidadosDiarios->count() }})</span>
        </h3>
        @can(P::CuidadosRegistrar)
        <button @click="iniciarNuevo()" title="Registrar cuidado diario" class="boton boton-primario">
            <x-lucide-plus />Nuevo Cuidado
        </button>
        @endcan
    </div>

    <div class="hidden bg-fondo p-4 rounded-lg border border-linea">
        <form method="POST" action="{{ route('cuidados_diarios.store', $residente) }}" x-ref="form" class="space-y-3">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="etiqueta-campo block mb-1">Fecha <span class="text-peligro">*</span></label>
                    <input type="date" name="fecha" x-model="form.fecha" required class="campo">
                </div>
                <div>
                    <label class="etiqueta-campo block mb-1">Comió bien</label>
                    <select name="comio_bien" x-model="form.comio_bien" class="campo">
                        <option value="1">Sí</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <div>
                    <label class="etiqueta-campo block mb-1">Se bañó</label>
                    <select name="se_baño" x-model="form.se_baño" class="campo">
                        <option value="1">Sí</option>
                        <option value="0">No</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="etiqueta-campo block mb-1">Observaciones</label>
                <textarea name="observaciones" x-model="form.observaciones" rows="2" class="campo"></textarea>
            </div>
            <div class="grupo-acciones grupo-acciones--final">
                <button @click="cancelar()" type="button" class="boton tono-neutro">Cancelar</button>
                <button type="submit" class="boton boton-primario"><x-lucide-save />Guardar</button>
            </div>
        </form>
    </div>

    @if($residente->cuidadosDiarios->count() > 0)
        <div class="overflow-x-auto">
            <table class="tabla ">
                <thead>
                    <tr>
                        <th class="pb-2 font-semibold">Fecha</th>
                        <th class="pb-2 font-semibold">Comió bien</th>
                        <th class="pb-2 font-semibold">Se bañó</th>
                        <th class="pb-2 font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-linea">
                    @foreach($residente->cuidadosDiarios as $cuidado)
                    @php
                        $cBtn = ['id' => $cuidado->id, 'fecha' => $cuidado->fecha->format('Y-m-d'), 'comio_bien' => $cuidado->comio_bien ? '1' : '0', 'se_baño' => $cuidado->se_baño ? '1' : '0', 'observaciones' => $cuidado->observaciones];
                        $detalleData = $cBtn + ['fecha_fmt' => $cuidado->fecha->format('d/m/Y'), 'comio_bien_label' => $cuidado->comio_bien ? 'Sí' : 'No', 'se_baño_label' => $cuidado->se_baño ? 'Sí' : 'No', 'user_name' => $cuidado->autor?->name];
                    @endphp
                    <tr>
                        <td data-label="Fecha">
                            <span class="font-medium text-tinta">{{ $cuidado->fecha->format('d/m/Y') }}</span>
                        </td>
                        <td data-label="Comió bien">
                            @if($cuidado->comio_bien)
                                <span class="etiqueta tono-exito">Sí</span>
                            @else
                                <span class="etiqueta tono-peligro">No</span>
                            @endif
                        </td>
                        <td data-label="Se bañó">
                            @if($cuidado->se_baño)
                                <span class="etiqueta tono-marca">Sí</span>
                            @else
                                <span class="etiqueta tono-aviso">No</span>
                            @endif
                        </td>
                        <td class="acciones" data-label="Acciones">
                            <div class="grupo-acciones">
                                <button type="button" title="Ver detalle" data-json='@json($detalleData)' @click="detalle = JSON.parse($event.currentTarget.dataset.json)" class="accion tono-neutro"><x-lucide-eye  /></button>
                                @can(P::CuidadosRegistrar)
                                @can('update', $cuidado)
                                <button type="button" title="Editar registro" aria-label="Editar registro" class="accion tono-marca" data-json='@json($cBtn)' @click="editar(JSON.parse($event.currentTarget.dataset.json))"><x-lucide-pencil /></button>
                                @endcan
                                @can(P::CuidadosAnular)
                                <x-boton-motivo :action="route('cuidados_diarios.anular', $cuidado)" titulo="Anular cuidado diario" />
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
        <p class="text-sm text-tinta-tenue italic text-center py-8">No hay registros de cuidados diarios.</p>
    @endif

    {{-- Modal de detalle de cuidado diario --}}
    <div x-show="detalle" class="modal-fondo" x-cloak @click="detalle = null">
        <div class="modal" @click.stop>
            <div class="cabecera-pagina">
                <h2 class="titulo-seccion">Detalle de Cuidado Diario</h2>
                <button @click="detalle = null" title="Cerrar" class="text-tinta-tenue hover:text-tinta-suave text-2xl leading-none cursor-pointer">&times;</button>
            </div>
            <template x-if="detalle">
                <div class="space-y-4">
                    <div>
                        <p class="etiqueta-campo">Fecha</p>
                        <p class="text-sm text-tinta" x-text="detalle.fecha_fmt"></p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="etiqueta-campo">Comió bien</p>
                            <span class="etiqueta " :class="detalle.comio_bien == '1' || detalle.comio_bien == true ? 'tono-exito' : 'tono-peligro'" x-text="detalle.comio_bien_label"></span>
                        </div>
                        <div>
                            <p class="etiqueta-campo">Se bañó</p>
                            <span class="etiqueta " :class="detalle.se_baño == '1' || detalle.se_baño == true ? 'tono-marca' : 'tono-aviso'" x-text="detalle.se_baño_label"></span>
                        </div>
                    </div>
                    <div>
                        <p class="etiqueta-campo">Registrado por</p>
                        <p class="text-sm text-tinta" x-text="detalle.user_name ?? '—'"></p>
                    </div>
                    <div x-show="detalle.observaciones">
                        <p class="etiqueta-campo">Observaciones</p>
                        <p class="text-sm text-tinta" x-text="detalle.observaciones"></p>
                    </div>
                </div>
            </template>
            <div class="mt-6 text-right">
                <button @click="detalle = null" title="Cerrar detalle" class="boton tono-neutro"><x-lucide-x />Cerrar</button>
            </div>
        </div>
    </div>
</div>

<x-registros-anulados :registros="$anulados['cuidados']" />
