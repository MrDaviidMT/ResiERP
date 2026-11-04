<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // El superadministrador no es un empleado: no aparece en la lista
        $query = User::with('roles')->withoutRole(Role::Superadmin);

        $estado = $request->input('estado', 'activos');
        $query->where('activo', $estado !== 'desactivados');

        if ($request->filled('name')) {
            $query->where('name', 'like', '%'.$request->name.'%');
        }

        if ($request->filled('email')) {
            $query->where('email', 'like', '%'.$request->email.'%');
        }

        if ($rol = Role::tryFrom((string) $request->input('role'))) {
            $query->role($rol);
        }

        if ($request->filled('verified')) {
            if ($request->verified === 'si') {
                $query->whereNotNull('email_verified_at');
            } elseif ($request->verified === 'no') {
                $query->whereNull('email_verified_at');
            }
        }

        if ($request->filled('desde')) {
            $query->whereDate('created_at', '>=', $request->desde);
        }

        if ($request->filled('hasta')) {
            $query->whereDate('created_at', '<=', $request->hasta);
        }

        $users = $query->orderBy('name')->paginate(20);

        $roles = Role::deNegocio();

        return view('users.index', compact('users', 'roles'));
    }

    public function show(User $user)
    {
        Gate::authorize('view', $user);

        $user->load('fichajes');

        return view('users.show', compact('user'));
    }

    public function create()
    {
        return view('users.create', ['roles' => $this->rolesAsignables()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
            // El superadministrador nunca se asigna desde la aplicación (tampoco lo puede hacer él mismo)
            'role' => ['required', Rule::enum(Role::class)->except(Role::Superadmin)],
        ]);

        if (Gate::denies('asignarRol', [User::class, Role::from($data['role'])])) {
            return back()->withInput()->with('error', 'No puedes asignar un rol con permisos que tú no tienes.');
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);
        $user->syncRoles([$data['role']]);

        event(new Registered($user));

        return redirect()->route('users.index')->with('success', 'Usuario creado correctamente. Se ha enviado un enlace de verificación a su correo.');
    }

    public function edit(User $user)
    {
        Gate::authorize('update', $user);

        return view('users.edit', ['user' => $user, 'roles' => $this->rolesAsignables()]);
    }

    public function update(Request $request, User $user)
    {
        Gate::authorize('update', $user);

        // Nadie cambia su propio rol: al editarse a sí mismo el campo no se envía
        $esUnoMismo = $user->is($request->user());

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => [Rule::requiredIf(! $esUnoMismo), Rule::prohibitedIf($esUnoMismo), Rule::enum(Role::class)->except(Role::Superadmin)],
        ]);

        if (! $esUnoMismo && $data['role'] !== $user->rol()?->value && Gate::denies('asignarRol', [User::class, Role::from($data['role'])])) {
            return back()->withInput()->with('error', 'No puedes asignar un rol con permisos que tú no tienes.');
        }

        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
        ];

        if ($request->filled('password')) {
            $updateData['password'] = bcrypt($data['password']);
        }

        $user->update($updateData);
        // Solo si cambia: syncRoles retira y vuelve a asignar, y ensuciaría la auditoría
        if (! $esUnoMismo && $data['role'] !== $user->rol()?->value) {
            $user->syncRoles([$data['role']]);
        }

        return redirect()->route('users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function verifyEmail(User $user)
    {
        Gate::authorize('verificarEmail', $user);

        if ($user->hasVerifiedEmail()) {
            return back()->with('info', 'Este usuario ya estaba verificado.');
        }

        $user->markEmailAsVerified();

        return back()->with('success', 'Usuario verificado manualmente.');
    }

    public function desactivar(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes darte de baja a ti mismo.');
        }

        Gate::authorize('desactivar', $user);

        if ($user->esSuperadmin() && User::role(Role::Superadmin)->where('activo', true)->count() <= 1) {
            return back()->with('error', 'No puedes dar de baja al único administrador.');
        }

        // Baja = desactivar: se conservan sus fichajes (registro de jornada), informes y auditoría
        $user->forceFill(['activo' => false])->save();

        return redirect()->route('users.index')->with('success', 'Usuario dado de baja. Ya no puede iniciar sesión.');
    }

    public function reactivar(User $user)
    {
        Gate::authorize('desactivar', $user);

        $user->forceFill(['activo' => true])->save();

        return redirect()->route('users.show', $user)->with('success', 'Usuario reactivado. Ya puede iniciar sesión.');
    }

    /** Roles de negocio que el usuario actual puede asignar (anti-escalada). @return list<Role> */
    private function rolesAsignables(): array
    {
        return array_values(array_filter(Role::deNegocio(), fn (Role $rol) => Gate::allows('asignarRol', [User::class, $rol])));
    }
}
