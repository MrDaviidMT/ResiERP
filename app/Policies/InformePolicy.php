<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Informe;
use App\Models\User;

// Los permisos de informes tienen ámbito por tipo.
// Ver y crear dependen del tipo; editar es solo del autor; anular (nunca borrar), de quien tenga informes.anular.
class InformePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny(Permission::verInformes());
    }

    public function view(User $user, Informe $informe): bool
    {
        return $user->can(Permission::verInforme($informe->tipo));
    }

    public function create(User $user): bool
    {
        return $user->canAny(Permission::crearInformes());
    }

    public function update(User $user, Informe $informe): bool
    {
        return $informe->user_id === $user->id && $user->can(Permission::crearInforme($informe->tipo));
    }

    public function anular(User $user): bool
    {
        return $user->can(Permission::InformesAnular);
    }
}
