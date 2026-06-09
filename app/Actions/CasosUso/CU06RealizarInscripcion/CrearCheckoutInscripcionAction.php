<?php

namespace App\Actions\CasosUso\CU06RealizarInscripcion;

use App\Models\GestionCup;
use App\Models\RequisitoInscripcion;
use App\Models\Usuario;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Stripe\StripeClient;

class CrearCheckoutInscripcionAction
{
    private const CACHE_PREFIX = 'cu06:inscripcion-pago:';

    /**
     * @param  array{
     *     gestion_cup_id: int,
     *     postulante: array<string, mixed>,
     *     turno_gestion_cup_id: int,
     *     carrera_primera_id: int,
     *     carrera_segunda_id: int|null,
     *     requisitos_cumplidos: list<int>
     * }  $datos
     */
    public function execute(array $datos): string
    {
        $gestionCup = GestionCup::query()
            ->with('requisitos')
            ->findOrFail($datos['gestion_cup_id']);

        if (! $this->requierePago($gestionCup, (bool) $datos['postulante']['es_extranjero'])) {
            throw ValidationException::withMessages([
                'pago' => 'La inscripcion no requiere pago.',
            ]);
        }

        $montoCentavos = $gestionCup->montoInscripcionCentavos();

        if ($montoCentavos <= 0) {
            throw ValidationException::withMessages([
                'pago' => 'La gestion CUP debe tener un costo de inscripcion mayor a cero.',
            ]);
        }

        $tokenBorrador = (string) Str::uuid();

        Cache::put(self::cacheKey($tokenBorrador), $datos, now()->addHours(2));

        $stripe = $this->clienteStripe();

        $session = $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'customer_email' => $datos['postulante']['correo'],
            'line_items' => [[
                'price_data' => [
                    'currency' => $gestionCup->monedaInscripcionStripe(),
                    'unit_amount' => $montoCentavos,
                    'product_data' => [
                        'name' => 'Inscripcion CUP '.$gestionCup->convocatoria,
                    ],
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'borrador_inscripcion' => $tokenBorrador,
                'ci_postulante' => (string) $datos['postulante']['ci'],
            ],
            'success_url' => route('cu06.inscripcion.pago.exito', [
                'session_id' => '{CHECKOUT_SESSION_ID}',
            ]),
            'cancel_url' => route('cu06.inscripcion.pago.cancelado', [
                'borrador' => $tokenBorrador,
            ]),
        ]);

        return (string) $session->url;
    }

    public static function cacheKey(string $tokenBorrador): string
    {
        return self::CACHE_PREFIX.$tokenBorrador;
    }

    private function requierePago(GestionCup $gestionCup, bool $esExtranjero): bool
    {
        return $gestionCup->requisitos
            ->filter(fn (RequisitoInscripcion $requisito): bool => $this->requisitoAplica($requisito, $esExtranjero))
            ->contains(fn (RequisitoInscripcion $requisito): bool => $requisito->obligatorio
                && $requisito->tipo_requisito === RequisitoInscripcion::TIPO_PAGO
                && $requisito->estado === Usuario::ESTADO_ACTIVO);
    }

    private function requisitoAplica(RequisitoInscripcion $requisito, bool $esExtranjero): bool
    {
        return $requisito->aplica_a === RequisitoInscripcion::APLICA_TODOS
            || ($requisito->aplica_a === RequisitoInscripcion::APLICA_EXTRANJEROS && $esExtranjero);
    }

    private function clienteStripe(): StripeClient
    {
        $secret = (string) config('services.stripe.secret');

        if ($secret === '') {
            throw ValidationException::withMessages([
                'stripe' => 'Falta configurar STRIPE_SECRET_KEY con una llave secreta sk_test.',
            ]);
        }

        if (str_starts_with($secret, 'pk_')) {
            throw ValidationException::withMessages([
                'stripe' => 'STRIPE_SECRET_KEY no puede usar pk_test. Debe ser una llave secreta sk_test.',
            ]);
        }

        return new StripeClient($secret);
    }
}
