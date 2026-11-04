<x-app-layout>
    <h1 class="titulo-pagina mb-6">Registro de Actividad</h1>

    <div class="tarjeta p-6">
        <form method="GET" class="filtros space-y-4" x-data="{ filtersOpen: true }">
            <button @click="filtersOpen = !filtersOpen" type="button" class="flex items-center gap-2 text-sm font-medium text-tinta-suave hover:text-tinta cursor-pointer mb-2">
                <span x-show="!filtersOpen" x-cloak><x-lucide-funnel-plus class="w-4 h-4" /></span>
                <span x-show="filtersOpen" x-cloak><x-lucide-funnel-x class="w-4 h-4" /></span>
                <span>Filtros</span>
            </button>
            <div :class="filtersOpen ? 'filter-expanded' : 'filter-collapsed'" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label for="filter_loggable_type" class="etiqueta-campo block">Modelo</label>
                    <select name="loggable_type" id="filter_loggable_type" class="mt-1 block w-full campo text-sm font-medium text-tinta">
                        <option value="">Todos</option>
                        @foreach($modelos as $m)
                            <option value="{{ $m }}" @selected(request('loggable_type') === $m)>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="filter_action" class="etiqueta-campo block">Acción</label>
                    <select name="action" id="filter_action" class="mt-1 block w-full campo text-sm font-medium text-tinta">
                        <option value="">Todas</option>
                        <option value="created" @selected(request('action') === 'created')>Creado</option>
                        <option value="updated" @selected(request('action') === 'updated')>Actualizado</option>
                        <option value="deleted" @selected(request('action') === 'deleted')>Eliminado</option>
                        <option value="restored" @selected(request('action') === 'restored')>Restaurado</option>
                        <option value="viewed" @selected(request('action') === 'viewed')>Consulta</option>
                        <option value="annulled" @selected(request('action') === 'annulled')>Anulado</option>
                        <option value="withdrawn" @selected(request('action') === 'withdrawn')>Retirada</option>
                        <option value="exported" @selected(request('action') === 'exported')>Exportación</option>
                        <option value="anonymised" @selected(request('action') === 'anonymised')>Anonimizado</option>
                        <option value="retention_applied" @selected(request('action') === 'retention_applied')>Conservación aplicada</option>
                        <option value="retention_dry_run" @selected(request('action') === 'retention_dry_run')>Conservación (simulación)</option>
                        <option value="linked" @selected(request('action') === 'linked')>Vinculación</option>
                        <option value="links_synced" @selected(request('action') === 'links_synced')>Vínculos actualizados</option>
                        <option value="role_attached" @selected(request('action') === 'role_attached')>Rol asignado</option>
                        <option value="role_detached" @selected(request('action') === 'role_detached')>Rol retirado</option>
                        <option value="permission_attached" @selected(request('action') === 'permission_attached')>Permiso añadido</option>
                        <option value="permission_detached" @selected(request('action') === 'permission_detached')>Permiso retirado</option>
                    </select>
                </div>
                <div>
                    <label for="filter_user_id" class="etiqueta-campo block">Usuario</label>
                    <select name="user_id" id="filter_user_id" class="mt-1 block w-full campo text-sm font-medium text-tinta">
                        <option value="">Todos</option>
                        @foreach($usuarios as $u)
                            <option value="{{ $u->id }}" @selected(request('user_id') == $u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="filter_desde" class="etiqueta-campo block">Desde</label>
                    <input type="date" name="desde" id="filter_desde" value="{{ request('desde') }}" class="mt-1 block w-full campo">
                </div>
                <div>
                    <label for="filter_hasta" class="etiqueta-campo block">Hasta</label>
                    <input type="date" name="hasta" id="filter_hasta" value="{{ request('hasta') }}" class="mt-1 block w-full campo">
                </div>
            </div>
            <div class="grupo-acciones grupo-acciones--final">
                <a href="{{ route('logs.index') }}" title="Limpiar todos los filtros" class="boton tono-neutro">
                    <x-lucide-eraser />Limpiar Filtros
                </a>
                <button type="submit" title="Filtrar registros de actividad" class="boton tono-neutro">
                    <x-lucide-search />Filtrar
                </button>
            </div>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="tabla ">
                <thead>
                    <tr>
                        <th>Fecha/Hora</th>
                        <th>Usuario</th>
                        <th>Modelo</th>
                        <th class="centrado">Acción</th>
                        <th>Cambios</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="text-xs whitespace-nowrap" data-label="Fecha/Hora">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td class="text-xs" data-label="Usuario">
                                {{ $log->user?->name ?? 'Sistema' }}
                                @if($log->ip_address)
                                    <span class="block text-tinta-tenue" title="{{ $log->user_agent }}">{{ $log->ip_address }}</span>
                                @endif
                            </td>
                            <td class="text-xs" data-label="Modelo">{{ $log->loggable_type ? class_basename($log->loggable_type).' #'.$log->loggable_id : 'Tarea del sistema' }}</td>
                            <td class="centrado" data-label="Acción">
                                @if($log->action === 'created')
                                    <span class="etiqueta tono-exito">Creado</span>
                                @elseif($log->action === 'updated')
                                    <span class="etiqueta tono-marca">Actualizado</span>
                                @elseif($log->action === 'deleted')
                                    <span class="etiqueta tono-peligro">Eliminado</span>
                                @elseif($log->action === 'restored')
                                    <span class="etiqueta tono-aviso">Restaurado</span>
                                @elseif($log->action === 'viewed')
                                    <span class="etiqueta tono-neutro">Consulta</span>
                                @elseif($log->action === 'annulled')
                                    <span class="etiqueta tono-peligro">Anulado</span>
                                @elseif($log->action === 'withdrawn')
                                    <span class="etiqueta tono-aviso">Retirada</span>
                                @elseif($log->action === 'exported')
                                    <span class="etiqueta tono-marca">Exportación</span>
                                @elseif($log->action === 'anonymised')
                                    <span class="etiqueta tono-neutro">Anonimizado</span>
                                @elseif(in_array($log->action, ['retention_applied', 'retention_dry_run']))
                                    <span class="etiqueta tono-neutro">{{ $log->action === 'retention_applied' ? 'Conservación aplicada' : 'Conservación (simulación)' }}</span>
                                @elseif(in_array($log->action, ['linked', 'links_synced']))
                                    <span class="etiqueta tono-marca">{{ $log->action === 'linked' ? 'Vinculación' : 'Vínculos actualizados' }}</span>
                                @elseif(in_array($log->action, ['role_attached', 'permission_attached']))
                                    <span class="etiqueta tono-neutro">{{ $log->action === 'role_attached' ? 'Rol asignado' : 'Permiso añadido' }}</span>
                                @elseif(in_array($log->action, ['role_detached', 'permission_detached']))
                                    <span class="etiqueta tono-neutro">{{ $log->action === 'role_detached' ? 'Rol retirado' : 'Permiso retirado' }}</span>
                                @else
                                    <span class="etiqueta tono-neutro">{{ $log->action }}</span>
                                @endif
                            </td>
                            <td class="text-xs max-w-md" data-label="Cambios">
                                @if(in_array($log->action, ['retention_applied', 'retention_dry_run']))
                                    <span class="text-tinta-suave">{{ collect($log->changes['resultado'] ?? [])->filter()->map(fn ($n, $k) => str_replace('_', ' ', $k).': '.$n)->join(' · ') ?: 'Nada vencido' }}</span>
                                @elseif($log->action === 'anonymised')
                                    <span class="text-tinta-suave">Política: {{ $log->changes['politica'] ?? '—' }}</span>
                                @elseif($log->action === 'exported')
                                    <span class="text-tinta-suave">{{ $log->changes['tipo'] ?? '—' }} · {{ $log->changes['fichajes'] ?? 0 }} registro(s)</span>
                                @elseif($log->action === 'linked')
                                    <span class="text-tinta-suave">Residente #{{ $log->changes['residente_id'] ?? '—' }} · {{ $log->changes['parentesco'] ?? '—' }} · {{ ($log->changes['autorizado'] ?? false) ? 'autorizado' : 'NO autorizado' }}</span>
                                @elseif($log->action === 'links_synced')
                                    <span class="text-tinta-suave">{{ count($log->changes['vinculos'] ?? []) }} vínculo(s): {{ collect($log->changes['vinculos'] ?? [])->map(fn ($v) => '#'.$v['residente_id'].($v['autorizado'] ? ' (autorizado)' : ' (no autorizado)'))->join(', ') }}</span>
                                @elseif(in_array($log->action, ['annulled', 'withdrawn']))
                                    <span class="text-tinta-suave">Motivo: {{ $log->changes['motivo'] ?? '—' }}</span>
                                @elseif($log->action === 'viewed')
                                    <span class="text-tinta-suave">Pantalla: {{ $log->changes['pantalla'] ?? '—' }}</span>
                                @elseif(isset($log->changes['roles']) || isset($log->changes['permisos']))
                                    <span class="font-medium text-tinta">{{ implode(', ', $log->changes['roles'] ?? $log->changes['permisos']) }}</span>
                                @elseif($log->action === 'created')
                                    <span class="text-tinta-tenue italic">Alta del registro</span>
                                @elseif($log->action === 'deleted' && $log->changes)
                                    <span class="text-tinta-tenue italic">Instantánea del registro eliminado guardada</span>
                                @elseif($log->changes && $log->action !== 'created')
                                    <div class="space-y-1">
                                        @foreach($log->changes['new'] ?? [] as $campo => $valor)
                                            @if(isset($log->changes['old'][$campo]) && $log->changes['old'][$campo] != $valor)
                                                <div class="text-tinta-suave">
                                                    <span class="font-medium">{{ $campo }}:</span>
                                                    <span class="line-through text-peligro">{{ $log->changes['old'][$campo] ?? '—' }}</span>
                                                    →
                                                    <span class="text-exito">{{ $valor ?? '—' }}</span>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-tinta-tenue italic">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="centrado text-tinta-suave italic" data-label="">No hay registros de actividad.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $logs->links() }}
        </div>
    </div>
</x-app-layout>
