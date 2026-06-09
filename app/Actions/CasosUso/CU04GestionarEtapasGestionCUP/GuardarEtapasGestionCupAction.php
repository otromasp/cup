<?php

namespace App\Actions\CasosUso\CU04GestionarEtapasGestionCUP;

use App\Models\EtapaGestionCup;
use App\Models\GestionCup;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GuardarEtapasGestionCupAction
{
    /**
     * @param  list<array<string, mixed>>  $etapas
     */
    public function execute(GestionCup $gestionCup, array $etapas): GestionCup
    {
        return DB::transaction(function () use ($gestionCup, $etapas): GestionCup {
            /** @var GestionCup $gestionCup */
            $gestionCup = GestionCup::query()
                ->whereKey($gestionCup->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($gestionCup->etapas()->whereIn('estado_etapa', [EtapaGestionCup::ESTADO_ACTIVA, EtapaGestionCup::ESTADO_CERRADA])->exists()) {
                throw ValidationException::withMessages([
                    'etapas' => 'No se puede reprogramar etapas cuando la gestion CUP ya inicio.',
                ]);
            }

            // Las etapas programadas se reemplazan como plan completo de calendario.
            $gestionCup->etapas()->delete();
            $gestionCup->etapas()->createMany($etapas);

            return $gestionCup->load('etapas');
        });
    }
}
