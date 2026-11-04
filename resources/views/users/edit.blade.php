<x-app-layout :volver="route('users.show', $user)" volver-texto="Volver al usuario" estrecho>
    <h1 class="titulo-pagina mb-6">Editar Usuario: {{ $user->name }}</h1>

    <div class="tarjeta p-6">

        <form action="{{ route('users.update', $user) }}" method="POST" class="space-y-6">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="etiqueta-campo block">Nombre <span class="text-peligro">*</span></label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="campo mt-1">
                </div>
                <div>
                    <label class="etiqueta-campo block">Email <span class="text-peligro">*</span></label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="campo mt-1">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="etiqueta-campo block">Nueva Contraseña <span class="text-tinta-tenue">(dejar vacío para mantener)</span></label>
                    <input type="password" name="password" class="campo mt-1">
                </div>
                <div>
                    <label class="etiqueta-campo block">Confirmar Contraseña</label>
                    <input type="password" name="password_confirmation" class="campo mt-1">
                </div>
            </div>

            <div>
                <label class="etiqueta-campo block">Rol <span class="text-peligro">*</span></label>
                @if($user->is(auth()->user()))
                <p class="mt-1 p-2 text-sm text-tinta-suave bg-fondo rounded-md border border-linea">{{ $user->rol()?->label() }} <span class="text-xs text-tinta-tenue">(no puedes cambiar tu propio rol)</span></p>
                @else
                <select name="role" required class="campo mt-1">
                                                @foreach($roles as $rol)
                    <option value="{{ $rol->value }}" {{ old('role', $user->rol()?->value) === $rol->value ? 'selected' : '' }}>{{ $rol->label() }}</option>
                    @endforeach
                </select>
                @endif
            </div>

            <div class="grupo-acciones grupo-acciones--final pt-4 border-t border-linea">
                <a href="{{ route('users.index') }}" title="Cancelar" class="boton tono-neutro">
                    <x-lucide-x />Cancelar
                </a>
                <button type="submit" title="Guardar cambios" class="boton boton-primario">
                    <x-lucide-check />Guardar
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
