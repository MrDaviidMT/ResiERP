<?php

namespace App\Enums;

// Roles principales (puesto de trabajo, uno por empleado). El superadministrador es técnico,
// no es un rol de negocio: tiene todos los permisos mediante Gate::before.
enum Role: string
{
    case Superadmin = 'superadmin';
    case Director = 'director';
    case Enfermero = 'enfermero';
    case Auxiliar = 'auxiliar';
    case Recepcionista = 'recepcionista';
    case Psicologo = 'psicologo';
    case TrabajadorSocial = 'trabajador_social';
    case Fisioterapeuta = 'fisioterapeuta';
    case Cocinero = 'cocinero';
    case Mantenimiento = 'mantenimiento';

    public function label(): string
    {
        return match ($this) {
            self::Superadmin => 'Superadministrador',
            self::Director => 'Director',
            self::Enfermero => 'Enfermero',
            self::Auxiliar => 'Auxiliar',
            self::Recepcionista => 'Recepcionista',
            self::Psicologo => 'Psicólogo',
            self::TrabajadorSocial => 'Trabajador social',
            self::Fisioterapeuta => 'Fisioterapeuta',
            self::Cocinero => 'Cocinero',
            self::Mantenimiento => 'Mantenimiento',
        };
    }

    /** Roles de negocio: los que se asignan desde la gestión de empleados. @return list<self> */
    public static function deNegocio(): array
    {
        return array_values(array_filter(self::cases(), fn (self $r) => $r !== self::Superadmin));
    }
}
