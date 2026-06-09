<?php

namespace App\Actions\CasosUso\CU06RealizarInscripcion;

use App\Models\Inscripcion;
use App\Models\PagoInscripcion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Stripe\StripeClient;

class ConfirmarPagoStripeAction
{
    public function __construct(
        private readonly RegistrarInscripcionAction $registrarInscripcion
    ) {}

    public function execute(string $sessionId): Inscripcion
    {
        $pagoRegistrado = PagoInscripcion::query()
            ->with('inscripcion')
            ->where('stripe_checkout_session_id', $sessionId)
            ->first();

        if ($pagoRegistrado instanceof PagoInscripcion && $pagoRegistrado->inscripcion instanceof Inscripcion) {
            return $pagoRegistrado->inscripcion
                ->load(['postulante.usuario', 'gestionCup', 'carreraPrimera', 'carreraSegunda', 'turnoGestionCup', 'pago', 'requisitosCumplidos.requisito']);
        }

        $stripe = $this->clienteStripe();
        $session = $stripe->checkout->sessions->retrieve($sessionId);

        if ($session->payment_status !== 'paid') {
            throw ValidationException::withMessages([
                'pago' => 'Stripe todavia no confirma el pago de la inscripcion.',
            ]);
        }

        $tokenBorrador = (string) data_get($session, 'metadata.borrador_inscripcion');
        $datos = $tokenBorrador !== '' ? Cache::get(CrearCheckoutInscripcionAction::cacheKey($tokenBorrador)) : null;

        if (! is_array($datos)) {
            throw ValidationException::withMessages([
                'pago' => 'No se encontraron los datos temporales de la inscripcion. Vuelva a realizar el registro.',
            ]);
        }

        $inscripcion = $this->registrarInscripcion->execute($datos, [
            'session_id' => (string) $session->id,
            'payment_intent' => filled($session->payment_intent) ? (string) $session->payment_intent : null,
        ]);

        Cache::forget(CrearCheckoutInscripcionAction::cacheKey($tokenBorrador));

        return $inscripcion;
    }

    private function clienteStripe(): StripeClient
    {
        $secret = (string) config('services.stripe.secret');

        if ($secret === '' || str_starts_with($secret, 'pk_')) {
            throw ValidationException::withMessages([
                'stripe' => 'Stripe no esta configurado con una llave secreta valida.',
            ]);
        }

        return new StripeClient($secret);
    }
}
