<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Token de acesso à API (autenticação por Bearer token).
 *
 * Na base de dados fica só o hash SHA-256 do token. Quem faz login recebe
 * o token em claro uma única vez; se o perder, gera outro.
 */
class ApiToken extends Model
{
    protected $fillable = ['user_id', 'name', 'token', 'last_used_at', 'expires_at'];

    protected $hidden = ['token'];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function hash(string $plainText): string
    {
        return hash('sha256', $plainText);
    }

    /**
     * Cria um token novo para o utilizador e devolve o texto em claro.
     */
    public static function issueFor(User $user, string $name, ?int $days = null): string
    {
        $plain = Str::random(40);

        static::create([
            'user_id' => $user->id,
            'name' => $name,
            'token' => static::hash($plain),
            'expires_at' => $days ? now()->addDays($days) : null,
        ]);

        return $plain;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
