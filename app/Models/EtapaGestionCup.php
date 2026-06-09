<?php

namespace App\Models;

use Database\Factories\EtapaGestionCupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EtapaGestionCup extends Model
{
    /** @use HasFactory<EtapaGestionCupFactory> */
    use HasFactory;

    public const ESTADO_PROGRAMADA = 'programada';

    public const ESTADO_ACTIVA = 'activa';

    public const ESTADO_CERRADA = 'cerrada';

    public const ETAPA_INSCRIPCION = 'Inscripcion';

    protected $table = 'etapas_gestion_cup';

    protected $primaryKey = 'id_etapa_gestion';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'gestion_cup_id',
        'nombre_etapa',
        'orden',
        'fecha_inicio',
        'fecha_fin',
        'estado_etapa',
    ];

    /**
     * @return array<string, string>
     */
    public static function estadosEtapa(): array
    {
        return [
            self::ESTADO_PROGRAMADA => 'Programada',
            self::ESTADO_ACTIVA => 'Activa',
            self::ESTADO_CERRADA => 'Cerrada',
        ];
    }

    public function gestionCup(): BelongsTo
    {
        return $this->belongsTo(GestionCup::class, 'gestion_cup_id', 'id_gestion');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }
}
