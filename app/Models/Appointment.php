<?php

namespace App\Models;

use DateTime;
use Illuminate\Database\Eloquent\Model;
use App\Enums\Status;

class Appointment extends Model
{
    protected function casts(): array 
    {
        return [
            'status' => Status::class,
        ];
    }

    protected $fillable = [
        'data',
        'hora',
        'status',
        'cliente_id',
        'servico_id',
        'barbeiro_id',
    ];

    public function fazerMarcacao(DateTime $data, string $cliente_id, string $servico_id, string $barbeiro_id): void
    {
        $this->data = $data;
        $this->status = Status::Pendente;
        $this->cliente_id = $cliente_id;
        $this->servico_id = $servico_id;
        $this->barbeiro_id = $barbeiro_id;
        $this->save();
    }

    public function atualizarMarcacao(DateTime $nova_data, string $novo_cliente_id, string $novo_servico_id, string $novo_barbeiro_id): void
    {
        $this->data = $nova_data;
        $this->cliente_id = $novo_cliente_id;
        $this->servico_id = $novo_servico_id;
        $this->barbeiro_id = $novo_barbeiro_id;
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