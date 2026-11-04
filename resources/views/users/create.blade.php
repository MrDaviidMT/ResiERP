<x-app-layout :volver="route('users.index')" volver-texto="Volver a usuarios" estrecho>
    <h1 class="titulo-pagina mb-6">Nuevo Usuario</h1>

    <div class="tarjeta p-6">

        <form action="{{ route('users.store') }}" method="POST" class="space-y-6">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="etiqueta-campo block">Nombre <span class="text-peligro">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="campo mt-1">
                </div>
                <div>
                    <label class="etiqueta-campo block">Email <span class="text-peligro">*</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" required class="campo mt-1">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="etiqueta-campo block">Contraseña <span class="text-peligro">*</span></label>
                    <input type="password" name="password" required class="campo mt-1">
                </div>
                <div>
                    <label class="etiqueta-campo block">Confirmar Contraseña <span class="text-peligro">*</span></label>
                    <input type="password" name="password_confirmation" required class="campo mt-1">
                </div>
            </div>

            <div>
                <label class="etiqueta-campo block">Rol <span class="text-peligro">*</span></label>
                <select name="role" required class="campo mt-1">
                    <option value="">Selecciona un rol...</option>
                                                @foreach($roles as $rol)
                    <option value="{{ $rol->value }}" {{ old('role') === $rol->value ? 'selected' : '' }}>{{ $rol->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grupo-acciones grupo-acciones--final pt-4 border-t border-linea">
                <a href="{{ route('users.index') }}" title="Cancelar" class="boton tono-neutro">
                    <x-lucide-x />Cancelar
                </a>
                <button type="submit" title="Guardar usuario" class="boton boton-primario">
                    <x-lucide-check />Guardar
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
