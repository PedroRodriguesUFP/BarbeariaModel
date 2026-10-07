<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Service Details</title>
</head>
<body>

<h1>{{ $service->name }}</h1>

<p><strong>Description:</strong> {{ $service->description }}</p>

<p><strong>Price:</strong> {{ $service->price }} €</p>

<p><strong>Duration:</strong> {{ $service->duration_minutes }} min</p>

<p><strong>Category:</strong> {{ $service->category->value }}</p>

<a href="{{ route('services.edit', $service) }}">Edit</a>

<br><br>

<a href="{{ route('services.index') }}">Back</a>

</body>
</html>