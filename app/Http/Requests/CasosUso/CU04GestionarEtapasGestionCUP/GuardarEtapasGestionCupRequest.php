<?php

namespace App\Http\Requests\CasosUso\CU04GestionarEtapasGestionCUP;

use App\Models\EtapaGestionCup;
use App\Models\GestionCup;
use App\Models\Usuario;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GuardarEtapasGestionCupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $usuario = $this->user();

        return $usuario instanceof Usuario && $usuario->puedeConfigurarGestionCup();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'etapas' => ['required', 'array', 'min:1'],
            'etapas.*.nombre_etapa' => ['required', 'string', 'max:120'],
            'etapas.*.orden' => ['required', 'integer', 'min:1', 'max:999'],
            'etapas.*.fecha_inicio' => ['required', 'date'],
            'etapas.*.fecha_fin' => ['required', 'date'],
            'etapas.*.estado_etapa' => ['nullable', Rule::in(array_keys(EtapaGestionCup::estadosEtapa()))],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var GestionCup|null $gestionCup */
            $gestionCup = $this->route('gestionCup');

            if (! $gestionCup instanceof GestionCup) {
                return;
            }

            if ($gestionCup->etapas()->whereIn('estado_etapa', [EtapaGestionCup::ESTADO_ACTIVA, EtapaGestionCup::ESTADO_CERRADA])->exists()) {
                $validator->errors()->add('etapas', 'No se puede reprogramar etapas cuando la gestion CUP ya inicio.');

                return;
            }

            $etapas = collect($this->input('etapas', []));

            $nombres = $etapas
                ->map(fn (mixed $etapa): string => mb_strtolower(trim((string) data_get($etapa, 'nombre_etapa'))))
                ->filter();

            if ($nombres->duplicates()->isNotEmpty()) {
                $validator->errors()->add('etapas', 'No se puede repetir una etapa en la misma gestion.');
            }

            $ordenes = $etapas
                ->map(fn (mixed $etapa): int => (int) data_get($etapa, 'orden'))
                ->filter();

            if ($ordenes->duplicates()->isNotEmpty()) {
                $validator->errors()->add('etapas', 'No se puede repetir el orden de una etapa.');
            }

            $inicioGestion = CarbonImmutable::parse($gestionCup->fecha_inicio);
            $finGestion = CarbonImmutable::parse($gestionCup->fecha_fin);
            $anteriorFin = null;

            $etapas
                ->sortBy(fn (mixed $etapa): int => (int) data_get($etapa, 'orden'))
                ->values()
                ->each(function (mixed $etapa, int $indice) use ($validator, $inicioGestion, $finGestion, &$anteriorFin): void {
                    $inicioEtapa = CarbonImmutable::parse((string) data_get($etapa, 'fecha_inicio'));
                    $finEtapa = CarbonImmutable::parse((string) data_get($etapa, 'fecha_fin'));

                    if ($finEtapa->lt($inicioEtapa)) {
                        $validator->errors()->add("etapas.{$indice}.fecha_fin", 'La fecha final de la etapa debe ser igual o posterior a la fecha inicial.');
                    }

                    if ($inicioEtapa->lt($inicioGestion) || $finEtapa->gt($finGestion)) {
                        $validator->errors()->add("etapas.{$indice}.fecha_inicio", 'La etapa debe estar dentro del periodo de la gestion CUP.');
                    }

                    if ($anteriorFin !== null && $inicioEtapa->lte($anteriorFin)) {
                        $validator->errors()->add("etapas.{$indice}.fecha_inicio", 'Las etapas no deben solaparse.');
                    }

                    $anteriorFin = $finEtapa;
                });
        });
    }

    /**
     * @return list<array{nombre_etapa: string, orden: int, fecha_inicio: string, fecha_fin: string, estado_etapa: string}>
     */
    public function datosEtapas(): array
    {
        /** @var array{etapas: list<array<string, mixed>>} $datos */
        $datos = $this->validated();

        return collect($datos['etapas'])
            ->sortBy(fn (array $etapa): int => (int) $etapa['orden'])
            ->values()
            ->map(fn (array $etapa): array => [
                'nombre_etapa' => trim((string) $etapa['nombre_etapa']),
                'orden' => (int) $etapa['orden'],
                'fecha_inicio' => (string) $etapa['fecha_inicio'],
                'fecha_fin' => (string) $etapa['fecha_fin'],
                'estado_etapa' => EtapaGestionCup::ESTADO_PROGRAMADA,
            ])
            ->all();
    }
}
