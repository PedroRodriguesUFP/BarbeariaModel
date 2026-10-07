<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Services</title>
</head>
<body>

    <h1>Services</h1>

    <a href="{{ route('services.create') }}">Create Service</a>

    @if (session('success'))
        <p>{{ session('success') }}</p>
    @endif

    <table border="1">
        <thead>
            <tr>
                <th>Name</th>
                <th>Price</th>
                <th>Duration</th>
                <th>Category</th>
                <th>Actions</th>
            </tr>
        </thead>

        <tbody>
            @foreach ($services as $service)
                <tr>
                    <td>{{ $service->name }}</td>
                    <td>{{ $service->price }} €</td>
                    <td>{{ $service->duration_minutes }} min</td>
                    <td>{{ $service->category->value }}</td>

                    <td>
                        <a href="{{ route('services.show', $service) }}">
                            View
                        </a>

                        <a href="{{ route('services.edit', $service) }}">
                            Edit
                        </a>

                        <form
                            action="{{ route('services.destroy', $service) }}"
                            method="POST"
                            style="display:inline;"
                        >
                            @csrf
                            @method('DELETE')

                            <button type="submit">
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>