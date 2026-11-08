<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\Status;
use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Marcação de um serviço com um barbeiro, a uma data e hora.
 *
 * Ciclo de vida do estado (ver Status):
 *   Pending -> Confirmed -> Completed
 *   Pending ou Confirmed -> Cancelled
 *
 * Clientes sem conta (walk-in) não têm customer_id: ficam com
 * walk_in_name e walk_in_phone.
 */
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

    // Barbeiro da marcação.
    public function barber(): BelongsTo
    {
    return $this->belongsTo(
        User::class,
        'barber_id' // foreign key´do barbeiro na tabela users
    );
    }

    // Serviço marcado.
    public function service(): BelongsTo
    {
        return $this->belongsTo(
            Service::class, 
            'service_id' // foreign key do serviço na tabela services
        );
    }

    // Pagamentos feitos para esta marcação.
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // ------------------------------------------------------------------
    // Criar e atualizar
    // ------------------------------------------------------------------

    // Cria uma marcação nova, sempre como pendente.
    public function makeAppointment(
        DateTime $date,
        string $customerId,
        string $serviceId,
        string $barberId,
        ?string $time = null
    ): void {
        $this->date        = $date;
        $this->status      = Status::Pending;
        $this->customer_id = $customerId;
        $this->service_id  = $serviceId;
        $this->barber_id   = $barberId;

        if ($time !== null) {
            $this->time = $time;
        }

        $this->save();
    }

    // Atualiza os dados da marcação. O estado não muda aqui.
    public function updateAppointment(
        DateTime $newDate,
        string $newCustomerId,
        string $newServiceId,
        string $newBarberId,
        ?string $newTime = null
    ): void {
        $this->date        = $newDate;
        $this->customer_id = $newCustomerId;
        $this->service_id  = $newServiceId;
        $this->barber_id   = $newBarberId;

        if ($newTime !== null) {
            $this->time = $newTime;
        }

        $this->save();
    }

    // ------------------------------------------------------------------
    // Estado
    // ------------------------------------------------------------------

    public function isPending(): bool
    {
        return $this->status === Status::Pending;
    }

    public function isConfirmed(): bool
    {
        return $this->status === Status::Confirmed;
    }

    public function isCancelled(): bool
    {
        return $this->status === Status::Cancelled;
    }

    public function isCompleted(): bool
    {
        return $this->status === Status::Completed;
    }

    // Só se confirma o que está pendente.
    public function canBeConfirmed(): bool
    {
        return $this->isPending();
    }

    // Só se conclui o que está confirmado.
    public function canBeCompleted(): bool
    {
        return $this->isConfirmed();
    }

    // Só se cancela o que ainda está em aberto.
    public function canBeCancelled(): bool
    {
        
        if ($this->status == Status::Pending || $this->status === Status::Confirmed) {
            return true;
        }

    // Muda o estado diretamente, sem verificar a transição.
    public function updateStatus(Status $newStatus): void
    {
        $this->status = $newStatus;
        $this->save();
    }

    // Os três métodos abaixo verificam o estado de partida e lançam
    // uma exceção se a mudança não for permitida.

    // Pendente -> Confirmada.
    public function confirmAppointment(): void
    {
        if (! $this->canBeConfirmed()) {
            throw new \Exception('The appointment cannot be confirmed.');
        }

        $this->status = Status::Confirmed;
        $this->save();
    }

    // Confirmada -> Concluída.
    public function completeAppointment(): void
    {
        if (! $this->canBeCompleted()) {
            throw new \Exception('The appointment cannot be completed.');
        }

        $this->status = Status::Completed;
        $this->save();
    }

    // Pendente ou Confirmada -> Cancelada.
    public function cancelAppointment(): void
    {
        if (! $this->canBeCancelled()) {
            throw new \Exception('The appointment cannot be cancelled.');
        }

        $this->status = Status::Cancelled;
        $this->save();
    }

    // ------------------------------------------------------------------
    // Pagamento
    // ------------------------------------------------------------------

    // A marcação já tem algum pagamento com estado "paid"?
    public function isPaid(): bool
    {
        return $this->payments->contains('status', PaymentStatus::Paid->value);
    }

    // Este utilizador pode pagar esta marcação agora?
    // Tem de ser o cliente dela, e ela não pode estar cancelada nem paga.
    public function canBePaidBy(User $user): bool
    {
        return $this->customer_id !== null
            && (int) $this->customer_id === (int) $user->id
            && ! $this->isCancelled()
            && ! $this->isPaid();
    }
    // verifica se o cliente é um walk-in (sem conta)

    // ------------------------------------------------------------------
    // Cliente sem conta (walk-in)
    // ------------------------------------------------------------------

    public function isWalkIn(): bool
    {
        return $this->customer_id === null;
    }

    // Nome a mostrar: o da conta, o nome escrito à mão, ou um texto genérico.
    public function customerDisplayName(): string
    {
        if ($this->customer) {
            return $this->customer->name;
        }

// Se não houver cliente associado, retorna o nome do walk-in.
        return $this->walk_in_name ?? 'Walk-in';
    }
}
