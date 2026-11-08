<?php

namespace App\Models;

use App\Enums\UserRole;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Utilizador do sistema. Pode ser admin, barbeiro ou cliente (ver UserRole).
 *
 * Os barbeiros têm ainda horário semanal, bloqueios, serviços que fazem
 * e a lógica de disponibilidade (isAvailableFor) no fim deste ficheiro.
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'bio',
        'password',
        'role',
        'active',
        'google_id',
    ];

    // Nunca aparecem quando o utilizador é convertido para JSON/array.
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed', // a password é encriptada ao guardar
            'role'              => UserRole::class,
            'active'            => 'boolean',
        ];
    }

    // ------------------------------------------------------------------
    // Papel (role)
    // ------------------------------------------------------------------

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isBarber(): bool
    {
        return $this->role === UserRole::Barber;
    }

    public function isClient(): bool
    {
        return $this->role === UserRole::Client;
    }

    public function hasRole(UserRole $role): bool
    {
        return $this->role === $role;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    // Nome da rota para onde o utilizador vai depois de entrar.
    public function homeRoute(): string
    {
        return match ($this->role) {
            UserRole::Admin  => 'admin.dashboard',
            UserRole::Barber => 'calendar.mine',
            default          => 'appointments.index',
        };
    }

    // ------------------------------------------------------------------
    // Relações
    // ------------------------------------------------------------------

    // Marcações feitas por este utilizador (como cliente).
    public function appointmentsAsCustomer(): HasMany
    {
        return $this->hasMany(Appointment::class, 'customer_id');
    }

    // Marcações em que este utilizador é o barbeiro.
    public function appointmentsAsBarber(): HasMany
    {
        return $this->hasMany(Appointment::class, 'barber_id');
    }

    // Serviços que o utilizador realiza quando é barbeiro.
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(
            Service::class,
            'service_user',
            'user_id',
            'service_id'
        );
    }

    // Avaliações escritas por este utilizador.
    public function reviewsWritten(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    // Avaliações recebidas quando este utilizador é barbeiro.
    public function reviewsReceived(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    // Horário semanal de trabalho do barbeiro.
    public function workingHours(): HasMany
    {
        return $this->hasMany(WorkingHour::class);
    }

    // Períodos em que o barbeiro está indisponível.
    public function scheduleBlocks(): HasMany
    {
        return $this->hasMany(ScheduleBlock::class);
    }

    // ------------------------------------------------------------------
    // Pequenos atalhos para atualizar dados
    // ------------------------------------------------------------------

    public function getDisplayNameAttribute(): string
    {
        return $this->name;
    }

    public function updateName(string $newName): void
    {
        $this->name = $newName;
        $this->save();
    }

    public function updateEmail(string $newEmail): void
    {
        $this->email = $newEmail;
        $this->save();
    }

    public function updatePhone(string $phoneNumber): void
    {
        $this->phone = $phoneNumber;
        $this->save();
    }

    // ------------------------------------------------------------------
    // Disponibilidade do barbeiro
    //
    // isAvailableFor() junta as quatro verificações abaixo.
    // Cada uma responde a uma pergunta simples, por ordem:
    //   1. O barbeiro faz este serviço?          -> canPerformService
    //   2. Está dentro do horário de trabalho?   -> worksDuring
    //   3. Tem um bloqueio nessa altura?         -> hasScheduleBlock
    //   4. Já tem outra marcação nessa altura?   -> hasAppointmentConflict
    // ------------------------------------------------------------------

    // Este utilizador é barbeiro e realiza o serviço indicado?
    public function canPerformService(Service $service): bool
    {
        if (! $this->isBarber()) {
            return false;
        }

        return $this->services()
            ->where('services.id', $service->id)
            ->exists();
    }

    // O intervalo cabe dentro do horário normal de trabalho nesse dia?
    public function worksDuring(string $date, string $startTime, string $endTime): bool
    {
        // 0 = domingo, 1 = segunda ... 6 = sábado.
        // É o mesmo formato que o BarberController usa ao guardar o horário.
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;

        return $this->workingHours()
            ->where('day_of_week', $dayOfWeek)
            ->where('start_time', '<=', $startTime)
            ->where('end_time', '>=', $endTime)
            ->exists();
    }

    // Existe um bloqueio (folga, férias, consulta...) que apanhe este intervalo?
    public function hasScheduleBlock(string $date, string $startTime, string $endTime): bool
    {
        $blocks = $this->scheduleBlocks()
            ->whereDate('date', $date)
            ->get();

        foreach ($blocks as $block) {
            // Dia inteiro bloqueado: não há mais nada a verificar.
            if ($block->all_day) {
                return true;
            }

            // Dois intervalos cruzam-se quando cada um começa antes
            // de o outro acabar.
            $overlaps = $startTime < $block->end_time
                && $endTime > $block->start_time;

            if ($overlaps) {
                return true;
            }
        }

        return false;
    }

    // Já existe uma marcação que ocupe este intervalo?
    //
    // Contam as marcações em que a pessoa é barbeiro E as que fez como
    // cliente (ninguém está em dois sítios ao mesmo tempo).
    // $ignoreAppointmentId serve nas edições: a própria marcação não conta.
    public function hasAppointmentConflict(
        string $date,
        string $startTime,
        string $endTime,
        ?int $ignoreAppointmentId = null
    ): bool {
        $asBarber = $this->appointmentsAsBarber()
            ->with('service')
            ->whereDate('date', $date)
            ->get();

        $asCustomer = $this->appointmentsAsCustomer()
            ->with('service')
            ->whereDate('date', $date)
            ->get();

        // Se for barbeiro e cliente da mesma marcação, só conta uma vez.
        $appointments = $asBarber->merge($asCustomer)->unique('id');

        $newStart = Carbon::parse($date . ' ' . $startTime);
        $newEnd   = Carbon::parse($date . ' ' . $endTime);

        foreach ($appointments as $appointment) {
            // A marcação que estamos a editar não conflita consigo própria.
            if ($ignoreAppointmentId !== null && (int) $appointment->id === $ignoreAppointmentId) {
                continue;
            }

            // Marcações canceladas não ocupam horário.
            if ($appointment->isCancelled()) {
                continue;
            }

            // Sem hora ou sem serviço não dá para saber quanto tempo ocupa.
            if (! $appointment->time || ! $appointment->service) {
                continue;
            }

            $existingStart = Carbon::parse(
                $appointment->date->format('Y-m-d') . ' ' . $appointment->time
            );

            $existingEnd = $existingStart
                ->copy()
                ->addMinutes($appointment->service->duration_minutes);

            $overlaps = $newStart->lt($existingEnd) && $newEnd->gt($existingStart);

            if ($overlaps) {
                return true;
            }
        }

        return false;
    }

    // Junta todas as regras: o barbeiro pode fazer este serviço a esta hora?
    public function isAvailableFor(
        Service $service,
        string $date,
        string $time,
        ?int $ignoreAppointmentId = null
    ): bool {
        if (! $this->isBarber()) {
            return false;
        }

        if (! $this->canPerformService($service)) {
            return false;
        }

        // Hora de fim = início + duração do serviço.
        $start = Carbon::parse($date . ' ' . $time);
        $end   = $start->copy()->addMinutes($service->duration_minutes);

        $startTime = $start->format('H:i:s');
        $endTime   = $end->format('H:i:s');

        if (! $this->worksDuring($date, $startTime, $endTime)) {
            return false;
        }

        if ($this->hasScheduleBlock($date, $startTime, $endTime)) {
            return false;
        }

        if ($this->hasAppointmentConflict($date, $startTime, $endTime, $ignoreAppointmentId)) {
            return false;
        }

        return true;
    }
}
