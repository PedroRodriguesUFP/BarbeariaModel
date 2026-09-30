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
        'data',
        'hora',
        'status',
        'cliente_id',
        'servico_id',
        'barbeiro_id',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'date',
            'status' => Status::class,
        ];
    }
    public function client(): BelongsTo
    {
        return $this->belongsTo(
            Client::class,
            'cliente_id'
        );
    }
    public function barber(): BelongsTo
    {
        return $this->belongsTo(
            Barber::class,
            'barbeiro_id'
        );
    }
    public function service(): BelongsTo
    {
        return $this->belongsTo(
            Service::class,
            'servico_id'
        );
    }
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
    public function fazerMarcacao(
        DateTime $data,
        string $cliente_id,
        string $servico_id,
        string $barbeiro_id,
        ?string $hora = null
    ): void {
        $this->data = $data;
        $this->status = Status::Pendente;
        $this->cliente_id = $cliente_id;
        $this->servico_id = $servico_id;
        $this->barbeiro_id = $barbeiro_id;

        if ($hora !== null) {
            $this->hora = $hora;
        }

        $this->save();
    }
    public function atualizarMarcacao(
        DateTime $nova_data,
        string $novo_cliente_id,
        string $novo_servico_id,
        string $novo_barbeiro_id,
        ?string $nova_hora = null
    ): void {
        $this->data = $nova_data;
        $this->cliente_id = $novo_cliente_id;
        $this->servico_id = $novo_servico_id;
        $this->barbeiro_id = $novo_barbeiro_id;

        if ($nova_hora !== null) {
            $this->hora = $nova_hora;
        }

        $this->save();
    }
    public function cancelarMarcacao(): void
    {
        $this->status = Status::Cancelado;

        $this->save();
    }
    public function atualizarStatus(Status $novo_status): void
    {
        $this->status = $novo_status;

        $this->save();
    }
}