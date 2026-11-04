@use('App\Enums\Permission', 'P')
<div class="space-y-6" x-data="crudForm({
    defaultForm: { tension_sistolica: '', tension_diastolica: '', frecuencia_cardiaca: '', temperatura: '', peso: '', saturacion_oxigeno: '', glucosa: '', fecha_registro_date: '{{ now()->format('Y-m-d') }}', fecha_registro_time: '{{ now()->format('H:i') }}', observaciones: '' },
    storeUrl: '{{ route('constantes.store', $residente) }}',
    basePath: '/constantes'
})">
    <div class="flex justify-between items-center">
        <h3 class="text-lg font-bold text-tinta flex items-center">
            <x-lucide-bar-chart-4 class="w-5 h-5 mr-1" /> Constantes Vitales
            <span class="ml-2 text-sm font-normal text-tinta-tenue">({{ $residente->constantes->count() }})</span>
        </h3>
        @can(P::ConstantesRegistrar)
        <button @click="iniciarNuevo()" title="Añadir nuevo registro de constantes" class="boton boton-primario">
            <x-lucide-plus />Nuevo Registro
        </button>
        @endcan
    </div>

    <div class="hidden bg-fondo p-4 rounded-lg border border-linea">
        <form method="POST" action="{{ route('constantes.store', $residente) }}" x-ref="form" class="space-y-3">
            @csrf
            <input type="hidden" name="_method" value="POST">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div>
                    <label class="etiqueta-campo block mb-1">Tensión Sistólica</label>
                    <div class="grupo-acciones">
                        <input type="number" name="tension_sistolica" x-model="form.tension_sistolica" class="campo" placeholder="120">
                        <span class="text-xs text-tinta-tenue">mmHg</span>
                    </div>
                </div>
                <div>
                    <label class="etiqueta-campo block mb-1">Tensión Diastólica</label>
                    <div class="grupo-acciones">
                        <input type="number" name="tension_diastolica" x-model="form.tension_diastolica" class="campo" placeholder="80">
                        <span class="text-xs text-tinta-tenue">mmHg</span>
                    </div>
                </div>
                <div>
                    <label class="etiqueta-campo block mb-1">Frec. Cardíaca</label>
                    <div class="grupo-acciones">
                        <input type="number" name="frecuencia_cardiaca" x-model="form.frecuencia_cardiaca" class="campo" placeholder="72">
                        <span class="text-xs text-tinta-tenue">lpm</span>
                    </div>
                </div>
                <div>
                    <label class="etiqueta-campo block mb-1">Temperatura</label>
                    <div class="grupo-acciones">
                        <input type="number" step="0.1" name="temperatura" x-model="form.temperatura" class="campo" placeholder="36.5">
                        <span class="text-xs text-tinta-tenue">°C</span>
                    </div>
                </div>
                <div>
                    <label class="etiqueta-campo block mb-1">Peso</label>
                    <div class="grupo-acciones">
                        <input type="number" step="0.1" name="peso" x-model="form.peso" class="campo" placeholder="70">
                        <span class="text-xs text-tinta-tenue">kg</span>
                    </div>
                </div>
                <div>
                    <label class="etiqueta-campo block mb-1">Sat. Oxígeno</label>
                    <div class="grupo-acciones">
                        <input type="number" name="saturacion_oxigeno" x-model="form.saturacion_oxigeno" class="campo" placeholder="98">
                        <span class="text-xs text-tinta-tenue">%</span>
                    </div>
                </div>
                <div>
                    <label class="etiqueta-campo block mb-1">Glucosa</label>
                    <div class="grupo-acciones">
                        <input type="number" step="0.1" name="glucosa" x-model="form.glucosa" class="campo" placeholder="100">
                        <span class="text-xs text-tinta-tenue">mg/dL</span>
                    </div>
                </div>
                <div>
                    <label class="etiqueta-campo block mb-1">Fecha / Hora</label>
                    <div class="flex space-x-2 items-center">
                        <input type="date" name="fecha_registro_date" required x-model="form.fecha_registro_date" class="flex-1 min-w-0 campo">
                        <input type="time" name="fecha_registro_time" required x-model="form.fecha_registro_time" class="w-28 shrink-0 campo">
                    </div>
                    <input type="hidden" name="fecha_registro" :value="form.fecha_registro_date + 'T' + form.fecha_registro_time">
                </div>
            </div>
            <div>
                <label class="etiqueta-campo block mb-1">Observaciones</label>
                <textarea name="observaciones" rows="2" x-model="form.observaciones" placeholder="Cualquier observación relevante..." class="campo"></textarea>
            </div>
            <div class="grupo-acciones grupo-acciones--final">
                <button type="button" @click="cancelar()" title="Descartar cambios" class="boton tono-neutro"><x-lucide-x />Cancelar</button>
                <button type="button" data-swal-submit title="Guardar o actualizar constantes" :data-title="editId ? 'Actualizar constantes' : 'Guardar constantes'" :data-text="editId ? '¿Actualizar estas constantes?' : '¿Registrar estas constantes vitales?'" class="boton boton-primario"><x-lucide-save /><span x-show="!editId">Guardar Constantes</span><span x-show="editId">Actualizar Constantes</span></button>
            </div>
        </form>
    </div>

    @if($residente->constantes->count() > 0)
        <div class="overflow-x-auto">
            <table class="tabla tabla--ancha">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th class="cifra">Tensión</th>
                        <th class="cifra">FC</th>
                        <th class="cifra">Temp</th>
                        <th class="cifra">Peso</th>
                        <th class="cifra">SpO₂</th>
                        <th class="cifra">Glucosa</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-linea">
                    @foreach($ultimasConstantes as $c)
                        <tr>
                            <td class="text-xs text-tinta-suave whitespace-nowrap" data-label="Fecha">{{ $c->fecha_registro->format('d/m/Y H:i') }}</td>
                            <td class="cifra text-tinta font-medium" data-label="Tensión">
                                @if($c->tension_sistolica && $c->tension_diastolica)
                                    {{ $c->tension_sistolica }}/{{ $c->tension_diastolica }}
                                @else
                                    <span class="text-tinta-tenue">—</span>
                                @endif
                            </td>
                            <td class="cifra" data-label="FC">{{ $c->frecuencia_cardiaca ?? '—' }}</td>
                            <td class="cifra" data-label="Temp">{{ $c->temperatura ? $c->temperatura.'°C' : '—' }}</td>
                            <td class="cifra" data-label="Peso">{{ $c->peso ? $c->peso.'kg' : '—' }}</td>
                            <td class="cifra" data-label="SpO₂">{{ $c->saturacion_oxigeno ? $c->saturacion_oxigeno.'%' : '—' }}</td>
                            <td class="cifra" data-label="Glucosa">{{ $c->glucosa ?? '—' }}</td>
                            <td class="acciones" data-label="Acciones">
                                <div class="grupo-acciones">
@php $cBtn = ['id' => $c->id, 'tension_sistolica' => $c->tension_sistolica, 'tension_diastolica' => $c->tension_diastolica, 'frecuencia_cardiaca' => $c->frecuencia_cardiaca, 'temperatura' => $c->temperatura, 'peso' => $c->peso, 'saturacion_oxigeno' => $c->saturacion_oxigeno, 'glucosa' => $c->glucosa, 'fecha_registro_date' => $c->fecha_registro->format('Y-m-d'), 'fecha_registro_time' => $c->fecha_registro->format('H:i'), 'observaciones' => $c->observaciones]; @endphp
                                    <button type="button" title="Ver detalle de constantes" data-json='@json($cBtn + ['tension' => ($c->tension_sistolica && $c->tension_diastolica) ? $c->tension_sistolica.'/'.$c->tension_diastolica : null, 'fecha_registro_fmt' => $c->fecha_registro->format('d/m/Y H:i')])' @click="detalle = JSON.parse($event.currentTarget.dataset.json)" class="accion tono-neutro">
                                        <x-lucide-eye class="w-4 h-4" />
                                    </button>
                                    @can(P::ConstantesRegistrar)
                                    @can('update', $c)
                                    <button type="button" title="Editar registro de constantes" aria-label="Editar registro de constantes" class="accion tono-marca" data-json='@json($cBtn)' @click="editar(JSON.parse($event.currentTarget.dataset.json))"><x-lucide-pencil /></button>
                                    @endcan
                                    @can(P::ConstantesAnular)
                                    <x-boton-motivo :action="route('constantes.anular', $c)" titulo="Anular registro de constantes" />
                                    @endcan
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if($residente->constantes->count() > 5)
                <div class="mt-3 text-center">
                    <span class="text-xs text-tinta-tenue">{{ $residente->constantes->count() }} registros en total (mostrando últimos 5)</span>
                </div>
            @endif
        </div>
    @else
        <p class="text-sm text-tinta-tenue italic text-center py-8">No hay constantes vitales registradas.</p>
    @endif

    {{-- Modal de detalle de constantes --}}
    <div x-show="detalle" class="modal-fondo" x-cloak @click="detalle = null">
        <div class="modal" @click.stop>
            <div class="cabecera-pagina">
                <h2 class="titulo-seccion">Detalle de Constantes Vitales</h2>
                <button @click="detalle = null" title="Cerrar" class="text-tinta-tenue hover:text-tinta-suave text-2xl leading-none cursor-pointer">&times;</button>
            </div>
            <template x-if="detalle">
                <div class="space-y-4">
                    <div>
                        <p class="etiqueta-campo">Fecha de registro</p>
                        <p class="text-sm text-tinta" x-text="detalle.fecha_registro_fmt"></p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="etiqueta-campo">Peso</p>
                            <p class="text-sm text-tinta"><span x-text="detalle.peso ?? '—'"></span> kg</p>
                        </div>
                        <div>
                            <p class="etiqueta-campo">Temperatura</p>
                            <p class="text-sm text-tinta"><span x-text="detalle.temperatura ?? '—'"></span> °C</p>
                        </div>
                        <div>
                            <p class="etiqueta-campo">Tensión arterial</p>
                            <p class="text-sm text-tinta" x-text="detalle.tension ?? '—'"></p>
                        </div>
                        <div>
                            <p class="etiqueta-campo">Saturación O₂</p>
                            <p class="text-sm text-tinta"><span x-text="detalle.saturacion_oxigeno ?? '—'"></span> %</p>
                        </div>
                        <div>
                            <p class="etiqueta-campo">Glucosa</p>
                            <p class="text-sm text-tinta"><span x-text="detalle.glucosa ?? '—'"></span> mg/dL</p>
                        </div>
                        <div>
                            <p class="etiqueta-campo">Frecuencia cardíaca</p>
                            <p class="text-sm text-tinta"><span x-text="detalle.frecuencia_cardiaca ?? '—'"></span> lpm</p>
                        </div>
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

<x-registros-anulados :registros="$anulados['constantes']" />
