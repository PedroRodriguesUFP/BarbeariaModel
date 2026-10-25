<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->query('role');
        $q = trim((string) $request->query('q', ''));

        $users = User::query()
            ->when(
                in_array($role, array_column(UserRole::cases(), 'value'), true),
                fn ($query) => $query->where('role', $role)
            )
            ->when($q !== '', fn ($query) => $query->where(
                fn ($w) => $w->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
            ))
            ->orderBy('name')
            ->get();

        return view('admin.users.index', [
            'users' => $users,
            'role' => $role,
            'q' => $q,
            'roles' => UserRole::cases(),
        ]);
    }

    public function create()
    {
        return view('admin.users.create', ['roles' => UserRole::cases()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => ['required', Rule::enum(UserRole::class)],
        ]);

        User::create($data + ['active' => true]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Conta criada.');
    }

    // Muda role e/ou estado ativo. Nunca na própria conta.
    public function update(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors([
                'user' => 'Não podes alterar a tua própria conta aqui.',
            ]);
        }

        $data = $request->validate([
            'role' => ['sometimes', Rule::enum(UserRole::class)],
            'active' => ['sometimes', 'boolean'],
        ]);

        $user->update($data);

        return back()->with('success', 'Conta atualizada.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors([
                'user' => 'Não podes eliminar a tua própria conta.',
            ]);
        }

        // Apagar levava marcações e pagamentos atrás (cascade).
        if (
            $user->appointmentsAsCustomer()->exists()
            || $user->appointmentsAsBarber()->exists()
        ) {
            return back()->withErrors([
                'user' => 'Esta conta tem marcações. Desativa em vez de eliminar.',
            ]);
        }

        $user->delete();

        return back()->with('success', 'Conta eliminada.');
    }
}
