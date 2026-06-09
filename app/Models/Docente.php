<?php

namespace App\Models;

use Database\Factories\DocenteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Docente extends Model
{
    /** @use HasFactory<DocenteFactory> */
    use HasFactory;

    public const ESTADO_HABILITADO = 'habilitado';

    public const ESTADO_OBSERVADO = 'observado';

    public const ESTADO_INACTIVO = 'inactivo';

    protected $table = 'docentes';

    protected $primaryKey = 'id_docente';

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
        'profesion',
        'area_especialidad',
        'titulo_profesional_afin',
        'tiene_maestria',
        'tiene_diplomado_educacion_superior',
        'maximo_grupos_asignables',
        'estado_contratacion',
    ];

    /**
     * @return array<string, string>
     */
    public static function estadosContratacion(): array
    {
        return [
            self::ESTADO_HABILITADO => 'Habilitado',
            self::ESTADO_OBSERVADO => 'Observado',
            self::ESTADO_INACTIVO => 'Inactivo',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id', 'id_usuario');
    }

    public function materias(): BelongsToMany
    {
        return $this->belongsToMany(
            MateriaCup::class,
            'docente_materia_cup',
            'docente_id',
            'materia_cup_id',
            'id_docente',
            'id_materia_cup'
        )->withTimestamps();
    }

    public function disponibilidades(): HasMany
    {
        return $this->hasMany(DisponibilidadDocente::class, 'docente_id', 'id_docente')
            ->orderBy('dia_semana')
            ->orderBy('hora_inicio');
    }

    public function asignacionesGrupo(): HasMany
    {
        return $this->hasMany(AsignacionGrupo::class, 'docente_id', 'id_docente');
    }

    public function nombreCompleto(): string
    {
        return trim("{$this->nombres} {$this->apellidos}");
    }

    public function cumpleRequisitosHabilitacion(): bool
    {
        return (bool) $this->titulo_profesional_afin
            && (bool) $this->tiene_maestria
            && (bool) $this->tiene_diplomado_educacion_superior
            && $this->materias()->exists()
            && $this->disponibilidades()->exists();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'titulo_profesional_afin' => 'boolean',
            'tiene_maestria' => 'boolean',
            'tiene_diplomado_educacion_superior' => 'boolean',
            'maximo_grupos_asignables' => 'integer',
        ];
    }
}
