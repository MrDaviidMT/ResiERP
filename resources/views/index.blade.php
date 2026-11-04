@use('App\Enums\Permission', 'P')
<x-app-layout>
    <h1 class="sr-only">Panel de inicio</h1>

    <div class="space-y-8">


        @php $fichajeActivo = auth()->user()->fichajes()->whereNull('salida')->first(); @endphp
        <div class="tarjeta p-4 mb-6">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div class="flex items-center gap-3">
                    <x-lucide-clock class="w-5 h-5 shrink-0 text-marca-600" />
                    <div>
                        <span class="etiqueta-campo">Mi Fichaje</span>
                        @if($fichajeActivo)
                            <p class="text-sm text-tinta">Entrada: <span class="font-semibold">{{ $fichajeActivo->entrada->format('H:i') }}</span> &middot; Llevas {{ $fichajeActivo->entrada->diffForHumans(now(), ['parts' => 2, 'short' => false, 'syntax' => Carbon\CarbonInterface::DIFF_ABSOLUTE]) }}</p>
                        @else
                            <p class="text-sm text-tinta-suave italic">No has fichado entrada hoy.</p>
                        @endif
                    </div>
                </div>
                @if($fichajeActivo)
                    <form action="{{ route('fichajes.salida', $fichajeActivo) }}" method="POST">
                        @csrf
                        <button type="submit" title="Registrar salida" class="boton tono-aviso">
                            <x-lucide-log-out />Salida
                        </button>
                    </form>
                @else
                    <form action="{{ route('fichajes.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ auth()->id() }}">
                        <button type="submit" title="Registrar entrada" class="boton tono-exito">
                            <x-lucide-log-in />Entrada
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div>
            <h2 class="text-sm font-bold text-tinta-suave uppercase tracking-wider mb-4 flex items-center gap-2">Resumen General
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">

                <div class="tarjeta p-4">
                    <div class="flex items-center justify-between">
                        <span class="etiqueta-campo">Ocupación</span>
                        <x-lucide-bed-double class="w-4 h-4 text-marca-600" />
                    </div>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-tinta">{{ $porcentajeOcupacion }}%</span>
                        <span class="text-xs text-tinta-suave">{{ $residentesActivos }}/{{ $totalCamas }}</span>
                    </div>
                    {{-- El dato va en una propiedad personalizada; el aspecto, en .barra-progreso --}}
                    <div class="barra-progreso mt-2" style="--valor: {{ min($porcentajeOcupacion, 100) }}%"
                         role="progressbar" aria-valuenow="{{ $porcentajeOcupacion }}" aria-valuemin="0" aria-valuemax="100"
                         aria-label="Ocupación de camas"></div>
                </div>

                <div class="tarjeta p-4">
                    <div class="flex items-center justify-between">
                        <span class="etiqueta-campo">Activos</span>
                        <x-lucide-users class="w-4 h-4 text-marca-600" />
                    </div>
                    <div class="mt-2">
                        <span class="text-2xl font-bold text-tinta">{{ $residentesActivos }}</span>
                    </div>
                    <p class="mt-1 text-xs text-tinta-tenue">Residentes en planta</p>
                </div>

                <div class="tarjeta p-4">
                    <div class="flex items-center justify-between">
                        <span class="etiqueta-campo">Incidencias</span>
                        <x-lucide-triangle-alert class="w-4 h-4 text-aviso" />
                    </div>
                    <div class="mt-2">
                        <span class="text-2xl font-bold {{ $incidenciasActivas > 0 ? 'text-aviso' : 'text-tinta' }}">{{ $incidenciasActivas }}</span>
                    </div>
                    <p class="mt-1 text-xs text-tinta-tenue">Pendientes o en proceso</p>
                </div>

                <div class="tarjeta p-4">
                    <div class="flex items-center justify-between">
                        <span class="etiqueta-campo">Urgentes</span>
                        <x-lucide-alert-triangle class="w-4 h-4 text-peligro" />
                    </div>
                    <div class="mt-2">
                        <span class="text-2xl font-bold {{ $incidenciasUrgentes > 0 ? 'text-peligro' : 'text-tinta' }}">{{ $incidenciasUrgentes }}</span>
                    </div>
                    <p class="mt-1 text-xs text-tinta-tenue">Atención inmediata</p>
                </div>

                <div class="tarjeta p-4">
                    <div class="flex items-center justify-between">
                        <span class="etiqueta-campo">Visitas Hoy</span>
                        <x-lucide-calendar-check class="w-4 h-4 text-marca-600" />
                    </div>
                    <div class="mt-2">
                        <span class="text-2xl font-bold text-tinta">{{ $visitasHoy }}</span>
                    </div>
                    <p class="mt-1 text-xs text-tinta-tenue">Programadas</p>
                </div>

            </div>
        </div>

        <div>
            <h2 class="text-sm font-bold text-tinta-suave uppercase tracking-wider mb-4 flex items-center gap-2">Infraestructura
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                <div class="tarjeta p-4 border-l-4 border-l-green-500">
                    <div class="flex items-center justify-between">
                        <span class="etiqueta-campo">Libres</span>
                        <x-lucide-door-open class="w-4 h-4 text-exito" />
                    </div>
                    <div class="mt-2">
                        <span class="text-2xl font-bold text-exito">{{ $habitacionesLibres }}</span>
                    </div>
                    <p class="mt-1 text-xs text-tinta-tenue">Disponibles para nuevos ingresos</p>
                </div>

                <div class="tarjeta p-4 border-l-4 border-l-marca-600">
                    <div class="flex items-center justify-between">
                        <span class="etiqueta-campo">Ocupadas</span>
                        <x-lucide-door-closed class="w-4 h-4 text-marca-600" />
                    </div>
                    <div class="mt-2">
                        <span class="text-2xl font-bold text-marca-700">{{ $habitacionesOcupadas }}</span>
                    </div>
                    <p class="mt-1 text-xs text-tinta-tenue">Con residentes asignados</p>
                </div>

                <div class="tarjeta p-4 border-l-4 border-l-amber-500">
                    <div class="flex items-center justify-between">
                        <span class="etiqueta-campo">Mantenimiento</span>
                        <x-lucide-wrench class="w-4 h-4 text-aviso" />
                    </div>
                    <div class="mt-2">
                        <span class="text-2xl font-bold text-aviso">{{ $habitacionesMantenimiento }}</span>
                    </div>
                    <p class="mt-1 text-xs text-tinta-tenue">Habitaciones bloqueadas</p>
                </div>

            </div>
        </div>

        <div>
            <h2 class="text-sm font-bold text-tinta-suave uppercase tracking-wider mb-4 flex items-center gap-2">Alertas y Pendientes
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                <div class="tarjeta p-4 border-l-4 border-l-red-500">
                    <div class="flex items-center justify-between">
                        <span class="etiqueta-campo">Alertas Médicas</span>
                        <x-lucide-stethoscope class="w-4 h-4 text-peligro" />
                    </div>
                    <div class="mt-2">
                        <span class="text-2xl font-bold text-peligro">{{ $conAlergias }}</span>
                    </div>
                    <p class="mt-1 text-xs text-tinta-tenue">Residentes con alergias activas</p>
                </div>

                <div class="tarjeta p-4 border-l-4 border-l-red-400">
                    <div class="flex items-center justify-between">
                        <span class="etiqueta-campo">Sin Habitación</span>
                        <x-lucide-user-x class="w-4 h-4 text-peligro" />
                    </div>
                    <div class="mt-2">
                        <span class="text-2xl font-bold text-peligro">{{ $sinHabitacion }}</span>
                    </div>
                    <p class="mt-1 text-xs text-tinta-tenue">Residentes sin cama asignada</p>
                </div>

                <div class="tarjeta p-4 border-l-4 border-l-yellow-500">
                    <div class="flex items-center justify-between">
                        <span class="etiqueta-campo">Familiares sin Vincular</span>
                        <x-lucide-user class="w-4 h-4 text-aviso" />
                    </div>
                    <div class="mt-2">
                        <span class="text-2xl font-bold text-aviso">{{ $familiaresSinVinculo }}</span>
                    </div>
                    <p class="mt-1 text-xs text-tinta-tenue">Sin residente asociado</p>
                </div>

            </div>
        </div>

        <div>
            <h2 class="text-sm font-bold text-tinta-suave uppercase tracking-wider mb-4 flex items-center gap-2">Hoy en ResiERP
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                <div class="tarjeta p-4">
                    <h3 class="etiqueta-campo mb-3">Datos del Día</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-fondo rounded-lg p-3">
                            <span class="text-xs text-tinta-suave block">Nuevos Ingresos</span>
                            <span class="text-lg font-bold text-exito">{{ $nuevosIngresos }}</span>
                        </div>
                        <div class="bg-fondo rounded-lg p-3">
                            <span class="text-xs text-tinta-suave block">Bajas este Mes</span>
                            <span class="text-lg font-bold text-tinta">{{ $bajasMes }}</span>
                        </div>
                        <div class="bg-fondo rounded-lg p-3">
                            <span class="text-xs text-tinta-suave block">Cumpleaños del Mes</span>
                            <span class="text-lg font-bold text-tinta-suave">{{ $cumpleanosMes }}</span>
                        </div>
                        <div class="bg-fondo rounded-lg p-3">
                            <span class="text-xs text-tinta-suave block">Logs de Hoy</span>
                            <span class="text-lg font-bold text-tinta">{{ $logsHoy }}</span>
                        </div>
                    </div>
                </div>

                @can(P::MenusVer)
                <div class="tarjeta p-4">
                    <h3 class="etiqueta-campo mb-3 flex items-center gap-2">
                        <x-lucide-utensils-crossed class="w-4 h-4 text-aviso" /> Menú del Día
                    </h3>
                    @if($menuHoy)
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <x-lucide-sun class="w-4 h-4 text-aviso shrink-0" />
                                <span class="text-sm font-medium text-tinta w-20">Desayuno:</span>
                                <span class="text-sm text-tinta-suave">{{ $menuHoy->desayuno }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-lucide-sun class="w-4 h-4 text-aviso shrink-0" />
                                <span class="text-sm font-medium text-tinta w-20">Comida:</span>
                                <span class="text-sm text-tinta-suave">{{ $menuHoy->comida }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-lucide-sun class="w-4 h-4 text-aviso shrink-0" />
                                <span class="text-sm font-medium text-tinta w-20">Merienda:</span>
                                <span class="text-sm text-tinta-suave">{{ $menuHoy->merienda }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-lucide-moon class="w-4 h-4 text-marca-400 shrink-0" />
                                <span class="text-sm font-medium text-tinta w-20">Cena:</span>
                                <span class="text-sm text-tinta-suave">{{ $menuHoy->cena }}</span>
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-tinta-tenue italic">Menú no configurado para hoy.</p>
                    @endif
                </div>
                @endcan

            </div>
        </div>

    </div>
</x-app-layout>