<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Postulante extends Model
{
    use HasFactory;

    public const ESTADO_REGISTRADO = 'registrado';

    public const ESTADO_INSCRITO = 'inscrito';

    /**
     * @return array<string, string>
     */
    public static function estadosGestionables(): array
    {
        return [
            self::ESTADO_REGISTRADO => 'Registrado',
            self::ESTADO_INSCRITO => 'Inscrito',
        ];
    }

    protected $table = 'postulantes';

    protected $primaryKey = 'id_postulante';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'usuario_id',
        'ci',
        'nombres',
        'apellidos',
        'correo',
        'telefono',
        'colegio_procedencia',
        'anio_bachillerato',
        'es_extranjero',
        'estado',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id', 'id_usuario');
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class, 'postulante_id', 'id_postulante');
    }

    public function nombreCompleto(): string
    {
        return trim("{$this->nombres} {$this->apellidos}");
    }

    public function estadoLabel(): string
    {
        return self::estadosGestionables()[$this->estado] ?? $this->estado;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'anio_bachillerato' => 'integer',
            'es_extranjero' => 'boolean',
        ];
    }
}
