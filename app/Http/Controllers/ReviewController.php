<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Review::class);

        $reviews = Review::with([
            'reviewer',
            'reviewable',
        ])->get();

        return view(
            'reviews.index',
            compact('reviews')
        );
    }

    public function create()
    {
        $this->authorize('create', Review::class);

        return view('reviews.create');
    }

    public function store(Request $request)
    {
        $this->authorize('create', Review::class);

        $request->validate([
            'reviewable_type' => [
                'required',
                Rule::in([
                    User::class,
                    Service::class,
                ]),
            ],

            'reviewable_id' => [
                'required',
                'integer',
                'min:1',
            ],

            'rating' => [
                'required',
                'integer',
                'min:1',
                'max:5',
            ],

            'comment' => [
                'nullable',
                'string',
            ],
        ]);

        $reviewableType = $request->input('reviewable_type');

        $reviewable = $reviewableType::find(
            $request->input('reviewable_id')
        );

        if (! $reviewable) {
            throw ValidationException::withMessages([
                'reviewable_id' =>
                    'O barbeiro ou serviço selecionado não existe.',
            ]);
        }

        // Se o alvo for um User, tem obrigatoriamente de ser barbeiro.
        if (
            $reviewable instanceof User &&
            ! $reviewable->isBarber()
        ) {
            throw ValidationException::withMessages([
                'reviewable_id' =>
                    'O utilizador selecionado não é barbeiro.',
            ]);
        }

        Review::create([
            'reviewer_id' => auth()->id(),
            'reviewable_type' => $reviewableType,
            'reviewable_id' => $request->input('reviewable_id'),
            'rating' => $request->input('rating'),
            'comment' => $request->input('comment'),
        ]);

        return redirect()
            ->route('reviews.index')
            ->with(
                'success',
                'Avaliação criada com sucesso.'
            );
    }

    public function show($id)
    {
        $review = Review::with([
            'reviewer',
            'reviewable',
        ])->findOrFail($id);

        $this->authorize('view', $review);

        return view(
            'reviews.show',
            compact('review')
        );
    }

    public function edit($id)
    {
        $review = Review::findOrFail($id);

        $this->authorize('update', $review);

        return view(
            'reviews.edit',
            compact('review')
        );
    }

    public function update(Request $request, $id)
    {
        $review = Review::findOrFail($id);

        $this->authorize('update', $review);

        $request->validate([
            'reviewable_type' => [
                'required',
                Rule::in([
                    User::class,
                    Service::class,
                ]),
            ],

            'reviewable_id' => [
                'required',
                'integer',
                'min:1',
            ],

            'rating' => [
                'required',
                'integer',
                'min:1',
                'max:5',
            ],

            'comment' => [
                'nullable',
                'string',
            ],
        ]);

        $reviewableType = $request->input('reviewable_type');

        $reviewable = $reviewableType::find(
            $request->input('reviewable_id')
        );

        if (! $reviewable) {
            throw ValidationException::withMessages([
                'reviewable_id' =>
                    'O barbeiro ou serviço selecionado não existe.',
            ]);
        }

        if (
            $reviewable instanceof User &&
            ! $reviewable->isBarber()
        ) {
            throw ValidationException::withMessages([
                'reviewable_id' =>
                    'O utilizador selecionado não é barbeiro.',
            ]);
        }

        // reviewer_id nunca muda: mantém-se o autor original.
        $review->update([
            'reviewable_type' => $reviewableType,
            'reviewable_id' => $request->input('reviewable_id'),
            'rating' => $request->input('rating'),
            'comment' => $request->input('comment'),
        ]);

        return redirect()
            ->route('reviews.index')
            ->with(
                'success',
                'Avaliação atualizada com sucesso.'
            );
    }

    public function destroy($id)
    {
        $review = Review::findOrFail($id);

        $this->authorize('delete', $review);

        $review->delete();

        return redirect()
            ->route('reviews.index')
            ->with(
                'success',
                'Avaliação eliminada com sucesso.'
            );
    }
}