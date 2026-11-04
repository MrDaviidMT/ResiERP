<x-app-layout :volver="route('fichajes.index')" volver-texto="Volver a fichajes" estrecho>
    <h1 class="titulo-pagina mb-6">Nuevo Fichaje</h1>


    <form action="{{ route('fichajes.store') }}" method="POST" class="tarjeta p-6 space-y-4">
        @csrf

        <div>
            <label for="user_id" class="etiqueta-campo block">Usuario <span class="text-peligro">*</span></label>
            <select name="user_id" id="user_id" required
                    class="campo mt-1">
                <option value="">-- Seleccionar usuario --</option>
                @foreach($usuarios as $usuario)
                    <option value="{{ $usuario->id }}" {{ old('user_id') == $usuario->id ? 'selected' : '' }}>{{ $usuario->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="entrada_fecha" class="etiqueta-campo block">Entrada (fecha) <span class="text-peligro">*</span></label>
                <input type="date" name="entrada_fecha" id="entrada_fecha" value="{{ old('entrada_fecha', now()->format('Y-m-d')) }}"
                       class="campo mt-1">
            </div>
            <div>
                <label for="entrada_hora" class="etiqueta-campo block">Entrada (hora) <span class="text-peligro">*</span></label>
                <input type="time" name="entrada_hora" id="entrada_hora" value="{{ old('entrada_hora', now()->format('H:i')) }}"
                       class="campo mt-1">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="salida_fecha" class="etiqueta-campo block">Salida (fecha)</label>
                <input type="date" name="salida_fecha" id="salida_fecha" value="{{ old('salida_fecha') }}"
                       class="campo mt-1">
            </div>
            <div>
                <label for="salida_hora" class="etiqueta-campo block">Salida (hora)</label>
                <input type="time" name="salida_hora" id="salida_hora" value="{{ old('salida_hora') }}"
                       class="campo mt-1">
            </div>
        </div>

        <div>
            <label for="notas" class="etiqueta-campo block">Notas</label>
            <textarea name="notas" id="notas" rows="3"
                      class="campo mt-1">{{ old('notas') }}</textarea>
        </div>

        <div>
            <label for="motivo" class="etiqueta-campo block">Motivo <span class="text-peligro">*</span></label>
            <textarea name="motivo" id="motivo" rows="2" required minlength="10" placeholder="Ej.: no pudo fichar por una avería del terminal"
                      class="campo mt-1">{{ old('motivo') }}</textarea>
            <p class="text-xs text-tinta-suave mt-1">Queda registrado en el historial del fichaje, visible para el trabajador (registro de jornada).</p>
            @error('motivo') <p class="text-peligro text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="grupo-acciones grupo-acciones--final pt-2">
            <a href="{{ route('fichajes.index') }}" title="Cancelar" class="boton tono-neutro">Cancelar</a>
            <button type="submit" title="Guardar fichaje" class="boton boton-primario">
                <x-lucide-save /> Guardar
            </button>
        </div>
    </form>
</x-app-layout>
