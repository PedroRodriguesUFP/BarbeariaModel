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
    /**
     * Lista de utilizadores, permitindo pesquisa por nome/email e filtro por perfil (role).
     */
    public function index(Request $request)
    {
        $role = $request->query('role');
        
        // Uso do helper 'string()' do Laravel para um trim mais elegante e seguro
        $searchQuery = $request->string('q')->trim();

        $users = User::query()
            // Valida se a 'role' pedida no URL existe mesmo no nosso Enum antes de filtrar
            ->when(
                UserRole::tryFrom($role),
                fn ($query) => $query->where('role', $role)
            )
            // Filtro de pesquisa livre em nome ou email
            ->when($searchQuery->isNotEmpty(), fn ($query) => $query->where(
                fn ($q) => $q->where('name', 'like', "%{$searchQuery}%")
                             ->orWhere('email', 'like', "%{$searchQuery}%")
            ))
            ->orderBy('name')
            ->get();

        return view('admin.users.index', [
            'users' => $users,
            'role'  => $role,
            'q'     => $searchQuery->value(),
            'roles' => UserRole::cases(),
        ]);
    }

    /**
     * Mostra o formulário para criar um novo utilizador manualmente.
     */
    public function create()
    {
        return view('admin.users.create', [
            'roles' => UserRole::cases()
        ]);
    }

    /**
     * Regista o utilizador na base de dados.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'    => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'role'     => ['required', Rule::enum(UserRole::class)],
        ]);

        // Por defeito, novas contas criadas pelo Admin entram logo ativas
        $data['active'] = true;
        
        User::create($data);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Conta de utilizador criada com sucesso.');
    }

    /**
     * Atualiza o estado ativo ou o perfil de uma conta.
     * Importante: Um administrador nunca pode alterar a sua própria conta por aqui,
     * para evitar cenários onde remove o seu próprio acesso de Admin por acidente.
     */
    public function update(Request $request, User $user)
    {
        // Proteção contra "tiros no próprio pé"
        if ($user->id === $request->user()->id) {
            return back()->withErrors([
                'user' => 'Para tua segurança, não podes alterar as permissões da tua própria conta.',
            ]);
        }

        $data = $request->validate([
            'role'   => ['sometimes', Rule::enum(UserRole::class)],
            'active' => ['sometimes', 'boolean'],
        ]);

        $user->update($data);

        return back()->with('success', 'Conta atualizada com sucesso.');
    }

    /**
     * Remove definitivamente uma conta do sistema, garantindo a integridade dos dados.
     */
    public function destroy(Request $request, User $user)
    {
        // Bloqueio de auto-exclusão
        if ($user->id === $request->user()->id) {
            return back()->withErrors([
                'user' => 'Não podes eliminar a tua própria conta.',
            ]);
        }

        // Proteção de integridade (Alinhado com a Fase 3 de BDAD): 
        // Apagar um utilizador com histórico provocaria um apagar em cascata (cascade delete) 
        // de marcações e faturas. Exigimos a desativação (soft-ban) da conta nestes casos.
        $hasAppointments = $user->appointmentsAsCustomer()->exists() 
                        || $user->appointmentsAsBarber()->exists();

        if ($hasAppointments) {
            return back()->withErrors([
                'user' => 'Esta conta possui um histórico de marcações. Por favor, desativa a conta em vez de a eliminar.',
            ]);
        }

        $user->delete();

        return back()->with('success', 'Conta eliminada definitivamente.');
    }
}