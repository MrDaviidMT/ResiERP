@use('App\Enums\Permission', 'P')
<x-app-layout :volver="route('familiares.lista')" volver-texto="Volver a familiares" estrecho>
    <h1 class="titulo-pagina mb-6">{{ $familiar->nombre_completo }}</h1>


    <div class="tarjeta p-6 mb-6">
        <div class="cabecera-pagina">
            <h2 class="titulo-seccion">Información de contacto</h2>
            <div class="grupo-acciones">
                @can(P::FamiliaresEditar)
                <a href="{{ route('familiares.edit', $familiar) }}" title="Editar datos del familiar" class="boton tono-marca">
                    <x-lucide-pencil />Editar
                </a>
                @endcan
                @can(P::FamiliaresBorrar)
                <form method="POST" action="{{ route('familiares.destroy', $familiar) }}" id="form-eliminar-familiar-{{ $familiar->id }}">
                    @csrf @method('DELETE')
                    <button type="button" title="Eliminar familiar" data-swal-delete data-title="Eliminar familiar" data-text="¿Estás seguro?" data-form-id="form-eliminar-familiar-{{ $familiar->id }}" class="boton tono-peligro">
                        <x-lucide-trash-2 />Eliminar
                    </button>
                </form>
                @endcan
            </div>
        </div>

        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <dt class="etiqueta-campo">Nombre completo</dt>
                <dd class="text-sm text-tinta mt-1">{{ $familiar->nombre_completo }}</dd>
            </div>
            @can(P::FamiliaresVerContacto)
            <div>
                <dt class="etiqueta-campo">DNI</dt>
                <dd class="text-sm text-tinta mt-1">{{ $familiar->dni ?? '-' }}</dd>
            </div>
            @endcan
            @can(P::FamiliaresVerContacto)
            <div>
                <dt class="etiqueta-campo">Teléfono</dt>
                <dd class="text-sm text-tinta mt-1">
                    <a href="tel:{{ $familiar->telefono }}" title="Llamar al {{ $familiar->telefono }}" class="text-marca-700 hover:text-marca-800">{{ $familiar->telefono }}</a>
                </dd>
            </div>
            @endcan
            @can(P::FamiliaresVerContacto)
            <div>
                <dt class="etiqueta-campo">Email</dt>
                <dd class="text-sm text-tinta mt-1">{{ $familiar->email ?? '-' }}</dd>
            </div>
            @endcan
            <div class="md:col-span-2">
                <dt class="etiqueta-campo">Dirección</dt>
                <dd class="text-sm text-tinta mt-1">{{ $familiar->direccion ?? '-' }}</dd>
            </div>
        </dl>
    </div>

    <div class="tarjeta p-6">
        <h2 class="titulo-seccion mb-4">Residentes vinculados
            <span class="ml-2 text-sm font-normal text-tinta-tenue">({{ $familiar->residentes->count() }})</span>
        </h2>

        @if($familiar->residentes->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($familiar->residentes as $residente)
                    @php $pivot = $residente->pivot; @endphp
                    <div class="border border-linea rounded-lg p-4 hover:shadow-md transition">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <a href="{{ route('residentes.show', $residente) }}" title="Ver perfil del residente" class="font-bold text-marca-700 hover:text-marca-800">
                                    {{ $residente->nombre }} {{ $residente->apellidos }}
                                </a>
                                <span class="block text-xs text-tinta-tenue">
                                    {{ $residente->habitacion?->numero ? "Hab. {$residente->habitacion->numero}" : 'Sin habitación' }}
                                </span>
                            </div>
                            <span class="etiqueta tono-neutro">
                                {{ $pivot->parentesco }}
                            </span>
                        </div>
                        <div class="flex items-center space-x-3 text-xs">
                            @if($pivot->es_emergencia)
                                <span class="text-peligro font-medium flex items-center space-x-1">
                                    <x-lucide-bell-ring class="w-3 h-3" /> <span>Emergencia</span>
                                </span>
                            @endif
                            @if($pivot->autorizado)
                                <span class="text-exito flex items-center space-x-1">
                                    <x-lucide-circle-check-big class="w-3 h-3" /> <span>Autorizado</span>
                                </span>
                            @endif
                        </div>
                        @if($pivot->observaciones)
                            <p class="mt-2 text-xs text-tinta-tenue italic">{{ $pivot->observaciones }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8">
                <x-lucide-building-2 class="w-10 h-10 mx-auto block mb-2 text-tinta-tenue" />
                <p class="text-sm text-tinta-tenue italic">Este familiar no está vinculado a ningún residente.</p>
            </div>
        @endif
    </div>
</x-app-layout>
