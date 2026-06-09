<?php

namespace App\Models;

use Database\Factories\DisponibilidadDocenteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisponibilidadDocente extends Model
{
    /** @use HasFactory<DisponibilidadDocenteFactory> */
    use HasFactory;

    public const DIA_LUNES = 'lunes';

    public const DIA_MARTES = 'martes';

    public const DIA_MIERCOLES = 'miercoles';

    public const DIA_JUEVES = 'jueves';

    public const DIA_VIERNES = 'viernes';

    public const DIA_SABADO = 'sabado';

    public const DIA_DOMINGO = 'domingo';

    public const TURNO_MANANA = 'manana';

    public const TURNO_TARDE = 'tarde';

    public const TURNO_NOCHE = 'noche';

    public const MODALIDAD_PRESENCIAL = 'presencial';

    public const MODALIDAD_VIRTUAL = 'virtual';

    public const MODALIDAD_MIXTA = 'mixta';

    protected $table = 'disponibilidades_docente';

    protected $primaryKey = 'id_disponibilidad_docente';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'docente_id',
        'dia_semana',
        'turno',
        'hora_inicio',
        'hora_fin',
        'modalidad',
        'observacion',
    ];

    /**
     * @return array<string, string>
     */
    public static function diasSemana(): array
    {
        return [
            self::DIA_LUNES => 'Lunes',
            self::DIA_MARTES => 'Martes',
            self::DIA_MIERCOLES => 'Miercoles',
            self::DIA_JUEVES => 'Jueves',
            self::DIA_VIERNES => 'Viernes',
            self::DIA_SABADO => 'Sabado',
            self::DIA_DOMINGO => 'Domingo',
        ];
    }

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

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'docente_id', 'id_docente');
    }
}
