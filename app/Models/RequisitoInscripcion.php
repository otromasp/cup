<?php

namespace App\Models;

use Database\Factories\RequisitoInscripcionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RequisitoInscripcion extends Model
{
    /** @use HasFactory<RequisitoInscripcionFactory> */
    use HasFactory;

    public const TIPO_DECLARATIVO = 'declarativo';

    public const TIPO_PAGO = 'pago';

    public const APLICA_TODOS = 'todos';

    public const APLICA_EXTRANJEROS = 'extranjeros';

    protected $table = 'requisitos_inscripcion';

    protected $primaryKey = 'id_requisito';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'gestion_cup_id',
        'nombre_requisito',
        'obligatorio',
        'tipo_requisito',
        'aplica_a',
        'estado',
    ];

    /**
     * @return array<string, string>
     */
    public static function tiposRequisito(): array
    {
        return [
            self::TIPO_DECLARATIVO => 'Declarativo',
            self::TIPO_PAGO => 'Pago',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function ambitosAplicacion(): array
    {
        return [
            self::APLICA_TODOS => 'Todos',
            self::APLICA_EXTRANJEROS => 'Postulantes extranjeros',
        ];
    }

    public function gestionCup(): BelongsTo
    {
        return $this->belongsTo(GestionCup::class, 'gestion_cup_id', 'id_gestion');
    }

    public function cumplimientosInscripcion(): HasMany
    {
        return $this->hasMany(InscripcionRequisito::class, 'requisito_id', 'id_requisito');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'obligatorio' => 'boolean',
        ];
    }
}
