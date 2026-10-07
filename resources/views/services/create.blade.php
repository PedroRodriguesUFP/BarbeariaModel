<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Create Service</title>
</head>
<body>

<h1>Create Service</h1>

@if ($errors->any())
    <ul>
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@endif

<form action="{{ route('services.store') }}" method="POST">
    @csrf

    <label>Name:</label>
    <input type="text" name="name" value="{{ old('name') }}">
    <br><br>

    <label>Description:</label>
    <textarea name="description">{{ old('description') }}</textarea>
    <br><br>

    <label>Price:</label>
    <input type="number" step="0.01" name="price" value="{{ old('price') }}">
    <br><br>

    <label>Duration:</label>
    <input type="number" name="duration_minutes" value="{{ old('duration_minutes') }}">
    <br><br>

    <label>Category:</label>
    <select name="category">
        <option value="hair">Hair</option>
        <option value="beard">Beard</option>
        <option value="hair and beard">Hair and Beard</option>
        <option value="fade">Fade</option>
        <option value="coloring">Coloring</option>
    </select>
    <br><br>

    <button type="submit">Create</button>
</form>

<br>

<a href="{{ route('services.index') }}">Back</a>

</body>
</html>