<?php

namespace App\Models;

use App\Enums\Status;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Barber extends Model
{
    protected $fillable = [
        'user_id',
        'phone',
        'bio',
    ];

    // Devolve os dados de utilizador do barbeiro.
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Devolve as avaliações do barbeiro.
    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    // Devolve os pagamentos do barbeiro.
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // Devolve as marcações do barbeiro.
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'barber_id');
    }

    // Devolve os serviços do barbeiro.
    public function services(): BelongsToMany 
    {
        return $this->belongsToMany(
            Service::class,
            'barber_service',
            'barber_id',
            'service_id'
        );
    }

    // Calcula a classificação média do barbeiro.
    public function getAverageRating(): float
    {
        return (float) $this->reviews()->avg('rating');
    }

    // Conta as avaliações do barbeiro.
    public function getReviewsCount(): int
    {
        return $this->reviews()->count();
    }

    // Lista as próximas marcações do barbeiro.
    public function getUpcomingAppointments()
    {
        return $this->appointments()
            ->where(function ($query) {
                $query->whereDate('date', '>', today())
                    ->orWhere(function ($query) {
                        $query->whereDate('date', today())
                            ->whereTime('time', '>=', now()->format('H:i:s'));
                    });
            })
            ->whereIn('status', [
                Status::Pending->value,
                Status::Confirmed->value,
            ])
            ->orderBy('date')
            ->orderBy('time')
            ->get();
    }

    // Lista as marcações concluídas do barbeiro.
    public function getCompletedAppointments()
    {
        return $this->appointments()
            ->where('status', Status::Completed->value)
            ->get();
    }

    // Conta todas as marcações do barbeiro.
    public function getTotalAppointments(): int
    {
        return $this->appointments()->count();
    }

    // Verifica se o barbeiro presta o serviço.
    public function canPerformService(Service $service): bool
    {
        return $this->services()
            ->whereKey($service->id)
            ->exists();
    }

    // Verifica se o barbeiro está livre no horário indicado.
    public function isAvailableAt(
        DateTimeInterface $dateTime,
        int $durationMinutes = 30
    ): bool {
        if ($durationMinutes <= 0) {
            return false;
        }

        $start = Carbon::parse(
            $dateTime->format('Y-m-d H:i:s')
        );

        $end = $start->copy()->addMinutes($durationMinutes);

        $appointments = $this->appointments()
            ->with('service')
            ->whereDate('date', $start->toDateString())
            ->whereIn('status', [
                Status::Pending->value,
                Status::Confirmed->value,
            ])
            ->get();

        foreach ($appointments as $appointment) {
            if (! $appointment->time) {
                continue;
            }

            $appointmentStart = Carbon::parse(
                Carbon::parse($appointment->date)->toDateString()
                .' '
                .$appointment->time
            );

            $serviceDuration = (int) (
                $appointment->service?->duration_minutes ?? 30
            );

            $appointmentEnd = $appointmentStart
                ->copy()
                ->addMinutes($serviceDuration);

            if (
                $appointmentStart->lt($end) &&
                $start->lt($appointmentEnd)
            ) {
                return false;
            }
        }

        return true;
    }
}
