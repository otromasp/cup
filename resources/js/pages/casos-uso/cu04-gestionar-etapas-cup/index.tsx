import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { CalendarDays, Pencil } from 'lucide-react';

import { type GestionesEtapasPaginadas } from './types';

type IndexProps = {
    gestiones: GestionesEtapasPaginadas;
    status?: string;
    error?: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Etapas CUP',
        href: '/etapas-gestion-cup',
    },
];

export default function Index({ gestiones, status, error }: IndexProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Gestionar etapas CUP" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold tracking-normal">Gestionar etapas de la gestion CUP</h1>
                        <p className="text-muted-foreground text-sm">Calendario operativo y avance de la gestion</p>
                    </div>

                    <Button asChild variant="outline">
                        <Link href={route('cu03.gestion-cup.index')}>
                            <CalendarDays />
                            Gestiones CUP
                        </Link>
                    </Button>
                </div>

                {status && <div className="rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700">{status}</div>}
                {error && <div className="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{error}</div>}

                <div className="overflow-hidden rounded-lg border">
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[860px] text-sm">
                            <thead className="bg-muted/50 text-left">
                                <tr>
                                    <th className="px-4 py-3 font-medium">Gestion</th>
                                    <th className="px-4 py-3 font-medium">Periodo</th>
                                    <th className="px-4 py-3 font-medium">Configuracion</th>
                                    <th className="px-4 py-3 font-medium">Etapas</th>
                                    <th className="px-4 py-3 font-medium">Etapa activa</th>
                                    <th className="px-4 py-3 text-right font-medium">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                {gestiones.data.map((gestion) => (
                                    <tr key={gestion.id_gestion} className="border-t">
                                        <td className="px-4 py-3">
                                            <div className="font-medium">{gestion.nombre_gestion}</div>
                                            <div className="text-muted-foreground">{gestion.convocatoria}</div>
                                        </td>
                                        <td className="text-muted-foreground px-4 py-3">
                                            {gestion.fecha_inicio} / {gestion.fecha_fin}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge variant={gestion.estado_configuracion === 'bloqueada' ? 'secondary' : 'outline'}>
                                                {gestion.estado_configuracion_label}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3">{gestion.etapas_count}</td>
                                        <td className="text-muted-foreground px-4 py-3">{gestion.etapa_activa ?? 'Sin etapa activa'}</td>
                                        <td className="px-4 py-3">
                                            <div className="flex justify-end">
                                                <Button asChild size="icon" variant="outline" title="Gestionar etapas">
                                                    <Link href={route('cu04.etapas-cup.edit', gestion.id_gestion)}>
                                                        <Pencil />
                                                        <span className="sr-only">Gestionar etapas</span>
                                                    </Link>
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}

                                {gestiones.data.length === 0 && (
                                    <tr>
                                        <td colSpan={6} className="text-muted-foreground px-4 py-8 text-center">
                                            No hay gestiones CUP configuradas.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div className="text-muted-foreground flex flex-col gap-3 text-sm sm:flex-row sm:items-center sm:justify-between">
                    <span>{gestiones.total > 0 ? `Mostrando ${gestiones.from} a ${gestiones.to} de ${gestiones.total}` : 'Sin registros'}</span>
                    <div className="flex flex-wrap gap-2">
                        {gestiones.links.map((link, index) => (
                            <Button
                                key={`${link.label}-${index}`}
                                asChild={Boolean(link.url)}
                                size="sm"
                                variant={link.active ? 'default' : 'outline'}
                                disabled={!link.url}
                            >
                                {link.url ? (
                                    <Link href={link.url} preserveScroll preserveState dangerouslySetInnerHTML={{ __html: link.label }} />
                                ) : (
                                    <span dangerouslySetInnerHTML={{ __html: link.label }} />
                                )}
                            </Button>
                        ))}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
