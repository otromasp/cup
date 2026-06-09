<?php

namespace App\Models;

use Database\Factories\CarreraCupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CarreraCup extends Model
{
    /** @use HasFactory<CarreraCupFactory> */
    use HasFactory;

    protected $table = 'carreras_cup';

    protected $primaryKey = 'id_carrera_cup';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'gestion_cup_id',
        'nombre_carrera',
        'cupo_disponible',
        'cupo_ocupado',
        'estado',
    ];

    public function gestionCup(): BelongsTo
    {
        return $this->belongsTo(GestionCup::class, 'gestion_cup_id', 'id_gestion');
    }

    public function inscripcionesPrimeraOpcion(): HasMany
    {
        return $this->hasMany(Inscripcion::class, 'carrera_primera_id', 'id_carrera_cup');
    }

    public function inscripcionesSegundaOpcion(): HasMany
    {
        return $this->hasMany(Inscripcion::class, 'carrera_segunda_id', 'id_carrera_cup');
    }
}
