<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar avaliação</title>
</head>

<body>

<main>

    <h1>Editar avaliação</h1>

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form
        action="{{ route('reviews.update', $review) }}"
        method="POST"
    >
        @csrf
        @method('PUT')

        <div>
            <label for="reviewer_id">
                ID do utilizador
            </label>

            <input
                id="reviewer_id"
                name="reviewer_id"
                type="number"
                value="{{ old(
                    'reviewer_id',
                    $review->reviewer_id
                ) }}"
                required
            >
        </div>

        <div>
            <label for="reviewable_type">
                Avaliação sobre
            </label>

            <select
                id="reviewable_type"
                name="reviewable_type"
                required
            >
                <option
                    value="App\Models\User"
                    @selected(
                        old(
                            'reviewable_type',
                            $review->reviewable_type
                        ) === 'App\Models\User'
                    )
                >
                    Barbeiro
                </option>

                <option
                    value="App\Models\Service"
                    @selected(
                        old(
                            'reviewable_type',
                            $review->reviewable_type
                        ) === 'App\Models\Service'
                    )
                >
                    Serviço
                </option>
            </select>
        </div>

        <div>
            <label for="reviewable_id">
                ID do barbeiro ou serviço
            </label>

            <input
                id="reviewable_id"
                name="reviewable_id"
                type="number"
                value="{{ old(
                    'reviewable_id',
                    $review->reviewable_id
                ) }}"
                required
            >
        </div>

        <div>
            <label for="rating">
                Classificação
            </label>

            <select
                id="rating"
                name="rating"
                required
            >
                @foreach (range(1, 5) as $rating)
                    <option
                        value="{{ $rating }}"
                        @selected(
                            (string) old(
                                'rating',
                                $review->rating
                            ) === (string) $rating
                        )
                    >
                        {{ $rating }} / 5
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="comment">
                Comentário
            </label>

            <textarea
                id="comment"
                name="comment"
            >{{ old('comment', $review->comment) }}</textarea>
        </div>

        <button type="submit">
            Guardar alterações
        </button>

        <a href="{{ route('reviews.index') }}">
            Voltar
        </a>

    </form>

</main>

</body>
</html>