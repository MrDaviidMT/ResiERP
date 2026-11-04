<x-app-layout :volver="route('informes.index')" volver-texto="Volver a informes" estrecho>
    <h1 class="titulo-pagina mb-6">Nuevo Informe</h1>

    <div class="tarjeta p-6">

        <form action="{{ route('informes.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="etiqueta-campo block">Título <span class="text-peligro">*</span></label>
                    <input type="text" name="titulo" value="{{ old('titulo') }}" required class="campo mt-1">
                </div>
                <div>
                    <label class="etiqueta-campo block">Tipo <span class="text-peligro">*</span></label>
                    <select name="tipo" required class="campo mt-1">
                        @foreach($tipos as $val => $label)
                            <option value="{{ $val }}" {{ old('tipo') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="etiqueta-campo block">Residentes relacionados</label>
                <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 max-h-48 overflow-y-auto border border-linea rounded-lg p-3">
                    @php $oldResidentes = old('residentes', $preseleccionado ? [$preseleccionado] : []) @endphp
                    @foreach($residentes as $res)
                    <label class="flex items-center space-x-2 text-sm">
                        <input type="checkbox" name="residentes[]" value="{{ $res->id }}" {{ in_array($res->id, $oldResidentes) ? 'checked' : '' }} class="rounded border-linea-fuerte">
                        <span>{{ $res->apellidos }}, {{ $res->nombre }} @if($res->habitacion)(Hab. {{ $res->habitacion->numero }})@endif</span>
                    </label>
                    @endforeach
                </div>
                <div class="flex space-x-3 mt-1">
                    <button type="button" data-marcar="residentes[]" class="text-xs text-marca-700 hover:text-marca-800 cursor-pointer">Añadir todos</button>
                    <button type="button" data-desmarcar="residentes[]" class="text-xs text-peligro hover:text-peligro cursor-pointer">Quitar todos</button>
                </div>
            </div>

            <div>
                <label class="etiqueta-campo block">Contenido <span class="text-peligro">*</span></label>
                <textarea name="contenido" rows="12" required class="campo mt-1">{{ old('contenido') }}</textarea>
            </div>

            <div>
                <label class="etiqueta-campo block">PDF adjunto</label>
                <x-campo-archivo name="pdf" accept=".pdf,application/pdf" />
                <p class="text-xs text-tinta-tenue mt-1">Máximo 20 MB. Solo PDF.</p>
            </div>

            <div class="grupo-acciones grupo-acciones--final pt-4 border-t border-linea">
                <a href="{{ route('informes.index') }}" title="Cancelar" class="boton tono-neutro">
                    <x-lucide-x />Cancelar
                </a>
                <button type="submit" title="Guardar informe" class="boton boton-primario">
                    <x-lucide-check />Guardar
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
