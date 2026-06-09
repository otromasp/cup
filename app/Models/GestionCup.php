<?php

namespace App\Models;

use Database\Factories\GestionCupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GestionCup extends Model
{
    /** @use HasFactory<GestionCupFactory> */
    use HasFactory;

    public const ESTADO_EN_CONFIGURACION = 'en_configuracion';

    public const ESTADO_CONFIGURADA = 'configurada';

    public const ESTADO_BLOQUEADA = 'bloqueada';

    public const MONEDA_BOB = 'bob';

    public const MONEDA_USD = 'usd';

    protected $table = 'gestiones_cup';

    protected $primaryKey = 'id_gestion';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'usuario_responsable_id',
        'nombre_gestion',
        'convocatoria',
        'fecha_inicio',
        'fecha_fin',
        'nota_minima_aprobacion',
        'costo_inscripcion',
        'moneda_inscripcion',
        'estado_configuracion',
    ];

    /**
     * @return array<string, string>
     */
    public static function estadosConfiguracion(): array
    {
        return [
            self::ESTADO_EN_CONFIGURACION => 'En configuracion',
            self::ESTADO_CONFIGURADA => 'Configurada',
            self::ESTADO_BLOQUEADA => 'Bloqueada por etapa iniciada',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function monedasInscripcion(): array
    {
        return [
            self::MONEDA_BOB => 'Bolivianos (BOB)',
            self::MONEDA_USD => 'Dolares (USD)',
        ];
    }

    public function montoInscripcionCentavos(): int
    {
        return (int) round(((float) $this->costo_inscripcion) * 100);
    }

    public function monedaInscripcionStripe(): string
    {
        return mb_strtolower($this->moneda_inscripcion ?: self::MONEDA_BOB);
    }

    public function costoInscripcionLabel(): string
    {
        return mb_strtoupper($this->monedaInscripcionStripe()).' '.number_format((float) $this->costo_inscripcion, 2, '.', ',');
    }

    public function usuarioResponsable(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_responsable_id', 'id_usuario');
    }

    public function carreras(): HasMany
    {
        return $this->hasMany(CarreraCup::class, 'gestion_cup_id', 'id_gestion')->orderBy('nombre_carrera');
    }

    public function materias(): HasMany
    {
        return $this->hasMany(MateriaCup::class, 'gestion_cup_id', 'id_gestion')->orderBy('nombre_materia');
    }

    public function requisitos(): HasMany
    {
        return $this->hasMany(RequisitoInscripcion::class, 'gestion_cup_id', 'id_gestion')->orderBy('nombre_requisito');
    }

    public function turnos(): HasMany
    {
        return $this->hasMany(TurnoGestionCup::class, 'gestion_cup_id', 'id_gestion')->orderBy('orden');
    }

    public function etapas(): HasMany
    {
        return $this->hasMany(EtapaGestionCup::class, 'gestion_cup_id', 'id_gestion')->orderBy('orden');
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class, 'gestion_cup_id', 'id_gestion');
    }

    public function grupos(): HasMany
    {
        return $this->hasMany(GrupoCup::class, 'gestion_cup_id', 'id_gestion')->orderBy('numero_grupo');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'nota_minima_aprobacion' => 'decimal:2',
            'costo_inscripcion' => 'decimal:2',
        ];
    }
}
