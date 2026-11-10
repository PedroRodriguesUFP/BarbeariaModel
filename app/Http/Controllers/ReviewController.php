<?php

namespace App\Http\Controllers;

use App\Enums\Status;
use App\Models\Appointment;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    // Marcações concluídas do cliente que ainda não foram avaliadas.
    public function create(Request $request)
    {
        $this->authorize('create', Review::class);

        $appointments = Appointment::with(['service', 'barber'])
            ->where('customer_id', auth()->id())
            ->where('status', Status::Completed)
            ->whereDoesntHave('reviews')
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->get();

        $selectedId = $request->query('appointment');

        return view('reviews.create', compact('appointments', 'selectedId'));
    }

    // Cria duas avaliações: uma ao barbeiro e outra ao serviço da marcação.
    public function store(Request $request)
    {
        $this->authorize('create', Review::class);

        $data = $request->validate([
            'appointment_id' => ['required', 'integer'],
            'barber_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'barber_comment' => ['nullable', 'string', 'max:1000'],
            'service_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'service_comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $appointment = Appointment::where('customer_id', auth()->id())
            ->where('status', Status::Completed)
            ->find($data['appointment_id']);

        if (! $appointment) {
            throw ValidationException::withMessages([
                'appointment_id' => 'Só podes avaliar marcações tuas já concluídas.',
            ]);
        }

        if ($appointment->reviews()->exists()) {
            throw ValidationException::withMessages([
                'appointment_id' => 'Esta marcação já foi avaliada.',
            ]);
        }

        DB::transaction(function () use ($appointment, $data) {
            Review::create([
                'reviewer_id' => auth()->id(),
                'appointment_id' => $appointment->id,
                'reviewable_type' => (new User)->getMorphClass(),
                'reviewable_id' => $appointment->barber_id,
                'rating' => $data['barber_rating'],
                'comment' => $data['barber_comment'] ?? null,
            ]);

            Review::create([
                'reviewer_id' => auth()->id(),
                'appointment_id' => $appointment->id,
                'reviewable_type' => (new Service)->getMorphClass(),
                'reviewable_id' => $appointment->service_id,
                'rating' => $data['service_rating'],
                'comment' => $data['service_comment'] ?? null,
            ]);
        });

        return redirect()
            ->route('reviews.index')
            ->with('success', 'Avaliações criadas com sucesso.');
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
        $review = Review::with('reviewable')->findOrFail($id);

        $this->authorize('update', $review);

        return view(
            'reviews.edit',
            compact('review')
        );
    }

    // O alvo da avaliação (barbeiro/serviço) não muda: só nota e comentário.
    public function update(Request $request, $id)
    {
        $review = Review::findOrFail($id);

        $this->authorize('update', $review);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $review->update($data);

        return redirect()
            ->route('reviews.index')
            ->with('success', 'Avaliação atualizada com sucesso.');
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