<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TurnoGestionCup extends Model
{
    use HasFactory;

    public const TURNO_MANANA = 'manana';

    public const TURNO_TARDE = 'tarde';

    public const TURNO_NOCHE = 'noche';

    public const MODALIDAD_PRESENCIAL = 'presencial';

    public const MODALIDAD_VIRTUAL = 'virtual';

    public const MODALIDAD_MIXTA = 'mixta';

    protected $table = 'turnos_gestion_cup';

    protected $primaryKey = 'id_turno_gestion';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'gestion_cup_id',
        'turno',
        'orden',
        'capacidad_maxima',
        'modalidad',
        'estado',
    ];

    /**
     * @return array<string, string>
     */
    public static function turnos(): array
    {
        return [
            self::TURNO_MANANA => 'Manana',
            self::TURNO_TARDE => 'Tarde',
            self::TURNO_NOCHE => 'Noche',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function modalidades(): array
    {
        return [
            self::MODALIDAD_PRESENCIAL => 'Presencial',
            self::MODALIDAD_VIRTUAL => 'Virtual',
            self::MODALIDAD_MIXTA => 'Mixta',
        ];
    }

    public function gestionCup(): BelongsTo
    {
        return $this->belongsTo(GestionCup::class, 'gestion_cup_id', 'id_gestion');
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class, 'turno_gestion_cup_id', 'id_turno_gestion');
    }
}
