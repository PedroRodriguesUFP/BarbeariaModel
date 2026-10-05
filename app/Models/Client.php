<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Client extends Model
{
    protected $fillable = [
        'user_id',
        'phone',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'client_id');
    }

    public function payments(): HasManyThrough
    {
        return $this->hasManyThrough(
            Payment::class,
            Appointment::class,
            'client_id',
            'appointment_id'
        );
    }

    public function getUpcomingAppointments()
    {
        return $this->appointments()
            ->whereDate('date', '>=', today())
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

    public function getCancelledAppointments()
    {
        return $this->appointments()
            ->where('status', Status::Cancelled->value)
            ->get();
    }

    public function getTotalAppointments(): int
    {
        return $this->appointments()->count();
    }

    public function hasAppointmentWith(Barber $barber): bool
    {
        return $this->appointments()
            ->where('barber_id', $barber->id)
            ->exists();
    }

    public function hasUsedService(Service $service): bool
    {
        return $this->appointments()
            ->where('service_id', $service->id)
            ->where('status', Status::Completed->value)
            ->exists();
    }
}
