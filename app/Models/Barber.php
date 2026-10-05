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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'barber_id');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(
            Service::class,
            'barber_service',
            'barber_id',
            'service_id'
        );
    }

    public function getAverageRating(): float
    {
        return (float) $this->reviews()->avg('rating');
    }

    public function getReviewsCount(): int
    {
        return $this->reviews()->count();
    }

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

    public function getCompletedAppointments()
    {
        return $this->appointments()
            ->where('status', Status::Completed->value)
            ->get();
    }

    public function getTotalAppointments(): int
    {
        return $this->appointments()->count();
    }

    public function canPerformService(Service $service): bool
    {
        return $this->services()
            ->whereKey($service->id)
            ->exists();
    }

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
