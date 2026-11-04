<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    // Igual que el valor por defecto de la columna, para que un usuario recién creado ya sea activo en memoria
    protected $attributes = [
        'activo' => true,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    /** Rol principal (puesto de trabajo) del empleado. */
    public function rol(): ?Role
    {
        return Role::tryFrom($this->roles->first()?->name ?? '');
    }

    public function esSuperadmin(): bool
    {
        return $this->hasRole(Role::Superadmin);
    }

    public function fichajes(): HasMany
    {
        return $this->hasMany(Fichaje::class);
    }
}
