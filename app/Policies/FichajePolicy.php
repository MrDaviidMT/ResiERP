<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Models\Fichaje;
use App\Models\User;

// Cada empleado ve y cierra sus propios fichajes; ver los de otros requiere permiso.
// Corregir y anular: con permiso y NUNCA sobre los propios (ET art. 34.9: nadie altera su propio registro).
class FichajePolicy
{
    public function view(User $user, Fichaje $fichaje): bool
    {
        return ($fichaje->user_id === $user->id && $user->can(Permission::FichajesVerPropios)) || $user->can(Permission::FichajesVerTodos);
    }

    // Cerrar el fichaje de otro empleado es una corrección (con motivo), no un "registrar salida"
    public function registrarSalida(User $user, Fichaje $fichaje): bool
    {
        return $fichaje->user_id === $user->id && $user->can(Permission::FichajesRegistrarPropios);
    }

    public function corregir(User $user, Fichaje $fichaje): bool
    {
        return $fichaje->user_id !== $user->id && $user->can(Permission::FichajesEditar);
    }

    public function anular(User $user, Fichaje $fichaje): bool
    {
        return $fichaje->user_id !== $user->id && $user->can(Permission::FichajesAnular);
    }
}
