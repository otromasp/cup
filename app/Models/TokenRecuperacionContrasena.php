<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TokenRecuperacionContrasena extends Model
{
    public const ESTADO_VIGENTE = 'vigente';

    public const ESTADO_USADO = 'usado';

    public const ESTADO_REEMPLAZADO = 'reemplazado';

    protected $table = 'tokens_recuperacion_contrasena';

    protected $primaryKey = 'id_token';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'usuario_id',
        'codigo_token',
        'fecha_expiracion',
        'usado_en',
        'estado',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id', 'id_usuario');
    }

    public function scopeVigente(Builder $query): Builder
    {
        return $query
            ->where('estado', self::ESTADO_VIGENTE)
            ->whereNull('usado_en')
            ->where('fecha_expiracion', '>', now());
    }

    public function marcarUsado(): void
    {
        $this->forceFill([
            'estado' => self::ESTADO_USADO,
            'usado_en' => now(),
        ])->save();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_expiracion' => 'datetime',
            'usado_en' => 'datetime',
        ];
    }
}
