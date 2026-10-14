@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="bg-neutral-800 border border-neutral-700 shadow-2xl rounded-xl overflow-hidden">
        <div class="px-6 py-6 border-b border-neutral-700 bg-neutral-800/50">
            <h3 class="text-2xl leading-6 font-bold text-white">Agendar Nova Marcação</h3>
            <p class="mt-2 max-w-2xl text-sm text-gray-400">Selecione o serviço, o seu barbeiro preferido e a hora.</p>
        </div>

        <div class="px-6 py-8">
            <!-- Preservação rigorosa da action e do método -->
            <form action="{{ route('appointments.store') }}" method="POST" class="space-y-6">
                @csrf

                <!-- Serviço -->
                <div>
                    <label for="service_id" class="block text-sm font-medium text-gray-300">Serviço</label>
                    <select id="service_id" name="service_id" required class="mt-1 block w-full pl-3 pr-10 py-3 text-base bg-neutral-900 border-neutral-600 text-white focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm rounded-md transition-colors">
                        <option value="" disabled selected>Escolha um serviço...</option>
                        @foreach($services as $service)
                            <option value="{{ $service->id }}">{{ $service->name }} ({{ $service->price }}€)</option>
                        @endforeach
                    </select>
                    @error('service_id') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Barbeiro -->
                <div>
                    <label for="barber_id" class="block text-sm font-medium text-gray-300">Barbeiro</label>
                    <select id="barber_id" name="barber_id" required class="mt-1 block w-full pl-3 pr-10 py-3 text-base bg-neutral-900 border-neutral-600 text-white focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm rounded-md transition-colors">
                        <option value="" disabled selected>Sem preferência / Escolha um barbeiro...</option>
                        @foreach($barbers as $barber)
                            <option value="{{ $barber->id }}">{{ $barber->name }}</option>
                        @endforeach
                    </select>
                    @error('barber_id') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Data e Hora -->
                <div>
                    <label for="appointment_time" class="block text-sm font-medium text-gray-300">Data e Hora</label>
                    <input type="datetime-local" name="appointment_time" id="appointment_time" required class="mt-1 block w-full py-3 px-3 border border-neutral-600 bg-neutral-900 rounded-md shadow-sm text-white focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm transition-colors" />
                    @error('appointment_time') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Botões de Ação -->
                <div class="pt-5 flex items-center justify-end space-x-4 border-t border-neutral-700 mt-8 pt-6">
                    <a href="{{ route('appointments.index') }}" class="bg-transparent border border-gray-600 py-2 px-4 rounded-md shadow-sm text-sm font-medium text-gray-300 hover:bg-neutral-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-neutral-900 focus:ring-gray-500 transition-colors">
                        Cancelar
                    </a>
                    <button type="submit" class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-neutral-900 bg-amber-500 hover:bg-amber-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-neutral-900 focus:ring-amber-500 transition-colors">
                        Confirmar Marcação
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection