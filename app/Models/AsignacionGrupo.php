<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsignacionGrupo extends Model
{
    public const ESTADO_ASIGNADA = 'asignada';

    protected $table = 'asignaciones_grupo';

    protected $primaryKey = 'id_asignacion_grupo';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'grupo_cup_id',
        'materia_cup_id',
        'docente_id',
        'horario_grupo_cup_id',
        'estado',
        'observacion',
    ];

    public function grupoCup(): BelongsTo
    {
        return $this->belongsTo(GrupoCup::class, 'grupo_cup_id', 'id_grupo_cup');
    }

    public function materiaCup(): BelongsTo
    {
        return $this->belongsTo(MateriaCup::class, 'materia_cup_id', 'id_materia_cup');
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'docente_id', 'id_docente');
    }

    public function horarioGrupoCup(): BelongsTo
    {
        return $this->belongsTo(HorarioGrupoCup::class, 'horario_grupo_cup_id', 'id_horario_grupo_cup');
    }
}
