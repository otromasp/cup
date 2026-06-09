<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InscripcionGrupo extends Model
{
    public const ESTADO_ASIGNADA = 'asignada';

    protected $table = 'inscripcion_grupo';

    protected $primaryKey = 'id_inscripcion_grupo';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'grupo_cup_id',
        'inscripcion_id',
        'estado',
        'asignado_en',
    ];

    public function grupoCup(): BelongsTo
    {
        return $this->belongsTo(GrupoCup::class, 'grupo_cup_id', 'id_grupo_cup');
    }

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(Inscripcion::class, 'inscripcion_id', 'id_inscripcion');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'asignado_en' => 'datetime',
        ];
    }
}
