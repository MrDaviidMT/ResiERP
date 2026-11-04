@use('App\Enums\Permission', 'P')
<x-app-layout :volver="route('residentes.index')" volver-texto="Volver a residentes">
    <x-residente-cabecera :residente="$residente">
        @can(P::ResidentesEditar)
        <a href="{{ route('residentes.edit', $residente->id) }}" title="Editar datos del residente" class="boton tono-marca">
            <x-lucide-pencil />Editar
        </a>
        @endcan
        @unless($residente->trashed())
            @can(P::ResidentesBaja)
            <form action="{{ route('residentes.destroy', $residente->id) }}" method="POST" id="form-baja-{{ $residente->id }}">
                @csrf
                @method('DELETE')
                <button type="button" title="Dar de baja al residente" data-swal-baja data-title="¿Confirmar baja?" data-text="Se liberará su cama en el sistema. Podrás reincorporarlo desde el filtro *Bajas / Histórico*." data-form-id="form-baja-{{ $residente->id }}" class="boton tono-peligro">
                    <x-lucide-user-x />Dar de baja
                </button>
            </form>
            @endcan
        @endunless
        <button type="button" data-imprimir title="Imprimir perfil" class="boton tono-neutro">
            <x-lucide-printer />Imprimir
        </button>
    </x-residente-cabecera>

    <div id="perfil-pdf" class="space-y-6">
        <section class="tarjeta p-6">
            <h2 class="titulo-seccion mb-4">Datos personales</h2>
            <dl class="datos">
                <div><dt>Nombre</dt><dd>{{ $residente->nombre }} {{ $residente->apellidos }}</dd></div>
                @can(P::ResidentesVerDocumento)
                <div><dt>DNI</dt><dd>{{ $residente->dni ?? '—' }}</dd></div>
                @endcan
                <div><dt>Edad</dt><dd>{{ $residente->nacimiento->age }} años</dd></div>
                <div><dt>Nacimiento</dt><dd>{{ $residente->nacimiento->format('d/m/Y') }}</dd></div>
                <div><dt>Ingreso</dt><dd>{{ $residente->ingreso->format('d/m/Y') }}</dd></div>
                @can(P::ResidentesVerSalud)
                <div><dt>Grupo sanguíneo</dt><dd>{{ $residente->grupo_sanguineo ?? '—' }}</dd></div>
                @endcan
                <div><dt>Habitación</dt><dd>{{ $residente->habitacion ? 'Nº '.$residente->habitacion->numero : '—' }}</dd></div>
                @can(P::ResidentesVerSalud)
                <div><dt>Dependencia</dt><dd>{{ $residente->dependencia ? ucfirst(str_replace('_', ' ', $residente->dependencia)) : '—' }}</dd></div>
                @endcan
            </dl>
        </section>

        @php
            $diagnosticosActivos = $residente->diagnosticos->where('activo', true);
            $alergiasActivas = $residente->alergias->where('activa', true);
        @endphp
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            @can(P::MedicacionVer)
            <section class="tarjeta p-6">
                <a href="{{ route('medicacion.index', $residente->id) }}" class="titulo-seccion flex items-center gap-2 mb-3 hover:text-marca-700">
                    <x-lucide-pill class="w-5 h-5" /> Medicación
                </a>
                @if($residente->medicaciones->isNotEmpty())
                    <ul class="text-sm space-y-2">
                        @foreach($residente->medicaciones as $med)
                            <li>
                                <span class="font-medium">{{ $med->medicamento }}</span>
                                <span class="text-tinta-suave">({{ $med->dosis }})</span>
                                <span class="block text-xs text-tinta-suave">{{ $med->tomas() ?: 'Sin horario de toma' }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-tinta-suave italic">Sin tratamientos activos</p>
                @endif
            </section>
            @endcan

            @can(P::DiagnosticosVer)
            <section class="tarjeta p-6">
                <a href="{{ route('residentes.historial', $residente) }}" class="titulo-seccion flex items-center gap-2 mb-3 hover:text-marca-700">
                    <x-lucide-heart class="w-5 h-5" /> Historial médico
                </a>
                @if($diagnosticosActivos->isNotEmpty())
                    <ul class="text-sm space-y-1">
                        @foreach($diagnosticosActivos->take(3) as $dx)
                            <li><span class="font-medium">{{ $dx->nombre }}</span> <span class="text-xs text-tinta-suave">({{ $dx->tipo }})</span></li>
                        @endforeach
                        @if($diagnosticosActivos->count() > 3)
                            <li class="text-xs text-tinta-suave">+{{ $diagnosticosActivos->count() - 3 }} más</li>
                        @endif
                    </ul>
                @else
                    <p class="text-sm text-tinta-suave italic">Sin diagnósticos activos</p>
                @endif
                @if($alergiasActivas->isNotEmpty())
                    <div class="mt-3 pt-3 border-t border-linea">
                        <p class="etiqueta-campo mb-1">Alergias</p>
                        <div class="flex flex-wrap gap-1">
                            @foreach($alergiasActivas as $a)
                                <span class="etiqueta tono-peligro">{{ $a->alergeno }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>
            @endcan

            @can(P::DietasVer)
            <section class="tarjeta p-6">
                <a href="{{ route('dieta.index', $residente) }}" class="titulo-seccion flex items-center gap-2 mb-3 hover:text-marca-700">
                    <x-lucide-utensils-crossed class="w-5 h-5" /> Dieta
                </a>
                @if($residente->dieta)
                    <p class="text-sm"><span class="font-medium">Tipo:</span> {{ str_replace('_', ' ', $residente->dieta->tipo) }}</p>
                    @if($residente->dieta->alergias_alimentarias)
                        <p class="text-sm text-peligro mt-1"><span class="font-medium">Alergias alimentarias:</span> {{ $residente->dieta->alergias_alimentarias }}</p>
                    @endif
                @else
                    <p class="text-sm text-tinta-suave italic">Sin plan de dieta</p>
                @endif
            </section>
            @endcan

            @can(P::FamiliaresVer)
            <section class="tarjeta p-6">
                <a href="{{ route('familiares.index', $residente) }}" class="titulo-seccion flex items-center gap-2 mb-3 hover:text-marca-700">
                    <x-lucide-users class="w-5 h-5" /> Familiares
                </a>
                @if($residente->familiares->isNotEmpty())
                    <ul class="text-sm space-y-1">
                        @foreach($residente->familiares->take(3) as $f)
                            <li><span class="font-medium">{{ $f->nombre_completo }}</span> <span class="text-xs text-tinta-suave">({{ $f->pivot->parentesco }})</span></li>
                        @endforeach
                        @if($residente->familiares->count() > 3)
                            <li class="text-xs text-tinta-suave">+{{ $residente->familiares->count() - 3 }} más</li>
                        @endif
                    </ul>
                @else
                    <p class="text-sm text-tinta-suave italic">Sin familiares registrados</p>
                @endif
            </section>
            @endcan

            @can(P::VisitasMedicasVer)
            <section class="tarjeta p-6">
                <a href="{{ route('residentes.historial', $residente) }}" class="titulo-seccion flex items-center gap-2 mb-3 hover:text-marca-700">
                    <x-lucide-stethoscope class="w-5 h-5" /> Próxima visita médica
                </a>
                @if($proximaVisita)
                    <p class="text-sm"><span class="font-medium">{{ $proximaVisita->medico }}</span> @if($proximaVisita->especialidad)({{ $proximaVisita->especialidad }})@endif</p>
                    <p class="text-xs text-tinta-suave">{{ $proximaVisita->fecha_visita->format('d/m/Y H:i') }}</p>
                    @if($proximaVisita->motivo)
                        <p class="text-xs text-tinta-suave mt-1">{{ $proximaVisita->motivo }}</p>
                    @endif
                @else
                    <p class="text-sm text-tinta-suave italic">No tiene próximas visitas</p>
                @endif
            </section>
            @endcan

        </div>
    </div>
</x-app-layout>
