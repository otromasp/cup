<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InscripcionRequisito extends Model
{
    use HasFactory;

    public const ORIGEN_DECLARATIVO = 'declarativo';

    public const ORIGEN_PAGO = 'pago';

    protected $table = 'inscripcion_requisitos';

    protected $primaryKey = 'id_inscripcion_requisito';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'inscripcion_id',
        'requisito_id',
        'cumplido',
        'origen',
        'cumplido_en',
    ];

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(Inscripcion::class, 'inscripcion_id', 'id_inscripcion');
    }

    public function requisito(): BelongsTo
    {
        return $this->belongsTo(RequisitoInscripcion::class, 'requisito_id', 'id_requisito');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cumplido' => 'boolean',
            'cumplido_en' => 'datetime',
        ];
    }
}
