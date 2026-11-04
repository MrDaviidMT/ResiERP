<x-app-layout :volver="route('actividades.index')" volver-texto="Volver a actividades" estrecho>
    <h1 class="titulo-pagina mb-6">Nueva Actividad</h1>

    <div class="tarjeta p-6">

        <form action="{{ route('actividades.store') }}" method="POST" class="space-y-6">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="md:col-span-2">
                    <label class="etiqueta-campo block">Título <span class="text-peligro">*</span></label>
                    <input type="text" name="titulo" value="{{ old('titulo') }}" required placeholder="Ej: Taller de memoria" class="campo mt-1">
                </div>
                <div>
                    <label class="etiqueta-campo block">Hora</label>
                    <input type="time" name="hora" value="{{ old('hora') }}" placeholder="hh:mm" class="campo mt-1">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="etiqueta-campo block">Fecha <span class="text-peligro">*</span></label>
                    <input type="date" name="fecha" value="{{ old('fecha') }}" required placeholder="dd/mm/aaaa" class="campo mt-1">
                </div>
            </div>

            <div>
                <label class="etiqueta-campo block">Descripción</label>
                <textarea name="descripcion" rows="4" placeholder="Describe la actividad..." class="campo mt-1">{{ old('descripcion') }}</textarea>
            </div>

            <div>
                <label class="etiqueta-campo block">Participantes (residentes)</label>
                <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 max-h-48 overflow-y-auto border border-linea rounded-lg p-3">
                    @foreach($residentes as $res)
                    <label class="flex items-center space-x-2 text-sm">
                        <input type="checkbox" name="residentes[]" value="{{ $res->id }}" {{ in_array($res->id, old('residentes', [])) ? 'checked' : '' }} class="rounded border-linea-fuerte">
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
                <button type="submit" title="Guardar actividad" class="boton boton-primario">
                    <x-lucide-save />Guardar
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
