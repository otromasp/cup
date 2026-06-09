<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Inscripcion extends Model
{
    use HasFactory;

    public const ESTADO_PENDIENTE_PAGO = 'pendiente_pago';

    public const ESTADO_CONFIRMADA = 'confirmada';

    public const ESTADO_CANCELADA = 'cancelada';

    /**
     * @return array<string, string>
     */
    public static function estadosGestionables(): array
    {
        return [
            self::ESTADO_PENDIENTE_PAGO => 'Pendiente de pago',
            self::ESTADO_CONFIRMADA => 'Confirmada',
            self::ESTADO_CANCELADA => 'Cancelada',
        ];
    }

    protected $table = 'inscripciones';

    protected $primaryKey = 'id_inscripcion';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'gestion_cup_id',
        'postulante_id',
        'carrera_primera_id',
        'carrera_segunda_id',
        'turno_gestion_cup_id',
        'codigo_inscripcion',
        'estado',
        'fecha_inscripcion',
        'observacion',
    ];

    public function gestionCup(): BelongsTo
    {
        return $this->belongsTo(GestionCup::class, 'gestion_cup_id', 'id_gestion');
    }

    public function postulante(): BelongsTo
    {
        return $this->belongsTo(Postulante::class, 'postulante_id', 'id_postulante');
    }

    public function carreraPrimera(): BelongsTo
    {
        return $this->belongsTo(CarreraCup::class, 'carrera_primera_id', 'id_carrera_cup');
    }

    public function carreraSegunda(): BelongsTo
    {
        return $this->belongsTo(CarreraCup::class, 'carrera_segunda_id', 'id_carrera_cup');
    }

    public function turnoGestionCup(): BelongsTo
    {
        return $this->belongsTo(TurnoGestionCup::class, 'turno_gestion_cup_id', 'id_turno_gestion');
    }

    public function pago(): HasOne
    {
        return $this->hasOne(PagoInscripcion::class, 'inscripcion_id', 'id_inscripcion');
    }

    public function requisitosCumplidos(): HasMany
    {
        return $this->hasMany(InscripcionRequisito::class, 'inscripcion_id', 'id_inscripcion');
    }

    public function grupoAsignado(): HasOne
    {
        return $this->hasOne(InscripcionGrupo::class, 'inscripcion_id', 'id_inscripcion');
    }

    public function estaConfirmada(): bool
    {
        return $this->estado === self::ESTADO_CONFIRMADA;
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
            'fecha_inscripcion' => 'datetime',
        ];
    }
}
