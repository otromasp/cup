<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoInscripcion extends Model
{
    use HasFactory;

    public const PROVEEDOR_STRIPE = 'stripe';

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_PAGADO = 'pagado';

    public const ESTADO_CANCELADO = 'cancelado';

    public const ESTADO_FALLIDO = 'fallido';

    /**
     * @return array<string, string>
     */
    public static function estadosGestionables(): array
    {
        return [
            self::ESTADO_PENDIENTE => 'Pendiente',
            self::ESTADO_PAGADO => 'Pagado',
            self::ESTADO_CANCELADO => 'Cancelado',
            self::ESTADO_FALLIDO => 'Fallido',
        ];
    }

    protected $table = 'pagos_inscripcion';

    protected $primaryKey = 'id_pago_inscripcion';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'inscripcion_id',
        'proveedor',
        'monto_centavos',
        'moneda',
        'estado',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
        'codigo_comprobante',
        'pagado_en',
    ];

    public function inscripcion(): BelongsTo
    {
        return $this->belongsTo(Inscripcion::class, 'inscripcion_id', 'id_inscripcion');
    }

    public function estadoLabel(): string
    {
        return self::estadosGestionables()[$this->estado] ?? $this->estado;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pagado_en' => 'datetime',
        ];
    }
}
