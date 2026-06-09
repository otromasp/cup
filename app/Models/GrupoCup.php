<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GrupoCup extends Model
{
    use HasFactory;

    public const CAPACIDAD_MAXIMA = 70;

    public const ESTADO_EN_PLANIFICACION = 'en_planificacion';

    public const ESTADO_PUBLICADO = 'publicado';

    protected $table = 'grupos_cup';

    protected $primaryKey = 'id_grupo_cup';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'gestion_cup_id',
        'nombre_grupo',
        'numero_grupo',
        'capacidad_maxima',
        'turno',
        'estado',
        'publicado_en',
    ];

    /**
     * @return array<string, string>
     */
    public static function estados(): array
    {
        return [
            self::ESTADO_EN_PLANIFICACION => 'En planificacion',
            self::ESTADO_PUBLICADO => 'Publicado',
        ];
    }

    public function gestionCup(): BelongsTo
    {
        return $this->belongsTo(GestionCup::class, 'gestion_cup_id', 'id_gestion');
    }

    public function inscripcionesAsignadas(): HasMany
    {
        return $this->hasMany(InscripcionGrupo::class, 'grupo_cup_id', 'id_grupo_cup');
    }

    public function horarios(): HasMany
    {
        return $this->hasMany(HorarioGrupoCup::class, 'grupo_cup_id', 'id_grupo_cup');
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(AsignacionGrupo::class, 'grupo_cup_id', 'id_grupo_cup');
    }

    public function estaPublicado(): bool
    {
        return $this->estado === self::ESTADO_PUBLICADO;
    }

    public function estadoLabel(): string
    {
        return self::estados()[$this->estado] ?? $this->estado;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'numero_grupo' => 'integer',
            'capacidad_maxima' => 'integer',
            'publicado_en' => 'datetime',
        ];
    }
}
