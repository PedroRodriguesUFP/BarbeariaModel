<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class BarberController extends Controller
{
    /**
     * Lista todos os barbeiros da plataforma.
     */
    public function index()
    {
        $barbers = User::where('role', UserRole::Barber->value)
            // Trazemos a contagem de marcações e serviços para exibir na tabela de forma rápida
            ->withCount(['appointmentsAsBarber', 'services'])
            ->orderBy('name')
            ->get();

        return view('admin.barbers.index', compact('barbers'));
    }

    /**
     * Mostra o formulário para adicionar um novo barbeiro.
     */
    public function create()
    {
        return view('admin.barbers.create', [
            // Passamos os serviços para podermos associar as especialidades do barbeiro
            'services' => Service::orderBy('name')->get(),
        ]);
    }

    /**
     * Regista um novo barbeiro na base de dados.
     */
    public function store(Request $request)
    {
        // 1. Validar os dados recebidos do formulário
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'         => ['nullable', 'string', 'max:30'],
            'bio'           => ['nullable', 'string', 'max:2000'],
            'password'      => ['required', 'confirmed', Password::min(8)],
            'services'      => ['nullable', 'array'],
            'services.*'    => ['exists:services,id'],
            'default_hours' => ['nullable', 'boolean'],
        ]);

        // 2. Criar o utilizador já com a permissão (role) de barbeiro
        $barber = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'phone'    => $data['phone'] ?? null,
            'bio'      => $data['bio'] ?? null,
            'password' => $data['password'], // Assume-se que o model User faz o cast 'hashed'
            'role'     => UserRole::Barber->value,
            'active'   => true,
        ]);

        // 3. Associar os serviços que este barbeiro sabe fazer
        $barber->services()->sync($data['services'] ?? []);

        // 4. Configurar horário de trabalho padrão, se solicitado
        if ($request->boolean('default_hours')) {
            $this->createDefaultHours($barber);
        }

        return redirect()
            ->route('admin.barbers.index')
            ->with('success', 'Barbeiro criado com sucesso.');
    }

    /**
     * Mostra o formulário de edição de um barbeiro.
     */
    public function edit(User $barber)
    {
        $this->ensureBarber($barber);

        return view('admin.barbers.edit', [
            // O "load" garante que já enviamos os serviços atuais do barbeiro para a vista
            'barber'   => $barber->load('services'),
            'services' => Service::orderBy('name')->get(),
        ]);
    }

    /**
     * Atualiza os dados de um barbeiro existente.
     */
    public function update(Request $request, User $barber)
    {
        $this->ensureBarber($barber);

        $data = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            // Permite manter o mesmo email ignorando a regra 'unique' para o próprio ID
            'email'      => ['required', 'email', 'max:255', Rule::unique('users')->ignore($barber->id)],
            'phone'      => ['nullable', 'string', 'max:30'],
            'bio'        => ['nullable', 'string', 'max:2000'],
            'password'   => ['nullable', 'confirmed', Password::min(8)],
            'services'   => ['nullable', 'array'],
            'services.*' => ['exists:services,id'],
            'active'     => ['nullable', 'boolean'],
        ]);

        // Atualizar dados base
        $barber->fill([
            'name'   => $data['name'],
            'email'  => $data['email'],
            'phone'  => $data['phone'] ?? null,
            'bio'    => $data['bio'] ?? null,
            'active' => $request->boolean('active'),
        ]);

        // Atualizar password apenas se o administrador preencheu esse campo
        if (!empty($data['password'])) {
            $barber->password = $data['password'];
        }

        $barber->save();
        $barber->services()->sync($data['services'] ?? []);

        return redirect()
            ->route('admin.barbers.index')
            ->with('success', 'Barbeiro atualizado com sucesso.');
    }

    /**
     * Remove um barbeiro da plataforma.
     */
    public function destroy(User $barber)
    {
        $this->ensureBarber($barber);

        // Proteção essencial: não podemos apagar quem já tem histórico.
        // Apagar forçaria a exclusão em cascata de pagamentos/marcações, arruinando a faturação e o histórico.
        if ($barber->appointmentsAsBarber()->exists()) {
            return back()->withErrors([
                'barber' => 'Este barbeiro já possui histórico de marcações. Por favor, desativa a conta em vez de a eliminar.',
            ]);
        }

        $barber->delete();

        return redirect()
            ->route('admin.barbers.index')
            ->with('success', 'Barbeiro eliminado definitivamente.');
    }

    /**
     * Garante que o utilizador a ser manipulado é efetivamente um barbeiro.
     * Impede que um admin edite clientes ou outros admins por este endpoint.
     */
    private function ensureBarber(User $user): void
    {
        abort_unless($user->role === UserRole::Barber->value, 404);
    }

    /**
     * Gera um horário de trabalho padrão (Segunda a Sexta: 09-13h / 14-18h | Sábado: 09-13h).
     */
    private function createDefaultHours(User $barber): void
    {
        // Dias 1 a 5 (Segunda a Sexta-feira)
        for ($day = 1; $day <= 5; $day++) {
            $barber->workingHours()->createMany([
                ['day_of_week' => $day, 'start_time' => '09:00', 'end_time' => '13:00'],
                ['day_of_week' => $day, 'start_time' => '14:00', 'end_time' => '18:00'],
            ]);
        }

        // Dia 6 (Sábado) apenas manhãs
        $barber->workingHours()->create([
            'day_of_week' => 6, 
            'start_time'  => '09:00', 
            'end_time'    => '13:00'
        ]);
    }
}