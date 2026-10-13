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

    // Devolve os dados de utilizador do cliente.
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Devolve as avaliações do cliente.
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    // Devolve as marcações do cliente.
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'client_id');
    }

    // Devolve os pagamentos associados às marcações.
    public function payments(): HasManyThrough
    {
        return $this->hasManyThrough(
            Payment::class,
            Appointment::class,
            'client_id',
            'appointment_id'
        );
    }

    // Lista as próximas marcações do cliente.
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

    // Lista as marcações concluídas do cliente.
    public function getCompletedAppointments()
    {
        return $this->appointments()
            ->where('status', Status::Completed->value)
            ->get();
    }

    // Lista as marcações canceladas do cliente.
    public function getCancelledAppointments()
    {
        return $this->appointments()
            ->where('status', Status::Cancelled->value)
            ->get();
    }

    // Conta as marcações do cliente.
    public function getTotalAppointments(): int
    {
        return $this->appointments()->count();
    }

    // Verifica se o cliente já marcou com o barbeiro.
    public function hasAppointmentWith(Barber $barber): bool
    {
        return $this->appointments()
            ->where('barber_id', $barber->id)
            ->exists();
    }

    // Verifica se o cliente já concluiu o serviço.
    public function hasUsedService(Service $service): bool
    {
        return $this->appointments()
            ->where('service_id', $service->id)
            ->where('status', Status::Completed->value)
            ->exists();
    }
}
