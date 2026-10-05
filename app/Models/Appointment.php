<?php

namespace App\Models;

use App\Enums\Status;
use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Appointment extends Model
{
    protected $fillable = [
        'date',
        'time',
        'status',
        'client_id',
        'service_id',
        'barber_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'status' => Status::class,
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(
            Client::class,
            'client_id'
        );
    }

    public function barber(): BelongsTo
    {
        return $this->belongsTo(
            Barber::class,
            'barber_id'
        );
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(
            Service::class,
            'service_id'
        );
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function makeAppointment(
        DateTime $date,
        string $clientId,
        string $serviceId,
        string $barberId,
        ?string $time = null
    ): void {
        $this->date = $date;
        $this->status = Status::Pending;
        $this->client_id = $clientId;
        $this->service_id = $serviceId;
        $this->barber_id = $barberId;

        if ($time !== null) {
            $this->time = $time;
        }

        $this->save();
    }

    public function updateAppointment(
        DateTime $newDate,
        string $newClientId,
        string $newServiceId,
        string $newBarberId,
        ?string $newTime = null
    ): void {
        $this->date = $newDate;
        $this->client_id = $newClientId;
        $this->service_id = $newServiceId;
        $this->barber_id = $newBarberId;

        if ($newTime !== null) {
            $this->time = $newTime;
        }

        $this->save();
    }

    public function updateStatus(Status $newStatus): void
    {
        $this->status = $newStatus;

        $this->save();
    }

    public function isPending(): bool
    {
        if ($this->status === Status::Pending) {
            return true;
        }

        return false;
    }

    public function isConfirmed(): bool
    {
        if ($this->status === Status::Confirmed) {
            return true;
        }

        return false;
    }

    public function isCancelled(): bool
    {
        if ($this->status === Status::Cancelled) {
            return true;
        }

        return false;
    }

    public function isCompleted(): bool
    {
        if ($this->status === Status::Completed) {
            return true;
        }

        return false;
    }

    public function canBeCancelled(): bool
    {
        if ($this->status == Status::Pending || $this->status === Status::Confirmed) {
            return true;
        }

        return false;
    }
    public function confirmAppointment(): void
    {
        $this->status = Status::Confirmed;
        $this->save();
    }

    public function completeAppointment(): void
    {
        $this->status = Status::Completed;
        $this->save();
    }

    public function cancelAppointment(): void
    {
        if ($this->canBeCancelled() == true) {
            $this->status = Status::Cancelled;
            $this->save();
        } else {
            throw new \Exception('The appointment cannot be cancelled.');
        }

    }
}
