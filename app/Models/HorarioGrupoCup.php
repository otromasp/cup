<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HorarioGrupoCup extends Model
{
    protected $table = 'horarios_grupo_cup';

    protected $primaryKey = 'id_horario_grupo_cup';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'grupo_cup_id',
        'dia_semana',
        'turno',
        'hora_inicio',
        'hora_fin',
        'modalidad',
        'aula',
        'enlace_clase',
    ];

    public function grupoCup(): BelongsTo
    {
        return $this->belongsTo(GrupoCup::class, 'grupo_cup_id', 'id_grupo_cup');
    }

    public function asignacion(): HasOne
    {
        return $this->hasOne(AsignacionGrupo::class, 'horario_grupo_cup_id', 'id_horario_grupo_cup');
    }
}
