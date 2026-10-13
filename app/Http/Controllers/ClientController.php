<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    // Lista todos os clientes.
    public function index()
    {
        $clients = Client::with('user')->get();

        return view('clients.index', compact('clients'));
    }

    // Apresenta o formulário de criação.
    public function create()
    {
        return view('clients.create');
    }

    // Valida e guarda um novo cliente.
    public function store(Request $request)
    {
        // valida se o formulário é válido
        $request->validate([
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->where(
                    'role',
                    UserRole::Client->value
                ),
                'unique:clients,user_id',
            ],
            'phone' => 'nullable|string|max:255',
        ]);

        Client::create([
            'user_id' => $request->input('user_id'),
            'phone' => $request->input('phone'),
        ]);

        return redirect()
            ->route('clients.index')
            ->with('success', 'Client created successfully.');
    }

    // Apresenta um cliente específico.
    public function show($id)
    {
        $client = Client::with('user')->find($id);

        if (! $client) {
            throw new \Exception(
                'Client not found, please check if the Client exists and try again.'
            );
        }

        return view('clients.show', compact('client'));
    }

    // Apresenta o formulário de edição.
    public function edit($id)
    {
        $client = Client::find($id);

        if (! $client) {
            throw new \Exception(
                'Client not found, please check if the Client exists and try again.'
            );
        }

        return view('clients.edit', compact('client'));
    }

    // Valida e atualiza um cliente.
    public function update(Request $request, $id)
    {
        $request->validate([
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->where(
                    'role',
                    UserRole::Client->value
                ),
                Rule::unique('clients', 'user_id')->ignore($id),
            ],
            'phone' => 'nullable|string|max:255',
        ]);

        $client = Client::find($id);

        if ($client) {
            $client->update([
                'user_id' => $request->input('user_id'),
                'phone' => $request->input('phone'),
            ]);

            return redirect()
                ->route('clients.index')
                ->with('success', 'Client updated successfully.');
        } else {
            throw new \Exception(
                'Client not found, please check if the Client exists and try again.'
            );
        }
    }

    // Apaga um cliente.
    public function destroy($id)
    {
        $client = Client::find($id);

        if ($client) {
            $client->delete();

            return redirect()
                ->route('clients.index')
                ->with('success', 'Client deleted successfully.');
        } else {
            throw new \Exception(
                'Client not found, please check if the Client exists and try again.'
            );
        }
    }
}