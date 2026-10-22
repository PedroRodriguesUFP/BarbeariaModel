<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Carbon\Carbon;

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
        'google_id'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'active' => 'boolean',
        ];
    }

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

    // Marcações feitas por este utilizador
    public function appointmentsAsCustomer(): HasMany
    {
        return $this->hasMany(
            Appointment::class,
            'customer_id'
        );
    }

    // Marcações em que este utilizador é o barbeiro
    public function appointmentsAsBarber(): HasMany
    {
        return $this->hasMany(
            Appointment::class,
            'barber_id'
        );
    }

    // Serviços que o utilizador realiza quando é barbeiro
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(
            Service::class,
            'service_user',
            'user_id',
            'service_id'
        );
    }

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
    // Avaliações escritas por este utilizador.
    public function reviewsWritten(): HasMany
    {
    return $this->hasMany(
        Review::class,
        'reviewer_id'
    );
     }

    // Avaliações recebidas quando este utilizador é barbeiro.
    public function reviewsReceived(): MorphMany
    {
    return $this->morphMany(
        Review::class,
        'reviewable'
    );
    }
    // Horário semanal de trabalho do barbeiro.
    public function workingHours(): HasMany
    {
    return $this->hasMany(
        WorkingHour::class
    );
    }
    // Períodos em que o barbeiro está indisponível.
    public function scheduleBlocks(): HasMany
    {
    return $this->hasMany(
        ScheduleBlock::class
    );
    }
    // Verifica se este utilizador/barbeiro realiza determinado serviço.
    public function canPerformService(Service $service): bool
    {
    if (! $this->isBarber()) {
        return false;
    }

    return $this->services()
        ->where('services.id', $service->id)
        ->exists();
    }


    // Verifica se o intervalo está dentro do horário normal de trabalho.
    public function worksDuring(
    string $date,
    string $startTime,
    string $endTime
    ): bool {
    $dayOfWeek = Carbon::parse($date)->isoWeekday();

    return $this->workingHours()
        ->where('day_of_week', $dayOfWeek)
        ->where('start_time', '<=', $startTime)
        ->where('end_time', '>=', $endTime)
        ->exists();
    }


    // Verifica se existe um bloqueio nesse horário.
    public function hasScheduleBlock(
    string $date,
    string $startTime,
    string $endTime
    ): bool {
    $blocks = $this->scheduleBlocks()
        ->whereDate('date', $date)
        ->get();

    foreach ($blocks as $block) {

        // Dia inteiro bloqueado.
        if ($block->all_day) {
            return true;
        }

        // Verifica se os intervalos se cruzam.
        if (
            $startTime < $block->end_time &&
            $endTime > $block->start_time
        ) {
            return true;
        }
    }

    return false;
    }


    // Verifica se já existe uma marcação que ocupe esse intervalo.
    public function hasAppointmentConflict(
    string $date,
    string $startTime,
    string $endTime,
    ?int $ignoreAppointmentId = null
    ): bool {
    $appointmentsAsBarber = $this
        ->appointmentsAsBarber()
        ->with('service')
        ->whereDate('date', $date)
        ->get();

    $appointmentsAsCustomer = $this
        ->appointmentsAsCustomer()
        ->with('service')
        ->whereDate('date', $date)
        ->get();

    $appointments = $appointmentsAsBarber
        ->merge($appointmentsAsCustomer)
        ->unique('id');

    foreach ($appointments as $appointment) {

        if (
            $ignoreAppointmentId !== null &&
            $appointment->id === $ignoreAppointmentId
        ) {
            continue;
        }

        // Marcações canceladas não ocupam horário.
        if ($appointment->isCancelled()) {
            continue;
        }

        if (! $appointment->time || ! $appointment->service) {
            continue;
        }

        $existingStart = Carbon::parse(
            $appointment->date->format('Y-m-d')
            .' '
            .$appointment->time
        );

        $existingEnd = $existingStart
            ->copy()
            ->addMinutes(
                $appointment->service->duration_minutes
            );

        $newStart = Carbon::parse(
            $date.' '.$startTime
        );

        $newEnd = Carbon::parse(
            $date.' '.$endTime
        );

        if (
            $newStart->lt($existingEnd) &&
            $newEnd->gt($existingStart)
        ) {
            return true;
        }
    }

    return false;
    }


     // Verifica todas as regras necessárias para uma marcação.
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

    $start = Carbon::parse(
        $date.' '.$time
    );

    $end = $start
        ->copy()
        ->addMinutes(
            $service->duration_minutes
        );

    $startTime = $start->format('H:i:s');
    $endTime = $end->format('H:i:s');

    if (! $this->worksDuring(
        $date,
        $startTime,
        $endTime
    )) {
        return false;
    }

    if ($this->hasScheduleBlock(
        $date,
        $startTime,
        $endTime
    )) {
        return false;
    }

    if ($this->hasAppointmentConflict(
        $date,
        $startTime,
        $endTime,
        $ignoreAppointmentId
    )) {
        return false;
    }

    return true;
    }
} 