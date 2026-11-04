<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

// El contenido de un registro clínico solo lo corrige su autor.
// Para corregir lo de otro profesional: anular con motivo y registrar de nuevo.
// (El permiso del módulo se comprueba aparte, en la ruta.)
class RegistroClinicoPolicy
{
    public function update(User $user, Model $registro): bool
    {
        return $registro->esAutor($user);
    }
}
