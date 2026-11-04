@props(['residente'])
@use('App\Enums\Permission', 'P')
@php
    // Secciones de la ficha: una sola lista para todas sus páginas. Navegar no es actuar, por eso
    // son pestañas y no botones, y las acciones de cada página van en el hueco de la cabecera.
    $usuario = auth()->user();
    $secciones = array_filter([
        ['Resumen', route('residentes.show', $residente), 'residentes.show', 'clipboard-list', true],
        ['Historial', route('residentes.historial', $residente), 'residentes.historial', 'heart', $usuario->canAny([P::DiagnosticosVer, P::AlergiasVer, P::ConstantesVer, P::CuidadosVer, P::VisitasMedicasVer])],
        ['Medicación', route('medicacion.index', $residente->id), 'medicacion.index', 'pill', $usuario->can(P::MedicacionVer)],
        ['Familiares', route('familiares.index', $residente), 'familiares.index', 'users', $usuario->can(P::FamiliaresVer)],
        ['Dieta', route('dieta.index', $residente), 'dieta.index', 'utensils-crossed', $usuario->can(P::DietasVer)],
        ['Visitas', route('visitas.index', $residente), 'visitas.index', 'log-in', $usuario->can(P::VisitasVer)],
    ], fn ($seccion) => $seccion[4]);
@endphp
<header class="mb-6 print-hide">
    <div class="cabecera-pagina">
        <div class="min-w-0">
            <h1 class="titulo-pagina">
                {{ $residente->nombre }} {{ $residente->apellidos }}
                <span @class(['etiqueta align-middle', 'tono-peligro' => $residente->trashed(), 'tono-exito' => ! $residente->trashed()])>{{ $residente->trashed() ? 'Baja' : 'Activo' }}</span>
            </h1>
            <p class="text-sm text-tinta-suave mt-1">
                @can(P::ResidentesVerDocumento)
                    DNI {{ $residente->dni ?? 'no aportado' }} &middot;
                @endcan
                {{ $residente->habitacion ? 'Habitación '.$residente->habitacion->numero : 'Sin habitación' }}
            </p>
        </div>
        @if($slot->isNotEmpty())
            <div class="grupo-acciones">{{ $slot }}</div>
        @endif
    </div>
    <nav class="pestanas" aria-label="Secciones del residente">
        @foreach($secciones as [$texto, $url, $ruta, $icono])
            <a href="{{ $url }}" class="pestana" @if(request()->routeIs($ruta)) aria-current="page" @endif>
                <x-dynamic-component :component="'lucide-'.$icono" />{{ $texto }}
            </a>
        @endforeach
    </nav>
</header>
