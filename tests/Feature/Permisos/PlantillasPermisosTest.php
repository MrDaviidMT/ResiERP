<?php

namespace Tests\Feature\Permisos;

use App\Enums\Permission as P;
use App\Enums\Role as R;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PlantillasPermisosTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Gate antiguo => permisos nuevos en los que se divide. Cada permiso nuevo debe tenerlo
     * exactamente el mismo conjunto de roles que tenía el Gate (migración sin cambio de comportamiento).
     *
     * @return array<string, list<P>>
     */
    private function correspondencia(): array
    {
        $mapa = [
            'delete-admin' => [], // solo superadmin
            'edit-familiares' => [P::FamiliaresCrear, P::FamiliaresEditar, P::FamiliaresVincular],
            'edit-habitaciones' => [P::HabitacionesVer, P::HabitacionesEditar],
            'maintenance-habitaciones' => [P::HabitacionesMantenimiento],
            'manage-actividades' => [P::ActividadesVer, P::ActividadesGestionar],
            'manage-daily-care' => [P::CuidadosRegistrar],
            'manage-dieta' => [P::DietasEditar],
            'manage-familiares' => [P::FamiliaresBorrar],
            'manage-habitaciones' => [P::HabitacionesCrear, P::HabitacionesBorrar],
            'manage-historial-alergias' => [P::AlergiasEditar],
            'manage-historial-constantes' => [P::ConstantesRegistrar],
            'manage-historial-diagnosticos' => [P::DiagnosticosEditar],
            'manage-historial-medicacion' => [P::MedicacionVer, P::MedicacionEditar],
            'manage-incidencias' => [P::IncidenciasAnular],
            'manage-menus' => [P::MenusGestionar],
            'manage-residentes' => [P::ResidentesCrear, P::ResidentesEditar, P::ResidentesBaja, P::ResidentesReincorporar],
            'manage-users' => [
                P::EmpleadosVer, P::EmpleadosCrear, P::EmpleadosEditar, P::EmpleadosDesactivar, P::EmpleadosAsignarRoles, P::EmpleadosVerificarEmail,
                P::FichajesVerTodos, P::FichajesCrearOtros, P::FichajesEditar, P::FichajesAnular,
            ],
            'manage-visits' => [P::VisitasMedicasGestionar],
            'view-familiares' => [P::FamiliaresVer],
            'view-historial-alergias' => [P::AlergiasVer],
            'view-historial-constantes' => [P::ConstantesVer],
            'view-historial-cuidados' => [P::CuidadosVer],
            'view-historial-diagnosticos' => [P::DiagnosticosVer],
            'view-historial-informes' => P::verInformes(),
            'view-historial-visitas-medicas' => [P::VisitasMedicasVer],
            'view-incidencias' => [P::IncidenciasVer],
            'view-informes' => P::verInformes(),
            'view-logs' => [P::AuditoriaVer],
            'view-personal-incidencias' => [P::IncidenciasVerPersonal],
            'view-residents' => [P::ResidentesVer],
            'view-visitas' => [P::VisitasVer, P::VisitasRegistrar, P::VisitasAnular],
        ];

        foreach (['psicologico', 'social', 'fisioterapia', 'enfermeria', 'medico', 'general'] as $tipo) {
            $mapa["manage-informes-tipo:{$tipo}"] = [P::crearInforme($tipo)];
        }
        foreach (['sanitaria', 'infraestructura', 'limpieza', 'personal', 'otros'] as $categoria) {
            $mapa["atender-incidencias:{$categoria}"] = [P::atenderIncidencia($categoria)];
        }

        return $mapa;
    }

    // Gates que no se usaban en ningún sitio: no pasan al catálogo nuevo
    private const GATES_SIN_USO = ['manage-historial-familiares', 'view-historial-medicacion'];

    // Gates cuyo equivalente no es un permiso, sino "tener alguno de" (se comprueba aparte)
    private const GATES_COMPUESTOS = ['manage-informes'];

    // Permisos nuevos para acciones que antes no tenían ninguna comprobación (abiertas a todos)
    private const PERMISOS_ANTES_ABIERTOS = [P::IncidenciasCrear, P::DietasVer, P::MenusVer, P::FichajesRegistrarPropios, P::FichajesVerPropios];

    /**
     * Cambios respecto al sistema antiguo decididos al definir los permisos.
     * Cualquier diferencia con la foto antigua que no esté aquí hace fallar el test.
     *
     * @return list<array{0: string, 1: R, 2: P, 3: bool}> [decisión, rol, permiso, ¿lo tiene ahora?]
     */
    private function cambiosDecididos(): array
    {
        return [
            ['D1', R::Auxiliar, P::MedicacionVer, true],
            ['D7', R::Director, P::DiagnosticosEditar, false],
            ['D7', R::Director, P::AlergiasEditar, false],
            ['D7', R::Director, P::ConstantesRegistrar, false],
            ['D7', R::Director, P::CuidadosRegistrar, false],
            ['D7', R::Director, P::MedicacionEditar, false],
            ['D7', R::Director, P::DietasEditar, false],
            // D2: cada uno ve los informes según su sensibilidad
            ['D2', R::TrabajadorSocial, P::InformesVerEnfermeria, false],
            ['D2', R::TrabajadorSocial, P::InformesVerMedico, false],
            ['D2', R::TrabajadorSocial, P::InformesVerFisioterapia, false],
            ['D2', R::TrabajadorSocial, P::InformesVerPsicologico, false],
            ['D2', R::Enfermero, P::InformesVerSocial, false],
            ['D2', R::Enfermero, P::InformesVerPsicologico, false],
            ['D2', R::Auxiliar, P::InformesVerFisioterapia, false],
            ['D2', R::Auxiliar, P::InformesVerSocial, false],
            ['D2', R::Auxiliar, P::InformesVerPsicologico, false],
            ['D2', R::Psicologo, P::InformesVerFisioterapia, false],
            ['D2', R::Psicologo, P::InformesVerSocial, false],
            ['D2', R::Fisioterapeuta, P::InformesVerSocial, false],
            ['D2', R::Fisioterapeuta, P::InformesVerPsicologico, false],
            // D2 + D7: el director solo crea informes generales y es quien borra
            ['D2', R::Director, P::InformesCrearEnfermeria, false],
            ['D2', R::Director, P::InformesCrearMedico, false],
            ['D2', R::Director, P::InformesCrearFisioterapia, false],
            ['D2', R::Director, P::InformesCrearSocial, false],
            ['D2', R::Director, P::InformesCrearPsicologico, false],
            ['D2', R::Director, P::InformesAnular, true],
            // Editar visitas (la pantalla ya existía): dirección y recepción
            ['D4', R::Director, P::VisitasEditar, true],
            ['D4', R::Recepcionista, P::VisitasEditar, true],
            // D4: registrar visitas, quien está en la puerta o con el residente; borrar, solo dirección
            ['D4', R::Psicologo, P::VisitasRegistrar, false],
            ['D4', R::TrabajadorSocial, P::VisitasRegistrar, false],
            ['D4', R::Fisioterapeuta, P::VisitasRegistrar, false],
            ['D4', R::Enfermero, P::VisitasAnular, false],
            ['D4', R::Auxiliar, P::VisitasAnular, false],
            ['D4', R::Recepcionista, P::VisitasAnular, false],
            ['D4', R::Psicologo, P::VisitasAnular, false],
            ['D4', R::TrabajadorSocial, P::VisitasAnular, false],
            ['D4', R::Fisioterapeuta, P::VisitasAnular, false],
            // D3: editar habitaciones, solo dirección
            ['D3', R::Enfermero, P::HabitacionesEditar, false],
            ['D3', R::Auxiliar, P::HabitacionesEditar, false],
            ['D3', R::Recepcionista, P::HabitacionesEditar, false],
            ['D3', R::Psicologo, P::HabitacionesEditar, false],
            ['D3', R::TrabajadorSocial, P::HabitacionesEditar, false],
            ['D3', R::Fisioterapeuta, P::HabitacionesEditar, false],
            ['D3', R::Mantenimiento, P::HabitacionesEditar, false],
            // D5: enfermería registra cuidados y los auxiliares registran constantes
            ['D5', R::Enfermero, P::CuidadosRegistrar, true],
            ['D5', R::Auxiliar, P::ConstantesRegistrar, true],
            // D6: alta y edición de residentes, Dir y TS; baja y reincorporación, solo Dir
            ['D6', R::Enfermero, P::ResidentesCrear, false],
            ['D6', R::Enfermero, P::ResidentesEditar, false],
            ['D6', R::Enfermero, P::ResidentesBaja, false],
            ['D6', R::Enfermero, P::ResidentesReincorporar, false],
            ['D6', R::Psicologo, P::ResidentesCrear, false],
            ['D6', R::Psicologo, P::ResidentesEditar, false],
            ['D6', R::Psicologo, P::ResidentesBaja, false],
            ['D6', R::Psicologo, P::ResidentesReincorporar, false],
            ['D6', R::TrabajadorSocial, P::ResidentesBaja, false],
            ['D6', R::TrabajadorSocial, P::ResidentesReincorporar, false],
            // D8: enfermería, auxiliares y fisioterapia ven las actividades
            ['D8', R::Enfermero, P::ActividadesVer, true],
            ['D8', R::Auxiliar, P::ActividadesVer, true],
            ['D8', R::Fisioterapeuta, P::ActividadesVer, true],
            // D10: borrar familiares, solo dirección
            ['D10', R::Enfermero, P::FamiliaresBorrar, false],
            ['D10', R::Psicologo, P::FamiliaresBorrar, false],
            ['D10', R::TrabajadorSocial, P::FamiliaresBorrar, false],
            // Fallo 11 y matriz 4.6: ven las dietas quien da de comer o cocina
            ['F11', R::Recepcionista, P::DietasVer, false],
            ['F11', R::Psicologo, P::DietasVer, false],
            ['F11', R::TrabajadorSocial, P::DietasVer, false],
            ['F11', R::Fisioterapeuta, P::DietasVer, false],
            ['F11', R::Mantenimiento, P::DietasVer, false],
            // L1 (auditoría): grupo sanguíneo y dependencia, solo personal asistencial
            ['L1', R::Director, P::ResidentesVerSalud, true],
            ['L1', R::Enfermero, P::ResidentesVerSalud, true],
            ['L1', R::Auxiliar, P::ResidentesVerSalud, true],
            ['L1', R::Psicologo, P::ResidentesVerSalud, true],
            ['L1', R::TrabajadorSocial, P::ResidentesVerSalud, true],
            ['L1', R::Fisioterapeuta, P::ResidentesVerSalud, true],
            // Anular registros clínicos, mismos roles que pueden editarlos
            ['ADR1', R::Enfermero, P::DiagnosticosAnular, true],
            ['ADR1', R::Psicologo, P::DiagnosticosAnular, true],
            ['ADR1', R::Fisioterapeuta, P::DiagnosticosAnular, true],
            ['ADR1', R::Enfermero, P::AlergiasAnular, true],
            ['ADR1', R::Enfermero, P::ConstantesAnular, true],
            ['ADR1', R::Auxiliar, P::ConstantesAnular, true],
            ['ADR1', R::Enfermero, P::CuidadosAnular, true],
            ['ADR1', R::Auxiliar, P::CuidadosAnular, true],
            ['ADR1', R::Director, P::VisitasMedicasAnular, true],
            ['ADR1', R::Enfermero, P::VisitasMedicasAnular, true],
            ['ADR1', R::Recepcionista, P::VisitasMedicasAnular, true],
            ['ADR1', R::Fisioterapeuta, P::VisitasMedicasAnular, true],
            // L7 (auditoría): minimización. El DNI del residente y los datos de contacto de los
            // familiares solo para quien los necesita por su puesto
            // L4 (auditoría): entregar copia de la historia clínica es un trámite de dirección y trabajo social
            ['L4', R::Director, P::ResidentesExportarHistorial, true],
            ['L4', R::TrabajadorSocial, P::ResidentesExportarHistorial, true],
            ['L7', R::Director, P::ResidentesVerDocumento, true],
            ['L7', R::Enfermero, P::ResidentesVerDocumento, true],
            ['L7', R::Recepcionista, P::ResidentesVerDocumento, true],
            ['L7', R::TrabajadorSocial, P::ResidentesVerDocumento, true],
            ['L7', R::Director, P::FamiliaresVerContacto, true],
            ['L7', R::Enfermero, P::FamiliaresVerContacto, true],
            ['L7', R::Recepcionista, P::FamiliaresVerContacto, true],
            ['L7', R::Psicologo, P::FamiliaresVerContacto, true],
            ['L7', R::TrabajadorSocial, P::FamiliaresVerContacto, true],
            ['ADR1', R::Enfermero, P::MedicacionRetirar, true],
            ['ADR1', R::Enfermero, P::MedicacionAnular, true],
        ];
    }

    private function esperado(R $rol, P $permiso, bool $antes): bool
    {
        foreach ($this->cambiosDecididos() as [, $r, $p, $ahora]) {
            if ($r === $rol && $p === $permiso) {
                return $ahora;
            }
        }

        return $antes;
    }

    public function test_role_templates_match_legacy_gates(): void
    {
        $this->seed(PermissionSeeder::class);
        $legacy = require base_path('tests/Fixtures/legacy_gates.php');
        $mapa = $this->correspondencia();

        foreach ($legacy as $gate => $rolesConAcceso) {
            if (in_array($gate, [...self::GATES_SIN_USO, ...self::GATES_COMPUESTOS], true)) {
                continue;
            }
            $this->assertArrayHasKey($gate, $mapa, "Falta la correspondencia del Gate {$gate}");

            foreach (R::deNegocio() as $rol) {
                $antes = in_array($rol->value, $rolesConAcceso, true);
                foreach ($mapa[$gate] as $permiso) {
                    $this->assertSame($this->esperado($rol, $permiso, $antes), Role::findByName($rol->value)->hasPermissionTo($permiso->value),
                        "{$rol->value} / {$permiso->value} (antes {$gate})");
                }
            }
        }

        foreach (R::deNegocio() as $rol) {
            $esperado = in_array($rol->value, $legacy['manage-informes'], true);
            $this->assertSame($esperado, Role::findByName($rol->value)->hasAnyPermission(P::values(P::crearInformes())), "{$rol->value} / manage-informes");
        }
    }

    public function test_every_permission_comes_from_a_legacy_gate_or_was_open_to_everyone(): void
    {
        $this->seed(PermissionSeeder::class);
        $cubiertos = collect($this->correspondencia())->flatten()->map(fn (P $p) => $p->value);

        foreach (P::cases() as $permiso) {
            if (in_array($permiso, self::PERMISOS_ANTES_ABIERTOS, true)) {
                foreach (R::deNegocio() as $rol) {
                    $this->assertSame($this->esperado($rol, $permiso, true), Role::findByName($rol->value)->hasPermissionTo($permiso->value), "{$rol->value} / {$permiso->value}");
                }

                continue;
            }

            // Permisos creados por una decisión: cada rol debe coincidir exactamente con lo decidido
            $decididos = array_filter($this->cambiosDecididos(), fn ($c) => $c[2] === $permiso);
            if ($decididos && ! $cubiertos->contains($permiso->value)) {
                foreach (R::deNegocio() as $rol) {
                    $this->assertSame($this->esperado($rol, $permiso, false), Role::findByName($rol->value)->hasPermissionTo($permiso->value), "{$rol->value} / {$permiso->value}");
                }

                continue;
            }
            $this->assertTrue($cubiertos->contains($permiso->value), "{$permiso->value} no sale de ningún Gate");
        }
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(PermissionSeeder::class);
        $antes = [Permission::count(), Role::count(), Role::findByName(R::Director->value)->permissions()->count()];

        $this->seed(PermissionSeeder::class);

        $this->assertSame($antes, [Permission::count(), Role::count(), Role::findByName(R::Director->value)->permissions()->count()]);
        $this->assertSame(count(P::cases()), Permission::count());
    }

    /**
     * El seeder no debe depender de la caché de permisos de spatie: con el almacén de caché en
     * base de datos puede quedar guardado un conjunto vacío, y antes el seeder fallaba
     * ("There is no permission named `incidencias.ver`"). Detectado al probar en MySQL real.
     */
    public function test_seeder_works_with_a_stale_empty_permission_cache(): void
    {
        Permission::query()->delete();
        Role::query()->delete();

        // Fuerza el guardado de un conjunto vacío en la caché, como ocurría en el despliegue
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();
        $this->assertCount(0, $registrar->getPermissions());

        $this->seed(PermissionSeeder::class);

        $this->assertSame(count(P::cases()), Permission::count());
        $this->assertTrue(Role::findByName(R::Director->value)->hasPermissionTo(P::IncidenciasVer->value));
    }
}
