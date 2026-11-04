<x-app-layout :volver="route('actividades.show', $actividad)" volver-texto="Volver a la actividad" estrecho>
    <h1 class="titulo-pagina mb-6">Editar Actividad</h1>

    <div class="tarjeta p-6">

        <form action="{{ route('actividades.update', $actividad) }}" method="POST" class="space-y-6">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="md:col-span-2">
                    <label class="etiqueta-campo block">Título <span class="text-peligro">*</span></label>
                    <input type="text" name="titulo" value="{{ old('titulo', $actividad->titulo) }}" required placeholder="Ej: Taller de memoria" class="campo mt-1">
                </div>
                <div>
                    <label class="etiqueta-campo block">Hora</label>
                    <input type="time" name="hora" value="{{ old('hora', $actividad->hora ? $actividad->hora->format('H:i') : '') }}" placeholder="hh:mm" class="campo mt-1">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="etiqueta-campo block">Fecha <span class="text-peligro">*</span></label>
                    <input type="date" name="fecha" value="{{ old('fecha', $actividad->fecha ? $actividad->fecha->format('Y-m-d') : '') }}" required placeholder="dd/mm/aaaa" class="campo mt-1">
                </div>
            </div>

            <div>
                <label class="etiqueta-campo block">Descripción</label>
                <textarea name="descripcion" rows="4" placeholder="Describe la actividad..." class="campo mt-1">{{ old('descripcion', $actividad->descripcion) }}</textarea>
            </div>

            <div>
                <label class="etiqueta-campo block">Participantes (residentes)</label>
                <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 max-h-48 overflow-y-auto border border-linea rounded-lg p-3">
                    @php $selectedResidentes = old('residentes', $actividad->residentes->pluck('id')->toArray()) @endphp
                    @foreach($residentes as $res)
                    <label class="flex items-center space-x-2 text-sm">
                        <input type="checkbox" name="residentes[]" value="{{ $res->id }}" {{ in_array($res->id, $selectedResidentes) ? 'checked' : '' }} class="rounded border-linea-fuerte">
                        <span>{{ $res->apellidos }}, {{ $res->nombre }} @if($res->habitacion)(Hab. {{ $res->habitacion->numero }})@endif</span>
                    </label>
                    @endforeach
                </div>
                <div class="flex space-x-3 mt-1">
                    <button type="button" data-marcar="residentes[]" class="text-xs text-marca-700 hover:text-marca-800 cursor-pointer">Añadir todos</button>
                    <button type="button" data-desmarcar="residentes[]" class="text-xs text-peligro hover:text-peligro cursor-pointer">Quitar todos</button>
                </div>
            </div>

            <div class="grupo-acciones grupo-acciones--final pt-4 border-t border-linea">
                <a href="{{ route('actividades.index') }}" title="Cancelar" class="boton tono-neutro">
                    <x-lucide-x />Cancelar
                </a>
                <button type="submit" title="Guardar cambios" class="boton boton-primario">
                    <x-lucide-save />Guardar
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
