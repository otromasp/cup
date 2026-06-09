<?php

namespace App\Actions\CasosUso\CU06RealizarInscripcion;

use App\Models\Inscripcion;
use App\Models\InscripcionRequisito;
use App\Models\PagoInscripcion;
use App\Models\Postulante;
use App\Models\RequisitoInscripcion;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ConfirmarInscripcionAction
{
    public function execute(Inscripcion $inscripcion, ?string $sessionId = null, ?string $paymentIntent = null): Inscripcion
    {
        return DB::transaction(function () use ($inscripcion, $sessionId, $paymentIntent): Inscripcion {
            $inscripcion = Inscripcion::query()
                ->with(['postulante', 'pago', 'requisitosCumplidos.requisito'])
                ->lockForUpdate()
                ->findOrFail($inscripcion->id_inscripcion);

            $pago = $inscripcion->pago;

            if ($pago instanceof PagoInscripcion) {
                $pago->forceFill([
                    'estado' => PagoInscripcion::ESTADO_PAGADO,
                    'stripe_checkout_session_id' => $sessionId ?: $pago->stripe_checkout_session_id,
                    'stripe_payment_intent_id' => $paymentIntent ?: $pago->stripe_payment_intent_id,
                    'codigo_comprobante' => $paymentIntent ?: $sessionId ?: $pago->codigo_comprobante,
                    'pagado_en' => $pago->pagado_en ?: now(),
                ])->save();

                $inscripcion->requisitosCumplidos()
                    ->whereHas('requisito', fn ($query) => $query->where('tipo_requisito', RequisitoInscripcion::TIPO_PAGO))
                    ->update([
                        'cumplido' => true,
                        'origen' => InscripcionRequisito::ORIGEN_PAGO,
                        'cumplido_en' => now(),
                    ]);
            }

            $usuario = $this->crearUsuarioPostulante($inscripcion->postulante);

            $inscripcion->postulante->forceFill([
                'usuario_id' => $usuario->id_usuario,
                'estado' => Postulante::ESTADO_INSCRITO,
            ])->save();

            $inscripcion->forceFill([
                'estado' => Inscripcion::ESTADO_CONFIRMADA,
                'fecha_inscripcion' => $inscripcion->fecha_inscripcion ?: now(),
            ])->save();

            return $inscripcion->load(['postulante.usuario', 'gestionCup', 'carreraPrimera', 'carreraSegunda', 'turnoGestionCup', 'pago', 'requisitosCumplidos.requisito']);
        });
    }

    private function crearUsuarioPostulante(Postulante $postulante): Usuario
    {
        if ($postulante->usuario instanceof Usuario) {
            return $postulante->usuario;
        }

        return Usuario::query()->create([
            'nombre' => $postulante->nombreCompleto(),
            'ci' => $postulante->ci,
            'correo' => $postulante->correo,
            'contrasena_hash' => Hash::make(Str::password(16)),
            'estado' => Usuario::ESTADO_INACTIVO,
            'rol' => Usuario::ROL_POSTULANTE,
        ]);
    }
}
