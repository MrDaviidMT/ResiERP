<x-app-layout :volver="route('habitaciones.index')" volver-texto="Volver a habitaciones" estrecho>
    <h1 class="titulo-pagina mb-6">Nueva Habitación</h1>

    <div class="tarjeta p-6">
        <form action="{{ route('habitaciones.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="etiqueta-campo block">Número de Habitación <span class="text-peligro">*</span></label>
                <input type="text" name="numero" class="campo mt-1" placeholder="Ej: 104A" required>
            </div>

            <div>
                <label class="etiqueta-campo block">Tipo de Habitación <span class="text-peligro">*</span></label>
                <select name="tipo" class="campo mt-1" required>
                    <option value="individual">Individual</option>
                    <option value="doble">Doble</option>
                </select>
            </div>

            <div>
                <label class="etiqueta-campo block">Observaciones</label>
                <textarea name="observaciones" rows="2" placeholder="Notas sobre la habitación..." class="campo mt-1"></textarea>
            </div>

            <button type="button" title="Crear nueva habitación" data-swal-submit data-title="Crear habitación" data-text="¿Crear esta nueva habitación?" class="boton boton-primario w-full">
                <x-lucide-building-2 /> Crear Habitación
            </button>
        </form>
    </div>
</x-app-layout>
