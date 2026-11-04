<?php

namespace App\Listeners;

use App\Models\Log;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Spatie\Permission\Events\PermissionAttachedEvent;
use Spatie\Permission\Events\PermissionDetachedEvent;
use Spatie\Permission\Events\RoleAttachedEvent;
use Spatie\Permission\Events\RoleDetachedEvent;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// Auditoría de quién da o quita qué rol o permiso a qué empleado (anti-escalada por detección).
// Los cambios de las plantillas de rol (PermissionSeeder) no se registran aquí: son commits revisables.
// Laravel registra este listener solo (descubrimiento automático por el tipo del parámetro de handle()).
class AuditarCambiosDePermisos
{
    public function handle(RoleAttachedEvent|RoleDetachedEvent|PermissionAttachedEvent|PermissionDetachedEvent $event): void
    {
        if (! $event->model instanceof User) {
            return;
        }

        [$accion, $clave, $modelo, $valores] = match (true) {
            $event instanceof RoleAttachedEvent => ['role_attached', 'roles', Role::class, $event->rolesOrIds],
            $event instanceof RoleDetachedEvent => ['role_detached', 'roles', Role::class, $event->rolesOrIds],
            $event instanceof PermissionAttachedEvent => ['permission_attached', 'permisos', Permission::class, $event->permissionsOrIds],
            $event instanceof PermissionDetachedEvent => ['permission_detached', 'permisos', Permission::class, $event->permissionsOrIds],
        };

        Log::registrar($event->model, $accion, [$clave => $this->nombres($modelo, $valores)]);
    }

    /** Spatie puede pasar ids, modelos o colecciones: se normaliza a nombres. @return list<string> */
    private function nombres(string $modelo, mixed $valores): array
    {
        $items = collect(Arr::wrap($valores instanceof Collection ? $valores->all() : $valores));
        $ids = $items->map(fn ($item) => $item instanceof $modelo ? $item->getKey() : $item);

        return $modelo::whereIn('id', $ids)->orderBy('name')->pluck('name')->all();
    }
}
