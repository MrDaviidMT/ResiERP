<?php

namespace Database\Seeders;

use App\Enums\Permission as P;
use App\Enums\Role as R;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

// Catálogo de permisos y plantillas por rol. Idempotente: se puede ejecutar en cada despliegue.
// Cambiar lo que puede hacer un rol = cambiar este archivo (un commit revisable), nunca la base de datos a mano.
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (P::cases() as $permiso) {
            Permission::findOrCreate($permiso->value, 'web');
        }

        // Elimina permisos que ya no existen en el catálogo. Es un borrado masivo: no dispara
        // eventos de modelo, así que la caché de spatie no se invalida sola.
        Permission::whereNotIn('name', array_map(fn (P $p) => $p->value, P::cases()))->delete();

        // Se sincroniza con modelos leídos de la base de datos, no con nombres: así el seeder no
        // depende de la caché de permisos (con el almacén de caché en base de datos puede quedar
        // guardado un conjunto vacío que se seguiría sirviendo, y syncPermissions fallaría).
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        // toBase(): en una colección de Eloquent, only() filtra por clave primaria, no por la clave del array
        $permisos = Permission::where('guard_name', 'web')->get()->keyBy('name')->toBase();

        Role::findOrCreate(R::Superadmin->value, 'web');

        foreach (self::plantillas() as $rol => $delRol) {
            Role::findOrCreate($rol, 'web')->syncPermissions($permisos->only(array_map(fn (P $p) => $p->value, $delRol))->values()->all());
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Plantilla de permisos de cada rol de negocio.
     *
     * @return array<string, list<P>>
     */
    public static function plantillas(): array
    {
        return self::conAnulacionClinica(self::plantillasBase());
    }

    /**
     * Anular un registro clínico (y retirar una pauta) lo pueden hacer los mismos roles que pueden editarlo.
     * Se deriva aquí para que ambas listas no puedan divergir.
     *
     * @param  array<string, list<P>>  $plantillas
     * @return array<string, list<P>>
     */
    private static function conAnulacionClinica(array $plantillas): array
    {
        $anularSegunEditar = [
            P::DiagnosticosAnular->value => P::DiagnosticosEditar,
            P::AlergiasAnular->value => P::AlergiasEditar,
            P::ConstantesAnular->value => P::ConstantesRegistrar,
            P::CuidadosAnular->value => P::CuidadosRegistrar,
            P::VisitasMedicasAnular->value => P::VisitasMedicasGestionar,
            P::MedicacionRetirar->value => P::MedicacionEditar,
            P::MedicacionAnular->value => P::MedicacionEditar,
        ];

        foreach ($plantillas as $rol => $permisos) {
            foreach ($anularSegunEditar as $anular => $editar) {
                if (in_array($editar, $permisos, true)) {
                    $plantillas[$rol][] = P::from($anular);
                }
            }
        }

        return $plantillas;
    }

    /** @return array<string, list<P>> */
    private static function plantillasBase(): array
    {
        // Comunes a todo el personal
        $todos = [P::IncidenciasVer, P::IncidenciasCrear, P::MenusVer, P::FichajesRegistrarPropios, P::FichajesVerPropios];

        // Ven historial clínico completo (y los datos de salud de la ficha: grupo sanguíneo, dependencia)
        $historial = [P::ResidentesVerSalud, P::DiagnosticosVer, P::AlergiasVer, P::ConstantesVer, P::CuidadosVer, P::VisitasMedicasVer];

        return [
            R::Director->value => [
                ...$todos, P::ResidentesExportarHistorial, P::ResidentesVerDocumento, P::DietasVer, ...$historial,
                P::ResidentesVer, P::ResidentesCrear, P::ResidentesEditar, P::ResidentesBaja, P::ResidentesReincorporar,
                P::VisitasMedicasGestionar,
                P::MedicacionVer,
                ...P::verInformes(), P::InformesCrearGeneral, P::InformesAnular,
                P::FamiliaresVer, P::FamiliaresVerContacto, P::FamiliaresCrear, P::FamiliaresEditar, P::FamiliaresVincular, P::FamiliaresBorrar,
                P::MenusGestionar,
                P::VisitasVer, P::VisitasRegistrar, P::VisitasEditar, P::VisitasAnular,
                P::HabitacionesVer, P::HabitacionesCrear, P::HabitacionesEditar, P::HabitacionesMantenimiento, P::HabitacionesBorrar,
                P::IncidenciasVerPersonal, P::IncidenciasAtenderSanitaria, P::IncidenciasAtenderLimpieza,
                P::IncidenciasAtenderInfraestructura, P::IncidenciasAtenderOtros, P::IncidenciasAtenderPersonal, P::IncidenciasAnular,
                P::ActividadesVer, P::ActividadesGestionar,
                P::FichajesVerTodos, P::FichajesCrearOtros, P::FichajesEditar, P::FichajesAnular,
                P::EmpleadosVer, P::EmpleadosCrear, P::EmpleadosEditar, P::EmpleadosDesactivar, P::EmpleadosAsignarRoles, P::EmpleadosVerificarEmail,
                P::AuditoriaVer,
            ],
            R::Enfermero->value => [
                ...$todos, P::ResidentesVerDocumento, P::DietasVer, ...$historial,
                P::ResidentesVer,
                P::DiagnosticosEditar, P::AlergiasEditar, P::ConstantesRegistrar, P::VisitasMedicasGestionar,
                P::MedicacionVer, P::MedicacionEditar,
                P::InformesVerGeneral, P::InformesVerEnfermeria, P::InformesVerMedico, P::InformesVerFisioterapia,
                P::InformesCrearEnfermeria, P::InformesCrearMedico, P::InformesCrearGeneral,
                P::FamiliaresVer, P::FamiliaresVerContacto, P::FamiliaresCrear, P::FamiliaresEditar, P::FamiliaresVincular,
                P::DietasEditar,
                P::VisitasVer, P::VisitasRegistrar,
                P::HabitacionesVer,
                P::IncidenciasAtenderSanitaria,
                P::CuidadosRegistrar,
                P::ActividadesVer,
            ],
            R::Auxiliar->value => [
                ...$todos, P::DietasVer, ...$historial,
                P::ResidentesVer,
                P::CuidadosRegistrar,
                P::MedicacionVer,
                P::InformesVerGeneral, P::InformesVerEnfermeria, P::InformesVerMedico,
                P::FamiliaresVer,
                P::VisitasVer, P::VisitasRegistrar,
                P::HabitacionesVer,
                P::IncidenciasAtenderSanitaria, P::IncidenciasAtenderLimpieza,
                P::ConstantesRegistrar,
                P::ActividadesVer,
            ],
            R::Recepcionista->value => [
                ...$todos, P::ResidentesVerDocumento,
                P::ResidentesVer,
                P::VisitasMedicasVer, P::VisitasMedicasGestionar,
                P::FamiliaresVer, P::FamiliaresVerContacto, P::FamiliaresCrear, P::FamiliaresEditar, P::FamiliaresVincular,
                P::VisitasVer, P::VisitasRegistrar, P::VisitasEditar,
                P::HabitacionesVer, P::HabitacionesMantenimiento,
                P::IncidenciasAtenderInfraestructura, P::IncidenciasAtenderLimpieza, P::IncidenciasAtenderOtros,
            ],
            R::Psicologo->value => [
                ...$todos, ...$historial,
                P::ResidentesVer,
                P::DiagnosticosEditar,
                P::InformesVerGeneral, P::InformesVerEnfermeria, P::InformesVerMedico, P::InformesVerPsicologico,
                P::InformesCrearPsicologico, P::InformesCrearGeneral,
                P::FamiliaresVer, P::FamiliaresVerContacto, P::FamiliaresCrear, P::FamiliaresEditar, P::FamiliaresVincular,
                P::VisitasVer,
                P::HabitacionesVer,
                P::IncidenciasAtenderSanitaria, P::IncidenciasAtenderOtros,
                P::ActividadesVer, P::ActividadesGestionar,
            ],
            R::TrabajadorSocial->value => [
                ...$todos, P::ResidentesExportarHistorial, P::ResidentesVerDocumento, ...$historial,
                P::ResidentesVer, P::ResidentesCrear, P::ResidentesEditar,
                P::InformesVerGeneral, P::InformesVerSocial,
                P::InformesCrearSocial, P::InformesCrearGeneral,
                P::FamiliaresVer, P::FamiliaresVerContacto, P::FamiliaresCrear, P::FamiliaresEditar, P::FamiliaresVincular,
                P::VisitasVer,
                P::HabitacionesVer,
                P::IncidenciasAtenderSanitaria, P::IncidenciasAtenderOtros,
                P::ActividadesVer, P::ActividadesGestionar,
            ],
            R::Fisioterapeuta->value => [
                ...$todos, ...$historial,
                P::ResidentesVer,
                P::DiagnosticosEditar, P::VisitasMedicasGestionar,
                P::InformesVerGeneral, P::InformesVerEnfermeria, P::InformesVerMedico, P::InformesVerFisioterapia,
                P::InformesCrearFisioterapia, P::InformesCrearGeneral,
                P::FamiliaresVer,
                P::VisitasVer,
                P::HabitacionesVer,
                P::IncidenciasAtenderSanitaria,
                P::ActividadesVer,
            ],
            R::Cocinero->value => [
                ...$todos, P::DietasVer,
                P::ResidentesVer,
                P::MenusGestionar,
                P::IncidenciasAtenderInfraestructura, P::IncidenciasAtenderLimpieza,
            ],
            R::Mantenimiento->value => [
                ...$todos,
                P::HabitacionesVer, P::HabitacionesMantenimiento,
                P::IncidenciasAtenderInfraestructura,
            ],
        ];
    }
}
