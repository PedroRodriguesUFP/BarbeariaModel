<?php

namespace App\Models;

use App\Enums\Status;
use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Appointment extends Model
{
    // 
    protected $fillable = [
    'date',
    'time',
    'status',
    'customer_id',
    'walk_in_name',
    'walk_in_phone',
    'service_id',
    'barber_id',
    ];

    //converte os campos date e status  para os tipos corretos
    protected function casts(): array
    {
        return [
            'date' => 'date', // converte para um objeto data laravel
            'status' => Status::class, // converte para o enum Status
        ];
    }

    // Retorna o dono da marcação.
    public function customer(): BelongsTo
    {
    return $this->belongsTo(
        User::class, 
        'customer_id' // foreign key na tabela users
    );
    }

    // Devolve o barbeiro da marcação.
    public function barber(): BelongsTo
    {
    return $this->belongsTo(
        User::class,
        'barber_id' // foreign key´do barbeiro na tabela users
    );
    }

    // Devolve o serviço da marcação.
    public function service(): BelongsTo
    {
        return $this->belongsTo(
            Service::class, 
            'service_id' // foreign key do serviço na tabela services
        );
    }

    // Devolve os pagamentos da marcação.
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // Cria uma marcação pendente.
    public function makeAppointment(
    DateTime $date,
    string $customerId,
    string $serviceId,
    string $barberId,
    ?string $time = null
    ): void {
    $this->date = $date;
    $this->status = Status::Pending;
    $this->customer_id = $customerId;
    $this->service_id = $serviceId;
    $this->barber_id = $barberId;

    if ($time !== null) {
        $this->time = $time;
    }

    $this->save();
    }

    // Atualiza os dados da marcação.
    public function updateAppointment(
    DateTime $newDate,
    string $newCustomerId,
    string $newServiceId,
    string $newBarberId,
    ?string $newTime = null
    ): void {
    $this->date = $newDate;
    $this->customer_id = $newCustomerId;
    $this->service_id = $newServiceId;
    $this->barber_id = $newBarberId;

    if ($newTime !== null) {
        $this->time = $newTime;
    }

    $this->save();
    }

    // Altera o estado da marcação.
    public function updateStatus(Status $newStatus): void
    {
        $this->status = $newStatus;

        $this->save();
    }

    // Verifica se a marcação está pendente.
    public function isPending(): bool
    {
        if ($this->status === Status::Pending) {
            return true;
        }

        return false;
    }

    // Verifica se a marcação está confirmada.
    public function isConfirmed(): bool
    {
        if ($this->status === Status::Confirmed) {
            return true;
        }

        return false;
    }

    // Verifica se a marcação está cancelada.
    public function isCancelled(): bool
    {
        if ($this->status === Status::Cancelled) {
            return true;
        }

        return false;
    }

    // Verifica se a marcação foi concluída.
    public function isCompleted(): bool
    {
        if ($this->status === Status::Completed) {
            return true;
        }

        return false;
    }

    // Verifica se a marcação pode ser cancelada.
    public function canBeCancelled(): bool
    {
        
        if ($this->status == Status::Pending || $this->status === Status::Confirmed) {
            return true;
        }

        return false;
    }
    // Confirma a marcação.
    public function confirmAppointment(): void
    {
        $this->status = Status::Confirmed;
        $this->save();
    }

    // Marca a marcação como concluída.
    public function completeAppointment(): void
    {
        $this->status = Status::Completed;
        $this->save();
    }

    // Cancela a marcação, se possível.
    public function cancelAppointment(): void
    {
        if ($this->canBeCancelled() == true) {
            $this->status = Status::Cancelled;
            $this->save();
        } else {
            throw new \Exception('The appointment cannot be cancelled.');
        }

    }
    // verifica se o cliente é um walk-in (sem conta)
    public function isWalkIn(): bool
    {
        return $this->customer_id === null;
    }

    public function customerDisplayName(): string
    {
        if ($this->customer) {
            return $this->customer->name;
        }

// Se não houver cliente associado, retorna o nome do walk-in.
        return $this->walk_in_name ?? 'Walk-in';
    }
}
