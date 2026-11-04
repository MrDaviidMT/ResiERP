@use('App\Enums\Permission', 'P')
<x-app-layout :volver="route('residentes.index')" volver-texto="Volver a residentes">
    <x-residente-cabecera :residente="$residente" >
        @can(P::ResidentesExportarHistorial)
        <a href="{{ route('residentes.historial.exportar', $residente) }}" target="_blank" rel="noopener"
           title="Copia de la historia clínica para el derecho de acceso" class="boton tono-neutro">
            <x-lucide-printer />Copia para el residente
        </a>
        @endcan
    </x-residente-cabecera>

    @php
        // La primera pestaña que este puesto puede ver: con una fija, quien no ve diagnósticos entraba a una página vacía
        $primera = collect([
            'diagnosticos' => P::DiagnosticosVer, 'alergias' => P::AlergiasVer, 'constantes' => P::ConstantesVer,
            'cuidados_diarios' => P::CuidadosVer, 'visitas' => P::VisitasMedicasVer,
        ])->filter(fn ($permiso) => auth()->user()->can($permiso))->keys()->first() ?? 'informes';
    @endphp
    <div class="tarjeta">
        <div x-data="{ tab: @js($primera) }">
            <nav class="pestanas px-2" aria-label="Apartados del historial">
                @can(P::DiagnosticosVer)
                <button type="button" class="pestana" @click="tab = 'diagnosticos'" :aria-selected="tab === 'diagnosticos'">
                    <x-lucide-heart />Diagnósticos
                </button>
                @endcan
                @can(P::AlergiasVer)
                <button type="button" class="pestana" @click="tab = 'alergias'" :aria-selected="tab === 'alergias'">
                    <x-lucide-triangle-alert />Alergias
                </button>
                @endcan
                @can(P::ConstantesVer)
                <button type="button" class="pestana" @click="tab = 'constantes'" :aria-selected="tab === 'constantes'">
                    <x-lucide-bar-chart-4 />Constantes Vitales
                </button>
                @endcan
                @can(P::CuidadosVer)
                <button type="button" class="pestana" @click="tab = 'cuidados_diarios'" :aria-selected="tab === 'cuidados_diarios'">
                    <x-lucide-clipboard-check />Cuidados Diarios
                </button>
                @endcan
                @can(P::VisitasMedicasVer)
                <button type="button" class="pestana" @click="tab = 'visitas'" :aria-selected="tab === 'visitas'">
                    <x-lucide-building-2 />Visitas Médicas
                </button>
                @endcan
                @can('viewAny', \App\Models\Informe::class)
                <button type="button" class="pestana" @click="tab = 'informes'" :aria-selected="tab === 'informes'">
                    <x-lucide-file-text />Informes
                </button>
                @endcan
            </nav>

            <div class="p-6">
                @can(P::DiagnosticosVer)
                <div x-show="tab === 'diagnosticos'" x-cloak>
                    @include('residentes.historial._diagnosticos')
                </div>
                @endcan
                @can(P::AlergiasVer)
                <div x-show="tab === 'alergias'" x-cloak>
                    @include('residentes.historial._alergias')
                </div>
                @endcan
                @can(P::ConstantesVer)
                <div x-show="tab === 'constantes'" x-cloak>
                    @include('residentes.historial._constantes')
                </div>
                @endcan
                @can(P::CuidadosVer)
                <div x-show="tab === 'cuidados_diarios'" x-cloak>
                    @include('residentes.historial._cuidados_diarios')
                </div>
                @endcan
                @can(P::VisitasMedicasVer)
                <div x-show="tab === 'visitas'" x-cloak>
                    @include('residentes.historial._visitas')
                </div>
                @endcan
                @can('viewAny', \App\Models\Informe::class)
                <div x-show="tab === 'informes'" x-cloak>
                    @include('residentes.historial._informes')
                </div>
                @endcan
            </div>
        </div>
    </div>
</x-app-layout>
