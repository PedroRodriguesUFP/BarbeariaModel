@if (session('success'))
    <p class="flash" role="status">{{ session('success') }}</p>
@endif

@if (session('error'))
    <div class="errors" role="alert">
        <strong>{{ session('error') }}</strong>
    </div>
@endif

@if (($showErrors ?? true) && $errors->any())
    <div class="errors" role="alert">
        <strong>Corrija os campos abaixo:</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
