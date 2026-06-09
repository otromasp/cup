<?php

namespace App\Http\Controllers\CasosUso\CU06RealizarInscripcion;

use App\Actions\CasosUso\CU06RealizarInscripcion\ConfirmarPagoStripeAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class ControladorWebhookStripe extends Controller
{
    public function __invoke(Request $request, ConfirmarPagoStripeAction $confirmarPago): JsonResponse
    {
        $secret = (string) config('services.stripe.webhook_secret');

        if ($secret === '') {
            return response()->json(['message' => 'Stripe webhook no configurado.'], 400);
        }

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
                $secret
            );
        } catch (UnexpectedValueException|SignatureVerificationException) {
            return response()->json(['message' => 'Firma de Stripe invalida.'], 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;

            if (data_get($session, 'payment_status') === 'paid') {
                $confirmarPago->execute((string) data_get($session, 'id'));
            }
        }

        return response()->json(['received' => true]);
    }
}
