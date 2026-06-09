import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { CalendarDays, Pencil, Plus } from 'lucide-react';

import { type GestionesPaginadas } from './types';

type IndexProps = {
    gestiones: GestionesPaginadas;
    status?: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Gestion CUP',
        href: '/gestion-cup',
    },
];

export default function Index({ gestiones, status }: IndexProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Configurar gestion CUP" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold tracking-normal">Configurar gestion CUP</h1>
                        <p className="text-muted-foreground text-sm">Gestion, carreras, cupos, materias y requisitos</p>
                    </div>

                    <Button asChild>
                        <Link href={route('cu03.gestion-cup.create')}>
                            <Plus />
                            Nueva gestion
                        </Link>
                    </Button>
                </div>

                {status && <div className="rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700">{status}</div>}

                <div className="overflow-hidden rounded-lg border">
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[1020px] text-sm">
                            <thead className="bg-muted/50 text-left">
                                <tr>
                                    <th className="px-4 py-3 font-medium">Gestion</th>
                                    <th className="px-4 py-3 font-medium">Periodo</th>
                                    <th className="px-4 py-3 font-medium">Nota minima</th>
                                    <th className="px-4 py-3 font-medium">Costo</th>
                                    <th className="px-4 py-3 font-medium">Configuracion</th>
                                    <th className="px-4 py-3 font-medium">Contenido</th>
                                    <th className="px-4 py-3 font-medium">Responsable</th>
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
                                        <td className="px-4 py-3">{gestion.nota_minima_aprobacion}</td>
                                        <td className="px-4 py-3">{gestion.costo_inscripcion}</td>
                                        <td className="px-4 py-3">
                                            <Badge variant={gestion.estado_configuracion === 'configurada' ? 'default' : 'outline'}>
                                                {gestion.estado_configuracion_label}
                                            </Badge>
                                        </td>
                                        <td className="text-muted-foreground px-4 py-3">
                                            {gestion.turnos_count} turnos, {gestion.carreras_count} carreras, {gestion.materias_count} materias,{' '}
                                            {gestion.requisitos_count} requisitos
                                        </td>
                                        <td className="text-muted-foreground px-4 py-3">{gestion.responsable ?? 'Sin responsable'}</td>
                                        <td className="px-4 py-3">
                                            <div className="flex justify-end gap-2">
                                                <Button asChild size="icon" variant="outline" title="Gestionar etapas">
                                                    <Link href={route('cu04.etapas-cup.edit', gestion.id_gestion)}>
                                                        <CalendarDays />
                                                        <span className="sr-only">Gestionar etapas</span>
                                                    </Link>
                                                </Button>
                                                <Button asChild size="icon" variant="outline" title="Editar gestion CUP">
                                                    <Link href={route('cu03.gestion-cup.edit', gestion.id_gestion)}>
                                                        <Pencil />
                                                        <span className="sr-only">Editar gestion CUP</span>
                                                    </Link>
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}

                                {gestiones.data.length === 0 && (
                                    <tr>
                                        <td colSpan={8} className="text-muted-foreground px-4 py-8 text-center">
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
