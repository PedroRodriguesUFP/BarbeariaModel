<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Edit Service</title>
</head>
<body>

<h1>Edit Service</h1>

@if ($errors->any())
    <ul>
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@endif

<form action="{{ route('services.update', $service) }}" method="POST">
    @csrf
    @method('PUT')

    <label>Name:</label>
    <input
        type="text"
        name="name"
        value="{{ old('name', $service->name) }}"
    >
    <br><br>

    <label>Description:</label>
    <textarea name="description">{{ old('description', $service->description) }}</textarea>
    <br><br>

    <label>Price:</label>
    <input
        type="number"
        step="0.01"
        name="price"
        value="{{ old('price', $service->price) }}"
    >
    <br><br>

    <label>Duration:</label>
    <input
        type="number"
        name="duration_minutes"
        value="{{ old('duration_minutes', $service->duration_minutes) }}"
    >
    <br><br>

    <label>Category:</label>
    <select name="category">
        <option value="hair" @selected(old('category', $service->category->value) === 'hair')>Hair</option>
        <option value="beard" @selected(old('category', $service->category->value) === 'beard')>Beard</option>
        <option value="hair and beard" @selected(old('category', $service->category->value) === 'hair and beard')>Hair and Beard</option>
        <option value="fade" @selected(old('category', $service->category->value) === 'fade')>Fade</option>
        <option value="coloring" @selected(old('category', $service->category->value) === 'coloring')>Coloring</option>
    </select>
    <br><br>

    <button type="submit">Update</button>
</form>

<br>

<a href="{{ route('services.index') }}">Back</a>

</body>
</html>