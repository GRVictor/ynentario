<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('users.view');

        $query = User::with('roles');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($roleId = $request->input('role_id')) {
            $query->whereHas('roles', fn ($q) => $q->where('roles.id', $roleId));
        }

        $users = $query->latest('id')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'is_active' => $u->is_active,
                'roles' => $u->roles->map(fn ($r) => ['id' => $r->id, 'name' => $r->name, 'slug' => $r->slug]),
                'last_login_at' => $u->last_login_at?->format('d/m/Y H:i') ?? 'Nunca',
                'created_at' => $u->created_at->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Users/Index', [
            'users' => $users,
            'roles' => Role::all(),
            'filters' => $request->only(['search', 'role_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('users.create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::defaults()],
            'role_id' => ['required', 'exists:roles,id'],
            'is_active' => ['boolean'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $user->roles()->sync([$validated['role_id']]);

        ActivityLogger::log('user_created', "Usuario '{$user->name}' ({$user->email}) creado.", $user);

        return redirect()->route('users.index')->with('success', 'Usuario creado correctamente.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('users.update');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', Password::defaults()],
            'role_id' => ['required', 'exists:roles,id'],
            'is_active' => ['boolean'],
        ]);

        // Validación de seguridad: Evitar desactivar al último administrador activo
        if ($user->hasRole('admin') && ! $request->boolean('is_active')) {
            $otherActiveAdmins = User::where('id', '!=', $user->id)
                ->where('is_active', true)
                ->whereHas('roles', fn ($q) => $q->where('slug', 'admin'))
                ->count();

            if ($otherActiveAdmins === 0) {
                throw ValidationException::withMessages([
                    'is_active' => ['No puedes desactivar al único administrador activo del sistema.'],
                ]);
            }
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->is_active = $request->boolean('is_active', true);

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();
        $user->roles()->sync([$validated['role_id']]);

        ActivityLogger::log('user_updated', "Usuario '{$user->name}' actualizado.", $user);

        return redirect()->route('users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('users.delete');

        // Comprobar que el usuario no intente eliminarse a sí mismo
        if ($user->id === $request->user()->id) {
            throw ValidationException::withMessages([
                'user' => ['No puedes eliminar tu propia cuenta de usuario.'],
            ]);
        }

        // Validación de seguridad: Evitar eliminar al único administrador existente
        if ($user->hasRole('admin')) {
            $otherAdmins = User::where('id', '!=', $user->id)
                ->whereHas('roles', fn ($q) => $q->where('slug', 'admin'))
                ->count();

            if ($otherAdmins === 0) {
                throw ValidationException::withMessages([
                    'user' => ['No es posible eliminar al único administrador del sistema.'],
                ]);
            }
        }

        $name = $user->name;
        $user->roles()->detach();
        $user->delete();

        ActivityLogger::log('user_deleted', "Usuario '{$name}' eliminado del sistema.");

        return redirect()->route('users.index')->with('success', "Usuario '{$name}' eliminado correctamente.");
    }
}
