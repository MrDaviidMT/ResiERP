<x-app-layout :volver="route('fichajes.show', $fichaje)" volver-texto="Volver al fichaje" estrecho>
    <h1 class="titulo-pagina mb-6">Editar fichaje de {{ $fichaje->user->name }}</h1>


    <form action="{{ route('fichajes.update', $fichaje) }}" method="POST" class="tarjeta p-6 space-y-4">
        @csrf @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="entrada_fecha" class="etiqueta-campo block">Entrada (fecha)</label>
                <input type="date" name="entrada_fecha" id="entrada_fecha" value="{{ old('entrada_fecha', $fichaje->entrada->format('Y-m-d')) }}"
                       class="campo mt-1 @error('entrada') border-peligro @enderror">
            </div>
            <div>
                <label for="entrada_hora" class="etiqueta-campo block">Entrada (hora)</label>
                <input type="time" name="entrada_hora" id="entrada_hora" value="{{ old('entrada_hora', $fichaje->entrada->format('H:i')) }}"
                       class="campo mt-1 @error('entrada') border-peligro @enderror">
            </div>
            @error('entrada') <p class="text-peligro text-xs mt-1 col-span-2">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="salida_fecha" class="etiqueta-campo block">Salida (fecha)</label>
                <input type="date" name="salida_fecha" id="salida_fecha" value="{{ old('salida_fecha', $fichaje->salida?->format('Y-m-d')) }}"
                       class="campo mt-1 @error('salida') border-peligro @enderror">
            </div>
            <div>
                <label for="salida_hora" class="etiqueta-campo block">Salida (hora)</label>
                <input type="time" name="salida_hora" id="salida_hora" value="{{ old('salida_hora', $fichaje->salida?->format('H:i')) }}"
                       class="campo mt-1 @error('salida') border-peligro @enderror">
            </div>
            @error('salida') <p class="text-peligro text-xs mt-1 col-span-2">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="notas" class="etiqueta-campo block">Notas</label>
            <textarea name="notas" id="notas" rows="3"
                      class="campo mt-1 @error('notas') border-peligro @enderror">{{ old('notas', $fichaje->notas) }}</textarea>
            @error('notas') <p class="text-peligro text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="motivo" class="etiqueta-campo block">Motivo <span class="text-peligro">*</span></label>
            <textarea name="motivo" id="motivo" rows="2" required minlength="10" placeholder="Ej.: olvidó registrar la salida; salió a las 15:00 según el cuadrante"
                      class="campo mt-1">{{ old('motivo') }}</textarea>
            <p class="text-xs text-tinta-suave mt-1">Queda registrado en el historial del fichaje, visible para el trabajador (registro de jornada).</p>
            @error('motivo') <p class="text-peligro text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div class="grupo-acciones grupo-acciones--final pt-2">
            <a href="{{ route('fichajes.show', $fichaje) }}" title="Cancelar" class="boton tono-neutro">Cancelar</a>
            <button type="submit" title="Guardar cambios" class="boton boton-primario">
                <x-lucide-save /> Guardar
            </button>
        </div>
    </form>
</x-app-layout>
