<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h3 class="text-lg font-bold text-tinta flex items-center">
            <x-lucide-file-text class="w-5 h-5 mr-1" /> Informes
            <span class="ml-2 text-sm font-normal text-tinta-tenue">({{ $residente->informes->count() }})</span>
        </h3>
        @can('create', \App\Models\Informe::class)
        <a href="{{ route('informes.create', ['residente_id' => $residente->id]) }}" title="Crear nuevo informe" class="boton boton-primario">
            <x-lucide-plus />Nuevo Informe
        </a>
        @endcan
    </div>

    @if($residente->informes->count() > 0)
        <div class="overflow-x-auto">
            <table class="tabla ">
                <thead>
                    <tr>
                        <th class="pb-2 font-semibold">Título</th>
                        <th class="pb-2 font-semibold">Tipo</th>
                        <th class="pb-2 font-semibold">Fecha</th>
                        <th class="pb-2 font-semibold">Autor</th>
                        <th class="pb-2 font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-linea">
                    @foreach($residente->informes as $informe)
                        <tr>
                            <td data-label="Título">
                                <a href="{{ route('informes.show', $informe) }}" class="enlace-fila">{{ $informe->titulo }}</a>
                            </td>
                            <td data-label="Tipo">
                                <span class="etiqueta tono-neutro">{{ ucfirst($informe->tipo) }}</span>
                            </td>
                            <td class="text-tinta-suave text-xs" data-label="Fecha">{{ $informe->created_at->format('d/m/Y') }}</td>
                            <td class="text-tinta-suave text-xs" data-label="Autor">{{ $informe->user->name }}</td>
                            <td class="acciones" data-label="Acciones">
                                <div class="grupo-acciones">
                                    <a href="{{ route('informes.show', $informe) }}" title="Ver informe" class="accion tono-neutro"><x-lucide-eye  /></a>
                                    @if($informe->pdf_path)
                                    <x-accion icono="file" titulo="Descargar PDF" tono="marca" href="{{ route('informes.pdf', $informe) }}" target="_blank" />
                                    @endif
                                    @can('update', $informe)
                                    <x-accion icono="pencil" titulo="Editar informe" tono="marca" href="{{ route('informes.edit', $informe) }}" />
                                    @endcan
                                    @can('anular', $informe)
                                    <x-boton-motivo :action="route('informes.anular', $informe)" titulo="Anular informe" />
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-sm text-tinta-tenue italic text-center py-8">No hay informes para este residente.</p>
    @endif
</div>

<x-registros-anulados :registros="$anulados['informes']" />
