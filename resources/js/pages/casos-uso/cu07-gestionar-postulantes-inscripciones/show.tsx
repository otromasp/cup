import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type FormDataConvertible } from '@inertiajs/core';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, Save } from 'lucide-react';
import { FormEventHandler } from 'react';

import { type InscripcionDetalle, type OpcionInscripcion } from './types';

type ShowProps = {
    inscripcion: InscripcionDetalle;
    opciones: {
        estadosInscripcion: OpcionInscripcion[];
    };
    status?: string;
};

type EstadoForm = {
    [key: string]: FormDataConvertible;
    estado: string;
    observacion: string;
};

const estadoVariant = (estado: string): 'default' | 'secondary' | 'outline' | 'destructive' => {
    if (estado === 'confirmada') {
        return 'default';
    }

    if (estado === 'cancelada') {
        return 'destructive';
    }

    return 'outline';
};

const pagoVariant = (estado?: string): 'default' | 'secondary' | 'outline' | 'destructive' => {
    if (estado === 'pagado') {
        return 'default';
    }

    if (estado === 'cancelado' || estado === 'fallido') {
        return 'destructive';
    }

    return 'outline';
};

export default function Show({ inscripcion, opciones, status }: ShowProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Postulantes e inscripciones',
            href: '/postulantes-inscripciones',
        },
        {
            title: inscripcion.codigo_inscripcion,
            href: `/postulantes-inscripciones/${inscripcion.id_inscripcion}`,
        },
    ];

    const { data, setData, patch, processing, errors } = useForm<EstadoForm>({
        estado: inscripcion.estado,
        observacion: inscripcion.observacion ?? '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        patch(route('cu07.postulantes-inscripciones.status.update', inscripcion.id_inscripcion), {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Inscripcion ${inscripcion.codigo_inscripcion}`} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold tracking-normal">Detalle de inscripcion</h1>
                        <p className="text-sm text-muted-foreground">{inscripcion.codigo_inscripcion}</p>
                    </div>

                    <Button asChild variant="outline">
                        <Link href={route('cu07.postulantes-inscripciones.index')}>
                            <ArrowLeft />
                            Volver
                        </Link>
                    </Button>
                </div>

                {status && <div className="rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700">{status}</div>}

                <div className="grid gap-4 xl:grid-cols-[1fr_380px]">
                    <div className="flex flex-col gap-4">
                        <section className="rounded-lg border p-4">
                            <div className="mb-3 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <h2 className="text-base font-semibold tracking-normal">{inscripcion.postulante?.nombre_completo ?? 'Sin postulante'}</h2>
                                    <p className="text-sm text-muted-foreground">
                                        CI {inscripcion.postulante?.ci ?? '-'} / {inscripcion.postulante?.correo ?? '-'}
                                    </p>
                                </div>
                                <Badge variant={estadoVariant(inscripcion.estado)}>{inscripcion.estado_label}</Badge>
                            </div>

                            <dl className="grid gap-3 text-sm md:grid-cols-2 xl:grid-cols-3">
                                <Dato label="Telefono" value={inscripcion.postulante?.telefono ?? 'Sin telefono'} />
                                <Dato label="Colegio" value={inscripcion.postulante?.colegio_procedencia ?? 'Sin colegio'} />
                                <Dato label="Ano bachillerato" value={inscripcion.postulante?.anio_bachillerato?.toString() ?? 'No registrado'} />
                                <Dato label="Postulante extranjero" value={inscripcion.postulante?.es_extranjero ? 'Si' : 'No'} />
                                <Dato label="Estado postulante" value={inscripcion.postulante?.estado_label ?? '-'} />
                                <Dato label="Fecha inscripcion" value={inscripcion.fecha_inscripcion ?? 'Pendiente'} />
                            </dl>
                        </section>

                        <section className="rounded-lg border p-4">
                            <h2 className="mb-3 text-base font-semibold tracking-normal">Gestion y carreras</h2>
                            <dl className="grid gap-3 text-sm md:grid-cols-3">
                                <Dato label="Gestion" value={inscripcion.gestion ? `${inscripcion.gestion.nombre_gestion} / ${inscripcion.gestion.convocatoria}` : 'Sin gestion'} />
                                <Dato label="Primera opcion" value={inscripcion.carrera_primera ?? 'No registrada'} />
                                <Dato label="Segunda opcion" value={inscripcion.carrera_segunda ?? 'No registrada'} />
                            </dl>
                        </section>

                        <section className="rounded-lg border p-4">
                            <h2 className="mb-3 text-base font-semibold tracking-normal">Requisitos cumplidos</h2>
                            <div className="grid gap-2 md:grid-cols-2">
                                {inscripcion.requisitos.map((requisito) => (
                                    <div key={requisito.id_inscripcion_requisito} className="flex items-start justify-between gap-3 rounded-md border px-3 py-2 text-sm">
                                        <div>
                                            <div className="font-medium">{requisito.nombre_requisito ?? 'Requisito'}</div>
                                            <div className="text-muted-foreground">
                                                {requisito.origen} {requisito.cumplido_en ? `/ ${requisito.cumplido_en}` : ''}
                                            </div>
                                        </div>
                                        <Badge variant={requisito.cumplido ? 'default' : 'secondary'}>{requisito.cumplido ? 'Cumplido' : 'Pendiente'}</Badge>
                                    </div>
                                ))}

                                {inscripcion.requisitos.length === 0 && <div className="rounded-md border px-3 py-2 text-sm text-muted-foreground">Sin requisitos registrados.</div>}
                            </div>
                        </section>
                    </div>

                    <aside className="flex flex-col gap-4">
                        <section className="rounded-lg border p-4">
                            <h2 className="mb-3 text-base font-semibold tracking-normal">Pago</h2>
                            {inscripcion.pago ? (
                                <dl className="grid gap-3 text-sm">
                                    <div className="flex items-center justify-between gap-3 rounded-md border px-3 py-2">
                                        <span className="text-muted-foreground">Estado</span>
                                        <Badge variant={pagoVariant(inscripcion.pago.estado)}>{inscripcion.pago.estado_label}</Badge>
                                    </div>
                                    <Dato label="Monto" value={inscripcion.pago.monto_label} />
                                    <Dato label="Proveedor" value={inscripcion.pago.proveedor} />
                                    <Dato label="Comprobante" value={inscripcion.pago.codigo_comprobante ?? 'Sin comprobante'} />
                                    <Dato label="Pagado en" value={inscripcion.pago.pagado_en ?? 'Pendiente'} />
                                </dl>
                            ) : (
                                <div className="rounded-md border px-3 py-2 text-sm text-muted-foreground">La inscripcion no tiene pago asociado.</div>
                            )}
                        </section>

                        <section className="rounded-lg border p-4">
                            <h2 className="mb-3 text-base font-semibold tracking-normal">Usuario postulante</h2>
                            {inscripcion.postulante?.usuario ? (
                                <dl className="grid gap-3 text-sm">
                                    <Dato label="Usuario" value={`#${inscripcion.postulante.usuario.id_usuario}`} />
                                    <div className="flex items-center justify-between gap-3 rounded-md border px-3 py-2">
                                        <span className="text-muted-foreground">Estado</span>
                                        <Badge variant={inscripcion.postulante.usuario.estado === 'activo' ? 'default' : 'secondary'}>
                                            {inscripcion.postulante.usuario.estado_label}
                                        </Badge>
                                    </div>
                                </dl>
                            ) : (
                                <div className="rounded-md border px-3 py-2 text-sm text-muted-foreground">La cuenta se genera cuando la inscripcion queda confirmada.</div>
                            )}
                        </section>

                        <form onSubmit={submit} className="rounded-lg border p-4">
                            <h2 className="mb-3 text-base font-semibold tracking-normal">Estado administrativo</h2>

                            <div className="grid gap-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="estado">Estado</Label>
                                    <Select value={data.estado} onValueChange={(value) => setData('estado', value)}>
                                        <SelectTrigger id="estado">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {opciones.estadosInscripcion.map((estado) => (
                                                <SelectItem key={estado.value} value={estado.value}>
                                                    {estado.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.estado} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="observacion">Observacion</Label>
                                    <textarea
                                        id="observacion"
                                        value={data.observacion}
                                        onChange={(event) => setData('observacion', event.target.value)}
                                        className="min-h-28 w-full resize-y rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                        maxLength={500}
                                    />
                                    <InputError message={errors.observacion} />
                                </div>

                                <Button type="submit" disabled={processing}>
                                    <Save />
                                    Guardar cambios
                                </Button>
                            </div>
                        </form>

                        <div className="rounded-lg border p-4 text-sm text-muted-foreground">
                            <div className="mb-1 flex items-center gap-2 font-medium text-foreground">
                                <CheckCircle2 className="h-4 w-4" />
                                Criterio de acceso
                            </div>
                            La cuenta del postulante puede existir, pero se mantiene inactiva hasta que la planificacion de grupos habilite su acceso.
                        </div>
                    </aside>
                </div>
            </div>
        </AppLayout>
    );
}

function Dato({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-md border px-3 py-2">
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="font-medium">{value}</dd>
        </div>
    );
}
