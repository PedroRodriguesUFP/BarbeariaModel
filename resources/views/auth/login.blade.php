@extends('layouts.app')

@section('content')
<div class="max-w-md mx-auto py-12 px-4">

    <h1 class="text-2xl font-bold mb-6">
        Iniciar sessão
    </h1>

    @if ($errors->any())
        <div class="mb-4 rounded border border-red-300 bg-red-50 p-3 text-red-800">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        action="{{ route('login') }}"
        method="POST"
        class="space-y-4"
    >
        @csrf

        <div>
            <label for="email" class="block mb-1 font-medium">
                Email
            </label>

            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                required
                autofocus
                class="w-full rounded border border-gray-300 px-3 py-2 text-black"
            >
        </div>

        <div>
            <label for="password" class="block mb-1 font-medium">
                Palavra-passe
            </label>

            <input
                id="password"
                name="password"
                type="password"
                required
                class="w-full rounded border border-gray-300 px-3 py-2 text-black"
            >
        </div>

        <button
            type="submit"
            class="w-full rounded bg-amber-500 px-4 py-2 font-semibold text-black hover:bg-amber-400"
        >
            Entrar
        </button>
    </form>

</div>
@endsection