<?php

namespace App\Http\Controllers;

use App\Enums\Status;
use App\Http\Requests\GuardarAvaliacaoRequest;
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
    public function store(GuardarAvaliacaoRequest $request)
    {
        $data = $request->validated();

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

    public function show(Review $review)
    {
        $this->authorize('view', $review);

        $review->load(['reviewer', 'reviewable']);

        return view(
            'reviews.show',
            compact('review')
        );
    }

    public function edit(Review $review)
    {
        $this->authorize('update', $review);

        $review->load('reviewable');

        return view(
            'reviews.edit',
            compact('review')
        );
    }

    // O alvo da avaliação (barbeiro/serviço) não muda: só nota e comentário.
    public function update(GuardarAvaliacaoRequest $request, Review $review)
    {
        $data = $request->validated();

        $review->update($data);

        return redirect()
            ->route('reviews.index')
            ->with('success', 'Avaliação atualizada com sucesso.');
    }

    public function destroy(Review $review)
    {
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