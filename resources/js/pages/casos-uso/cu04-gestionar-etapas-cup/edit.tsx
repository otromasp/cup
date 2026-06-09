import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowDown, ArrowUp, LoaderCircle, Play, Plus, RotateCcw, Save, Square, Trash2 } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import { type EtapaGestionCupForm, type EtapasGestionCupForm, type GestionEtapasDetalle, type OpcionesEtapasGestionCup } from './types';

type EditProps = {
    gestion: GestionEtapasDetalle;
    etapas: EtapaGestionCupForm[];
    opciones: OpcionesEtapasGestionCup;
    puedeProgramar: boolean;
    status?: string;
    error?: string;
};

const nuevaEtapa = (orden: number, fechaInicio: string | null, fechaFin: string | null): EtapaGestionCupForm => ({
    id_etapa_gestion: null,
    nombre_etapa: '',
    orden: String(orden),
    fecha_inicio: fechaInicio ?? '',
    fecha_fin: fechaFin ?? '',
    estado_etapa: 'programada',
    estado_etapa_label: 'Programada',
});

const renumerar = (etapas: EtapaGestionCupForm[]): EtapaGestionCupForm[] =>
    etapas.map((etapa, index) => ({
        ...etapa,
        orden: String(index + 1),
    }));

const estadoVariant = (estado: string): 'default' | 'secondary' | 'outline' => {
    if (estado === 'activa') {
        return 'default';
    }

    if (estado === 'cerrada') {
        return 'secondary';
    }

    return 'outline';
};

export default function Edit({ gestion, etapas, opciones, puedeProgramar, status, error }: EditProps) {
    const [accionProcesando, setAccionProcesando] = useState<string | null>(null);
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Etapas CUP',
            href: '/etapas-gestion-cup',
        },
        {
            title: gestion.nombre_gestion,
            href: `/etapas-gestion-cup/${gestion.id_gestion}`,
        },
    ];

    const { data, setData, put, processing, errors } = useForm<EtapasGestionCupForm>({
        etapas,
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        put(route('cu04.etapas-cup.update', gestion.id_gestion), {
            preserveScroll: true,
        });
    };

    const actualizarEtapa = <K extends keyof EtapaGestionCupForm>(index: number, key: K, value: EtapaGestionCupForm[K]) => {
        setData(
            'etapas',
            data.etapas.map((etapa, posicion) => (posicion === index ? { ...etapa, [key]: value } : etapa)),
        );
    };

    const moverEtapa = (index: number, direccion: -1 | 1) => {
        const destino = index + direccion;

        if (destino < 0 || destino >= data.etapas.length) {
            return;
        }

        const copia = [...data.etapas];
        const etapaOrigen = copia[index];
        copia[index] = copia[destino];
        copia[destino] = etapaOrigen;
        setData('etapas', renumerar(copia));
    };

    const quitarEtapa = (index: number) => {
        setData('etapas', renumerar(data.etapas.filter((_, posicion) => posicion !== index)));
    };

    const ejecutarAccion = (ruta: string, accion: string) => {
        setAccionProcesando(accion);
        router.patch(
            ruta,
            {},
            {
                preserveScroll: true,
                onFinish: () => setAccionProcesando(null),
            },
        );
    };

    const errorCampo = (key: string) => (errors as Record<string, string | undefined>)[key];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Etapas ${gestion.nombre_gestion}`} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold tracking-normal">Gestionar etapas de la gestion CUP</h1>
                        <p className="text-muted-foreground text-sm">
                            {gestion.nombre_gestion} / {gestion.convocatoria}
                        </p>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Badge variant={gestion.estado_configuracion === 'bloqueada' ? 'secondary' : 'outline'}>
                            {gestion.estado_configuracion_label}
                        </Badge>
                        <Badge variant="outline">
                            {gestion.fecha_inicio} / {gestion.fecha_fin}
                        </Badge>
                    </div>
                </div>

                {status && <div className="rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700">{status}</div>}
                {error && <div className="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{error}</div>}
                <InputError message={errorCampo('etapa')} />

                {!puedeProgramar && (
                    <div className="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                        La gestion ya inicio. Las fechas quedan protegidas; el avance se cambia iniciando, finalizando o retomando etapas.
                    </div>
                )}

                <form onSubmit={submit} className="flex flex-col gap-4">
                    <section className="rounded-lg border p-4">
                        <div className="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 className="text-base font-semibold tracking-normal">Calendario de etapas</h2>
                                <p className="text-muted-foreground text-sm">Orden, fechas y estado operativo</p>
                            </div>

                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                disabled={!puedeProgramar}
                                onClick={() =>
                                    setData('etapas', [...data.etapas, nuevaEtapa(data.etapas.length + 1, gestion.fecha_inicio, gestion.fecha_fin)])
                                }
                            >
                                <Plus />
                                Etapa
                            </Button>
                        </div>

                        <InputError message={errorCampo('etapas')} className="mb-2" />

                        <div className="overflow-x-auto rounded-md border">
                            <table className="w-full min-w-[1080px] text-sm">
                                <thead className="bg-muted/50 text-left">
                                    <tr>
                                        <th className="w-28 px-3 py-2 font-medium">Orden</th>
                                        <th className="px-3 py-2 font-medium">Etapa</th>
                                        <th className="w-40 px-3 py-2 font-medium">Inicio</th>
                                        <th className="w-40 px-3 py-2 font-medium">Fin</th>
                                        <th className="w-40 px-3 py-2 font-medium">Estado</th>
                                        <th className="w-64 px-3 py-2 text-right font-medium">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {data.etapas.map((etapa, index) => (
                                        <tr key={`etapa-${index}`} className="border-t">
                                            <td className="px-3 py-2">
                                                <div className="flex items-center gap-1">
                                                    <Input
                                                        value={etapa.orden}
                                                        disabled
                                                        className="h-9 w-14 text-center"
                                                        aria-label={`Orden de ${etapa.nombre_etapa || 'etapa'}`}
                                                    />
                                                    <div className="flex gap-1">
                                                        <Button
                                                            type="button"
                                                            size="icon"
                                                            variant="ghost"
                                                            title="Subir etapa"
                                                            disabled={!puedeProgramar || index === 0}
                                                            onClick={() => moverEtapa(index, -1)}
                                                        >
                                                            <ArrowUp />
                                                            <span className="sr-only">Subir etapa</span>
                                                        </Button>
                                                        <Button
                                                            type="button"
                                                            size="icon"
                                                            variant="ghost"
                                                            title="Bajar etapa"
                                                            disabled={!puedeProgramar || index === data.etapas.length - 1}
                                                            onClick={() => moverEtapa(index, 1)}
                                                        >
                                                            <ArrowDown />
                                                            <span className="sr-only">Bajar etapa</span>
                                                        </Button>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-3 py-2">
                                                <Label className="sr-only" htmlFor={`nombre-etapa-${index}`}>
                                                    Etapa
                                                </Label>
                                                <Input
                                                    id={`nombre-etapa-${index}`}
                                                    value={etapa.nombre_etapa}
                                                    disabled={!puedeProgramar}
                                                    onChange={(event) => actualizarEtapa(index, 'nombre_etapa', event.target.value)}
                                                />
                                                <InputError message={errorCampo(`etapas.${index}.nombre_etapa`)} className="mt-1" />
                                            </td>
                                            <td className="px-3 py-2">
                                                <Input
                                                    type="date"
                                                    value={etapa.fecha_inicio}
                                                    disabled={!puedeProgramar}
                                                    onChange={(event) => actualizarEtapa(index, 'fecha_inicio', event.target.value)}
                                                />
                                                <InputError message={errorCampo(`etapas.${index}.fecha_inicio`)} className="mt-1" />
                                            </td>
                                            <td className="px-3 py-2">
                                                <Input
                                                    type="date"
                                                    value={etapa.fecha_fin}
                                                    disabled={!puedeProgramar}
                                                    onChange={(event) => actualizarEtapa(index, 'fecha_fin', event.target.value)}
                                                />
                                                <InputError message={errorCampo(`etapas.${index}.fecha_fin`)} className="mt-1" />
                                            </td>
                                            <td className="px-3 py-2">
                                                <Badge variant={estadoVariant(etapa.estado_etapa)}>
                                                    {opciones.estadosEtapa.find((estado) => estado.value === etapa.estado_etapa)?.label ??
                                                        etapa.estado_etapa_label ??
                                                        etapa.estado_etapa}
                                                </Badge>
                                            </td>
                                            <td className="px-3 py-2">
                                                <div className="flex justify-end gap-2">
                                                    {etapa.id_etapa_gestion && etapa.estado_etapa === 'programada' && (
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            variant="outline"
                                                            title="Iniciar etapa"
                                                            disabled={accionProcesando !== null}
                                                            onClick={() =>
                                                                ejecutarAccion(
                                                                    route('cu04.etapas-cup.activate', etapa.id_etapa_gestion),
                                                                    `activar-${etapa.id_etapa_gestion}`,
                                                                )
                                                            }
                                                        >
                                                            {accionProcesando === `activar-${etapa.id_etapa_gestion}` ? <LoaderCircle className="animate-spin" /> : <Play />}
                                                            Iniciar
                                                        </Button>
                                                    )}
                                                    {etapa.id_etapa_gestion && etapa.estado_etapa === 'activa' && (
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            variant="outline"
                                                            title="Finalizar etapa"
                                                            disabled={accionProcesando !== null}
                                                            onClick={() =>
                                                                ejecutarAccion(
                                                                    route('cu04.etapas-cup.close', etapa.id_etapa_gestion),
                                                                    `cerrar-${etapa.id_etapa_gestion}`,
                                                                )
                                                            }
                                                        >
                                                            {accionProcesando === `cerrar-${etapa.id_etapa_gestion}` ? <LoaderCircle className="animate-spin" /> : <Square />}
                                                            Finalizar
                                                        </Button>
                                                    )}
                                                    {etapa.id_etapa_gestion && etapa.estado_etapa === 'cerrada' && (
                                                        <Button
                                                            type="button"
                                                            size="sm"
                                                            variant="outline"
                                                            title="Retomar etapa"
                                                            disabled={accionProcesando !== null}
                                                            onClick={() =>
                                                                ejecutarAccion(
                                                                    route('cu04.etapas-cup.reopen', etapa.id_etapa_gestion),
                                                                    `reabrir-${etapa.id_etapa_gestion}`,
                                                                )
                                                            }
                                                        >
                                                            {accionProcesando === `reabrir-${etapa.id_etapa_gestion}` ? <LoaderCircle className="animate-spin" /> : <RotateCcw />}
                                                            Retomar
                                                        </Button>
                                                    )}
                                                    <Button
                                                        type="button"
                                                        size="icon"
                                                        variant="ghost"
                                                        title="Quitar etapa"
                                                        disabled={!puedeProgramar || data.etapas.length === 1}
                                                        onClick={() => quitarEtapa(index)}
                                                    >
                                                        <Trash2 />
                                                        <span className="sr-only">Quitar etapa</span>
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <Button asChild variant="outline">
                            <Link href={route('cu04.etapas-cup.index')}>Volver</Link>
                        </Button>
                        <Button type="submit" disabled={processing || !puedeProgramar}>
                            {processing ? <LoaderCircle className="animate-spin" /> : <Save />}
                            Guardar etapas
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
