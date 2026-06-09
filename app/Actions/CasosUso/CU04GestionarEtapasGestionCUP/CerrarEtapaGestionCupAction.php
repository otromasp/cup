<?php

namespace App\Actions\CasosUso\CU04GestionarEtapasGestionCUP;

use App\Models\EtapaGestionCup;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CerrarEtapaGestionCupAction
{
    public function execute(EtapaGestionCup $etapaGestionCup): EtapaGestionCup
    {
        return DB::transaction(function () use ($etapaGestionCup): EtapaGestionCup {
            /** @var EtapaGestionCup $etapaGestionCup */
            $etapaGestionCup = EtapaGestionCup::query()
                ->whereKey($etapaGestionCup->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($etapaGestionCup->estado_etapa !== EtapaGestionCup::ESTADO_ACTIVA) {
                throw ValidationException::withMessages([
                    'etapa' => 'Solo se puede cerrar una etapa activa.',
                ]);
            }

            $etapaGestionCup->forceFill([
                'estado_etapa' => EtapaGestionCup::ESTADO_CERRADA,
            ])->save();

            return $etapaGestionCup->load('gestionCup');
        });
    }
}
