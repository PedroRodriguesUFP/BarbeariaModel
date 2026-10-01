<?php

namespace App\Models;

use App\Enums\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Service extends Model
{
    protected $fillable = [
        'nome',
        'descricao',
        'preco',
        'duracao',
        'categoria',
    ];

    protected function casts(): array
    {
        return [
            'categoria' => Category::class,
        ];
    }
    public function reviews(): MorphMany
    {
        return $this->morphMany(
            Review::class,
            'reviewable'
        );
    }
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