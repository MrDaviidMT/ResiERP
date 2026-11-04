<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Incidencia;
use App\Models\User;

// Reportar es libre; ver las de tipo "personal" es de dirección y de quien la registró (D9);
// atender (editar, cambiar estado) depende de la categoría: incidencias.atender.{categoria}.
class IncidenciaPolicy
{
    public function view(User $user, Incidencia $incidencia): bool
    {
        if ($incidencia->categoria === 'personal') {
            return $user->can(Permission::IncidenciasVerPersonal) || $incidencia->user_id === $user->id;
        }

        return $user->can(Permission::IncidenciasVer);
    }

    public function update(User $user, Incidencia $incidencia): bool
    {
        return $user->can(Permission::atenderIncidencia($incidencia->categoria));
    }
}
