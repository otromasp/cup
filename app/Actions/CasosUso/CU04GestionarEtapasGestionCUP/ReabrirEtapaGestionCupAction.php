<?php

namespace App\Actions\CasosUso\CU04GestionarEtapasGestionCUP;

use App\Models\EtapaGestionCup;
use App\Models\GestionCup;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReabrirEtapaGestionCupAction
{
    public function execute(EtapaGestionCup $etapaGestionCup): EtapaGestionCup
    {
        return DB::transaction(function () use ($etapaGestionCup): EtapaGestionCup {
            /** @var EtapaGestionCup $etapaGestionCup */
            $etapaGestionCup = EtapaGestionCup::query()
                ->whereKey($etapaGestionCup->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($etapaGestionCup->estado_etapa !== EtapaGestionCup::ESTADO_CERRADA) {
                throw ValidationException::withMessages([
                    'etapa' => 'Solo se puede volver a una etapa cerrada.',
                ]);
            }

            /** @var GestionCup $gestionCup */
            $gestionCup = GestionCup::query()
                ->whereKey($etapaGestionCup->gestion_cup_id)
                ->lockForUpdate()
                ->firstOrFail();

            // Al volver, lo posterior se reprograma y solo esta etapa queda activa.
            $gestionCup->etapas()
                ->where('orden', '>', $etapaGestionCup->orden)
                ->whereIn('estado_etapa', [EtapaGestionCup::ESTADO_ACTIVA, EtapaGestionCup::ESTADO_CERRADA])
                ->update(['estado_etapa' => EtapaGestionCup::ESTADO_PROGRAMADA]);

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
