<?php

namespace App\Policies;

use App\Enums\Permission;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Spatie\Permission\Models\Role as RolSpatie;

// Gestión de empleados. El superadministrador no es un empleado: para el resto de usuarios
// no existe (404 en lugar de 403, para no revelar ni que hay una cuenta así).
// El propio superadministrador lo puede todo mediante Gate::before.
class UserPolicy
{
    public function view(User $actor, User $user): Response
    {
        return $this->empleado($user) ?? $this->segun($actor->can(Permission::EmpleadosVer));
    }

    public function update(User $actor, User $user): Response
    {
        return $this->empleado($user) ?? $this->segun($actor->can(Permission::EmpleadosEditar));
    }

    public function verificarEmail(User $actor, User $user): Response
    {
        return $this->empleado($user) ?? $this->segun($actor->can(Permission::EmpleadosVerificarEmail));
    }

    public function desactivar(User $actor, User $user): Response
    {
        return $this->empleado($user)
            ?? $this->segun($actor->can(Permission::EmpleadosDesactivar) && ! $actor->is($user));
    }

    /**
     * Anti-escalada de privilegios: solo se asigna un rol si quien lo asigna ya tiene todos sus permisos
     * administrativos (Permission::esAdministrativo). Los permisos clínicos no cuentan, porque el director
     * asigna puestos clínicos sin poder editar contenido clínico; ese riesgo se cubre con la auditoría.
     * El superadministrador nunca se asigna desde la aplicación.
     */
    public function asignarRol(User $actor, Role $rol): bool
    {
        if ($rol === Role::Superadmin || ! $actor->can(Permission::EmpleadosAsignarRoles)) {
            return false;
        }

        return RolSpatie::findByName($rol->value)->permissions
            ->map(fn ($permiso) => Permission::from($permiso->name))
            ->filter(fn (Permission $permiso) => $permiso->esAdministrativo())
            ->every(fn (Permission $permiso) => $actor->can($permiso));
    }

    private function segun(bool $permitido): Response
    {
        return $permitido ? Response::allow() : Response::deny();
    }

    private function empleado(User $user): ?Response
    {
        return $user->esSuperadmin() ? Response::denyAsNotFound() : null;
    }
}
