<?php

namespace App\Actions\CasosUso\CU04GestionarEtapasGestionCUP;

use App\Models\EtapaGestionCup;
use App\Models\GestionCup;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActivarEtapaGestionCupAction
{
    public function execute(EtapaGestionCup $etapaGestionCup): EtapaGestionCup
    {
        return DB::transaction(function () use ($etapaGestionCup): EtapaGestionCup {
            /** @var EtapaGestionCup $etapaGestionCup */
            $etapaGestionCup = EtapaGestionCup::query()
                ->whereKey($etapaGestionCup->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            /** @var GestionCup $gestionCup */
            $gestionCup = GestionCup::query()
                ->whereKey($etapaGestionCup->gestion_cup_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($etapaGestionCup->estado_etapa !== EtapaGestionCup::ESTADO_PROGRAMADA) {
                throw ValidationException::withMessages([
                    'etapa' => 'La etapa seleccionada ya no esta programada.',
                ]);
            }

            $etapaActiva = $gestionCup->etapas()
                ->where('id_etapa_gestion', '<>', $etapaGestionCup->id_etapa_gestion)
                ->where('estado_etapa', EtapaGestionCup::ESTADO_ACTIVA)
                ->first();

            if ($etapaActiva instanceof EtapaGestionCup && $etapaActiva->orden > $etapaGestionCup->orden) {
                throw ValidationException::withMessages([
                    'etapa' => 'Para volver a una etapa anterior usa Reabrir.',
                ]);
            }

            // Al avanzar, las etapas anteriores quedan cerradas y solo la seleccionada queda activa.
            $gestionCup->etapas()
                ->where('orden', '<', $etapaGestionCup->orden)
                ->where('estado_etapa', '<>', EtapaGestionCup::ESTADO_CERRADA)
                ->update(['estado_etapa' => EtapaGestionCup::ESTADO_CERRADA]);

            $gestionCup->etapas()
                ->where('id_etapa_gestion', '<>', $etapaGestionCup->id_etapa_gestion)
                ->where('estado_etapa', EtapaGestionCup::ESTADO_ACTIVA)
                ->update(['estado_etapa' => EtapaGestionCup::ESTADO_CERRADA]);

            $etapaGestionCup->forceFill([
                'estado_etapa' => EtapaGestionCup::ESTADO_ACTIVA,
            ])->save();

            $gestionCup->forceFill([
                'estado_configuracion' => GestionCup::ESTADO_BLOQUEADA,
            ])->save();

            return $etapaGestionCup->load('gestionCup');
        });
    }
}
