<?php

namespace App\Models;

use Database\Factories\MateriaCupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MateriaCup extends Model
{
    /** @use HasFactory<MateriaCupFactory> */
    use HasFactory;

    protected $table = 'materias_cup';

    protected $primaryKey = 'id_materia_cup';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'gestion_cup_id',
        'nombre_materia',
        'ponderacion_nota1',
        'ponderacion_nota2',
        'ponderacion_nota3',
        'nota_minima',
        'estado',
    ];

    public function gestionCup(): BelongsTo
    {
        return $this->belongsTo(GestionCup::class, 'gestion_cup_id', 'id_gestion');
    }

    public function docentes(): BelongsToMany
    {
        return $this->belongsToMany(
            Docente::class,
            'docente_materia_cup',
            'materia_cup_id',
            'docente_id',
            'id_materia_cup',
            'id_docente'
        )->withTimestamps();
    }

    public function asignacionesGrupo(): HasMany
    {
        return $this->hasMany(AsignacionGrupo::class, 'materia_cup_id', 'id_materia_cup');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ponderacion_nota1' => 'decimal:2',
            'ponderacion_nota2' => 'decimal:2',
            'ponderacion_nota3' => 'decimal:2',
            'nota_minima' => 'decimal:2',
        ];
    }
}
