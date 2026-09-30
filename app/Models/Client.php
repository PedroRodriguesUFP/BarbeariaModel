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
        return $this->hasMany(Appointment::class, 'cliente_id');
    }
    public function payments(): HasManyThrough
    {
        return $this->hasManyThrough(
            Payment::class,
            Appointment::class,
            'cliente_id',
            'appointment_id'
        );
    }
    public function getUpcomingAppointments()
    {
        return $this->appointments()
            ->whereDate('data', '>=', today())
            ->whereIn('status', [
                Status::Pendente->value,
                Status::Confirmado->value,
            ])
            ->orderBy('data')
            ->orderBy('hora')
            ->get();
    }
    public function getCompletedAppointments()
    {
        return $this->appointments()
            ->where('status', Status::Concluido->value)
            ->get();
    }
    public function getCancelledAppointments()
    {
        return $this->appointments()
            ->where('status', Status::Cancelado->value)
            ->get();
    }
    public function getTotalAppointments(): int
    {
        return $this->appointments()->count();
    }
    public function hasAppointmentWith(Barber $barber): bool
    {
        return $this->appointments()
            ->where('barbeiro_id', $barber->id)
            ->exists();
    }
    public function hasUsedService(Service $service): bool
    {
        return $this->appointments()
            ->where('servico_id', $service->id)
            ->where('status', Status::Concluido->value)
            ->exists();
    }
}