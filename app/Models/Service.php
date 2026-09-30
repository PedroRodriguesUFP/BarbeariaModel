<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\Category;

class Service extends Model
{
    protected function casts(): array
    {
        return [
            'categoria' => Category::class,
        ];
    }

    protected $fillable = [
        'nome',
        'descricao',
        'preco',
        'duracao',
        'categoria',
    ];

    public function atualizarPreco(float $novoPreco): void
    {
        $this->preco = $novoPreco;
        $this->save();
    }

    public function atualizarNome(string $novoNome): void
    {
        $this->nome = $novoNome;
        $this->save();
    }

    public function atualizarDuracao(int $novaDuracao): void
    {
        $this->duracao = $novaDuracao;
        $this->save();
    }

    public function atualizarDescricao(string $novaDescricao): void
    {
        $this->descricao = $novaDescricao;
        $this->save();
    }
}