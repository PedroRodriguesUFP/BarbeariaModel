<?php

namespace App\Http\Controllers;

use App\Models\Barber;
use App\Models\Review;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReviewController extends Controller
{
    public function index()
    {
        $reviews = Review::with(['client.user', 'reviewable'])->get();

        return view('reviews.index', compact('reviews'));
    }

    public function create()
    {
        return view('reviews.create');
    }

    public function store(Request $request)
    {
        // valida se o formulário é válido
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'reviewable_type' => [
                'required',
                Rule::in([
                    Barber::class,
                    Service::class,
                ]),
            ],
            'reviewable_id' => 'required|integer|min:1',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        // verifica se o barbeiro ou serviço existe
        $reviewable = $request->input('reviewable_type')::find(
            $request->input('reviewable_id')
        );

        if (! $reviewable) {
            throw new \Exception(
                'The Barber or Service being reviewed does not exist.'
            );
        }

        Review::create([
            'client_id' => $request->input('client_id'),
            'reviewable_type' => $request->input('reviewable_type'),
            'reviewable_id' => $request->input('reviewable_id'),
            'rating' => $request->input('rating'),
            'comment' => $request->input('comment'),
        ]);

        return redirect()
            ->route('reviews.index')
            ->with('success', 'Review created successfully.');
    }

    public function show($id)
    {
        $review = Review::with(['client.user', 'reviewable'])->find($id);

        if (! $review) {
            throw new \Exception(
                'Review not found, please check if the Review exists and try again.'
            );
        }

        return view('reviews.show', compact('review'));
    }

    public function edit($id)
    {
        $review = Review::find($id);

        if (! $review) {
            throw new \Exception(
                'Review not found, please check if the Review exists and try again.'
            );
        }

        return view('reviews.edit', compact('review'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'reviewable_type' => [
                'required',
                Rule::in([
                    Barber::class,
                    Service::class,
                ]),
            ],
            'reviewable_id' => 'required|integer|min:1',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        $review = Review::find($id);

        if (! $review) {
            throw new \Exception(
                'Review not found, please check if the Review exists and try again.'
            );
        }

        $reviewable = $request->input('reviewable_type')::find(
            $request->input('reviewable_id')
        );

        if (! $reviewable) {
            throw new \Exception(
                'The Barber or Service being reviewed does not exist.'
            );
        }

        $review->update([
            'client_id' => $request->input('client_id'),
            'reviewable_type' => $request->input('reviewable_type'),
            'reviewable_id' => $request->input('reviewable_id'),
            'rating' => $request->input('rating'),
            'comment' => $request->input('comment'),
        ]);

        return redirect()
            ->route('reviews.index')
            ->with('success', 'Review updated successfully.');
    }

    public function destroy($id)
    {
        $review = Review::find($id);

        if ($review) {
            $review->delete();

            return redirect()
                ->route('reviews.index')
                ->with('success', 'Review deleted successfully.');
        } else {
            throw new \Exception(
                'Review not found, please check if the Review exists and try again.'
            );
        }
    }
}