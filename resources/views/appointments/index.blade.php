@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="md:flex md:items-center md:justify-between mb-8 pb-4 border-b border-neutral-800">
        <div>
            <h2 class="text-3xl font-bold text-white tracking-tight">Marcações</h2>
            <p class="mt-1 text-sm text-gray-400">Consulte e gira todas as marcações registadas.</p>
        </div>
        <div class="mt-4 md:mt-0">
            <a href="{{ route('appointments.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md text-sm font-semibold text-neutral-950 bg-amber-500 hover:bg-amber-400 transition-colors uppercase tracking-wider">
                + Nova Marcação
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-6 bg-green-900/40 border border-green-500 text-green-300 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-neutral-950 shadow-xl rounded-xl overflow-hidden border border-neutral-800">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-neutral-800">
                <thead class="bg-neutral-900">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Data e Hora</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Serviço</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Barbeiro</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Estado</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-400 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-800 bg-neutral-950">
                    @forelse ($appointments as $appointment)
                    <tr class="hover:bg-neutral-900/60 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-200">
                            {{ \Carbon\Carbon::parse($appointment->appointment_time)->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-white font-medium">
                            {{ $appointment->service->name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                            {{ $appointment->barber->name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @php
                                $statusLabel = match ($appointment->status?->value ?? null) {
                                    'pending' => 'Pendente',
                                    'confirmed' => 'Confirmado',
                                    'cancelled' => 'Cancelado',
                                    'completed' => 'Concluído',
                                    default => 'Pendente',
                                };
                            @endphp
                            <span class="px-2.5 py-1 inline-flex text-xs font-semibold rounded-full bg-neutral-800 text-amber-400 border border-amber-500/30">
                                {{ $statusLabel }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                            <a href="{{ route('appointments.show', $appointment->id) }}" class="text-indigo-400 hover:text-indigo-300">Ver</a>
                            <a href="{{ route('appointments.edit', $appointment->id) }}" class="text-amber-500 hover:text-amber-400">Editar</a>
                            <form action="{{ route('appointments.destroy', $appointment->id) }}" method="POST" class="inline-block">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-400" onclick="return confirm('Tem a certeza que deseja cancelar?')">Cancelar</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500 text-sm">
                            Nenhuma marcação encontrada.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection