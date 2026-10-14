@extends('layouts.app')

@section('content')
<div class="relative overflow-hidden min-h-[calc(100vh-4rem)] flex items-center justify-center">
    <div class="absolute inset-0 z-0 opacity-25 bg-[url('https://images.unsplash.com/photo-1503951914875-452162b0f3f1?q=80&w=2070')] bg-cover bg-center"></div>
    <div class="absolute inset-0 bg-gradient-to-t from-neutral-900 via-neutral-900/80 to-transparent z-0"></div>

    <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center py-12">
        <h1 class="text-4xl sm:text-6xl font-extrabold tracking-tight text-white uppercase">
            Estilo, Precisão & <span class="text-amber-500">Tradição</span>
        </h1>
        <p class="mt-4 text-lg sm:text-xl text-gray-300 max-w-2xl mx-auto">
            Gere as tuas marcações, consulta os serviços disponíveis e acompanha o teu histórico de forma simples e rápida.
        </p>

        <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('appointments.create') }}" class="inline-flex items-center justify-center px-8 py-3 border border-transparent text-base font-semibold rounded-md text-neutral-950 bg-amber-500 hover:bg-amber-400 transition-colors shadow-lg shadow-amber-500/20 uppercase tracking-wider">
                Marcar Horário
            </a>
            <a href="{{ route('services.index') }}" class="inline-flex items-center justify-center px-8 py-3 border border-neutral-700 text-base font-medium rounded-md text-white hover:bg-neutral-800 transition-colors">
                Ver Serviços
            </a>
        </div>
    </div>
</div>
@endsection