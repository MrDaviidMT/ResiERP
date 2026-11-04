<x-app-layout :volver="route('residentes.index')" volver-texto="Volver a residentes" estrecho>
    <h1 class="titulo-pagina mb-6">Dar de alta a un residente</h1>

    <div>
        <div>
            <div class="tarjeta p-6">
                

                <form action="{{ route('residentes.store') }}" method="POST" class="space-y-6">
                    @csrf <!-- Token de seguridad obligatorio en Laravel -->

                    <!-- Fila 1: Nombre y Apellidos -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="etiqueta-campo block">Nombre <span class="text-peligro">*</span></label>
                            <input type="text" name="nombre" value="{{ old('nombre') }}" required placeholder="Nombre del residente" class="campo mt-1">
                        </div>
                        <div>
                            <label class="etiqueta-campo block">Apellidos <span class="text-peligro">*</span></label>
                            <input type="text" name="apellidos" value="{{ old('apellidos') }}" required placeholder="Apellidos del residente" class="campo mt-1">
                        </div>
                    </div>

                    <!-- Fila 2: DNI y Habitación -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="etiqueta-campo block">DNI / Pasaporte <span class="text-peligro">*</span></label>
                            <input type="text" name="dni" value="{{ old('dni') }}" required placeholder="12345678X" class="campo mt-1">
                        </div>
                        <div>
                            <label class="etiqueta-campo block">Habitación Asignada <span class="text-peligro">*</span></label>
                            <select name="habitacion_id" required class="campo mt-1">
                                <option value="">Selecciona una habitación...</option>
                                @foreach($habitaciones as $habitacion)
                                    <option value="{{ $habitacion->id }}" {{ old('habitacion_id') == $habitacion->id ? 'selected' : '' }}>
                                        Número {{ $habitacion->numero }} ({{ $habitacion->tipo }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Fila 3: Fechas -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="etiqueta-campo block">Fecha de Nacimiento <span class="text-peligro">*</span></label>
                            <input type="date" name="nacimiento" value="{{ old('nacimiento') }}" required placeholder="dd/mm/aaaa" class="campo mt-1">
                        </div>
                        <div>
                            <label class="etiqueta-campo block">Fecha de Ingreso <span class="text-peligro">*</span></label>
                            <input type="date" name="ingreso" value="{{ old('ingreso') }}" required placeholder="dd/mm/aaaa" class="campo mt-1">
                        </div>
                    </div>

                    <!-- Fila 4: Grupo Sanguíneo -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="etiqueta-campo block">Grupo Sanguíneo</label>
                            <input type="text" name="grupo_sanguineo" value="{{ old('grupo_sanguineo') }}" placeholder="Ej: A+" class="campo mt-1">
                        </div>
                        <div>
                            <label class="etiqueta-campo block">Dependencia</label>
                            <select name="dependencia" class="campo mt-1">
                                <option value="">Selecciona nivel...</option>
                                <option value="ninguno" {{ old('dependencia') === 'ninguno' ? 'selected' : '' }}>Ninguno</option>
                                <option value="leve" {{ old('dependencia') === 'leve' ? 'selected' : '' }}>Leve</option>
                                <option value="moderado" {{ old('dependencia') === 'moderado' ? 'selected' : '' }}>Moderado</option>
                                <option value="grave" {{ old('dependencia') === 'grave' ? 'selected' : '' }}>Grave</option>
                                <option value="gran_dependiente" {{ old('dependencia') === 'gran_dependiente' ? 'selected' : '' }}>Gran Dependiente</option>
                            </select>
                        </div>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="grupo-acciones grupo-acciones--final pt-4 border-t border-linea">
                        <a href="{{ route('residentes.index') }}" title="Cancelar y volver al listado" class="boton tono-neutro">
                            <x-lucide-x />Cancelar
                        </a>
                        <button type="button" title="Guardar nuevo residente" data-swal-submit data-title="Dar de alta residente" data-text="¿Registrar este nuevo residente?" class="boton boton-primario">
                            <x-lucide-user-plus />Guardar Residente
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>