<x-app-layout :volver="route('users.index')" volver-texto="Volver a usuarios">
    <div class="cabecera-pagina">
        <h1 class="titulo-pagina">
            {{ $user->name }}
            @unless($user->activo)
                <span class="etiqueta tono-peligro align-middle">Desactivado</span>
            @endunless
        </h1>
        <div class="grupo-acciones">
            @can('update', $user)
            <a href="{{ route('users.edit', $user) }}" title="Editar usuario" class="boton tono-marca">
                <x-lucide-pencil />Editar
            </a>
            @endcan
            @if(!$user->hasVerifiedEmail() && auth()->user()->hasVerifiedEmail())
            <form action="{{ route('users.verify', $user) }}" method="POST">
                @csrf
                <button type="submit" title="Verificar manualmente" class="boton tono-exito">
                    <x-lucide-circle-check-big />Verificar
                </button>
            </form>
            @endif
            @can('desactivar', $user)
            @if($user->activo)
            <form action="{{ route('users.desactivar', $user) }}" method="POST" id="form-baja-user-{{ $user->id }}">
                @csrf
                <button type="button" title="Dar de baja al usuario" data-swal-baja data-title="¿Dar de baja?" data-text="No podrá iniciar sesión. Se conservan sus fichajes, informes y auditoría, y podrás reactivarlo." data-form-id="form-baja-user-{{ $user->id }}" class="boton tono-peligro">
                    <x-lucide-user-x />Dar de baja
                </button>
            </form>
            @else
            <form action="{{ route('users.reactivar', $user) }}" method="POST">
                @csrf
                <button type="button" title="Reactivar usuario" data-swal-submit data-title="¿Reactivar usuario?" data-text="Podrá volver a iniciar sesión." class="boton tono-exito">
                    <x-lucide-user-check />Reactivar
                </button>
            </form>
            @endif
            @endcan
        </div>
    </div>

    <div class="tarjeta p-6">
        <dl class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <dt class="etiqueta-campo">Nombre</dt>
            <dd class="text-sm text-tinta mt-1">{{ $user->name }}</dd>
        </div>
        <div>
            <dt class="etiqueta-campo">Email</dt>
            <dd class="text-sm text-tinta mt-1">{{ $user->email }}</dd>
        </div>
        <div>
            <dt class="etiqueta-campo">Rol</dt>
            <dd class="text-sm text-tinta mt-1">
                <x-etiqueta-rol :rol="$user->rol()" />
            </dd>
        </div>
        <div>
            <dt class="etiqueta-campo">Estado de verificación</dt>
            <dd class="text-sm text-tinta mt-1">
                @if($user->hasVerifiedEmail())
                    <span class="text-exito flex items-center gap-1"><x-lucide-circle-check-big class="w-4 h-4" /> Verificado ({{ $user->email_verified_at->format('d/m/Y H:i') }})</span>
                @else
                    <span class="text-peligro flex items-center gap-1"><x-lucide-x class="w-4 h-4" /> No verificado</span>
                @endif
            </dd>
        </div>
        <div>
            <dt class="etiqueta-campo">Fecha de creación</dt>
            <dd class="text-sm text-tinta mt-1">{{ $user->created_at->format('d/m/Y H:i') }}</dd>
        </div>
        <div>
            <dt class="etiqueta-campo">Última actualización</dt>
            <dd class="text-sm text-tinta mt-1">{{ $user->updated_at->format('d/m/Y H:i') }}</dd>
        </div>
    </dl>
</div>
    </div>
</x-app-layout>
