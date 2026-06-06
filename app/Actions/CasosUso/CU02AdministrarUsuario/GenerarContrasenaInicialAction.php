<?php

namespace App\Actions\CasosUso\CU02AdministrarUsuario;

class GenerarContrasenaInicialAction
{
    public function execute(): string
    {
        $caracteres = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $segmentos = [];

        for ($segmento = 0; $segmento < 2; $segmento++) {
            $texto = '';

            for ($indice = 0; $indice < 4; $indice++) {
                $texto .= $caracteres[random_int(0, strlen($caracteres) - 1)];
            }

            $segmentos[] = $texto;
        }

        return 'Cup-'.$segmentos[0].'-'.$segmentos[1];
    }
}
