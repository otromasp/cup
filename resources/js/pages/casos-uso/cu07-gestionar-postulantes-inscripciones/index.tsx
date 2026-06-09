import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type FormDataConvertible } from '@inertiajs/core';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Eye, RotateCcw, Search } from 'lucide-react';
import { FormEventHandler } from 'react';

import { type InscripcionesPaginadas, type InscripcionResumen, type OpcionInscripcion } from './types';

type IndexProps = {
    inscripciones: InscripcionesPaginadas;
    filtros: {
        buscar?: string;
        gestion_id?: string;
        estado?: string;
        carrera_id?: string;
    };
    opciones: {
        gestiones: OpcionInscripcion[];
        carreras: OpcionInscripcion[];
        estadosInscripcion: OpcionInscripcion[];
    };
    status?: string;
};

type FiltrosForm = {
    [key: string]: FormDataConvertible;
    buscar: string;
    gestion_id: string;
    estado: string;
    carrera_id: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Postulantes e inscripciones',
        href: '/postulantes-inscripciones',
    },
];

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

export default function Index({ inscripciones, filtros, opciones, status }: IndexProps) {
    const { data, setData, get, processing, errors } = useForm<FiltrosForm>({
        buscar: filtros.buscar ?? '',
        gestion_id: filtros.gestion_id ?? '',
        estado: filtros.estado ?? '',
        carrera_id: filtros.carrera_id ?? '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        get(route('cu07.postulantes-inscripciones.index'), {
            preserveState: true,
            replace: true,
        });
    };

    const limpiarFiltro = () => {
        setData('buscar', '');
        setData('gestion_id', '');
        setData('estado', '');
        setData('carrera_id', '');
        router.get(route('cu07.postulantes-inscripciones.index'), {}, { preserveState: true, replace: true });
    };

    const filtrosActivos = Boolean(data.buscar || data.gestion_id || data.estado || data.carrera_id);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Gestionar postulantes e inscripciones" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-col gap-1">
                    <h1 className="text-xl font-semibold tracking-normal">Gestionar postulantes e inscripciones</h1>
                    <p className="text-sm text-muted-foreground">Consulta, revision y estado administrativo de inscripciones CUP</p>
                </div>

                {status && <div className="rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700">{status}</div>}

                <form onSubmit={submit} className="grid gap-2 xl:grid-cols-[1fr_220px_220px_220px_auto]">
                    <div className="min-w-0">
                        <Input
                            value={data.buscar}
                            onChange={(event) => setData('buscar', event.target.value)}
                            placeholder="Buscar por CI, nombre, correo o codigo"
                        />
                        <InputError message={errors.buscar} className="mt-2" />
                    </div>

                    <div>
                        <Select value={data.gestion_id || 'todas'} onValueChange={(value) => setData('gestion_id', value === 'todas' ? '' : value)}>
                            <SelectTrigger>
                                <SelectValue placeholder="Gestion CUP" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="todas">Todas las gestiones</SelectItem>
                                {opciones.gestiones.map((gestion) => (
                                    <SelectItem key={gestion.value} value={gestion.value}>
                                        {gestion.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.gestion_id} className="mt-2" />
                    </div>

                    <div>
                        <Select value={data.estado || 'todos'} onValueChange={(value) => setData('estado', value === 'todos' ? '' : value)}>
                            <SelectTrigger>
                                <SelectValue placeholder="Estado" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="todos">Todos los estados</SelectItem>
                                {opciones.estadosInscripcion.map((estado) => (
                                    <SelectItem key={estado.value} value={estado.value}>
                                        {estado.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.estado} className="mt-2" />
                    </div>

                    <div>
                        <Select value={data.carrera_id || 'todas'} onValueChange={(value) => setData('carrera_id', value === 'todas' ? '' : value)}>
                            <SelectTrigger>
                                <SelectValue placeholder="Carrera" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="todas">Todas las carreras</SelectItem>
                                {opciones.carreras.map((carrera) => (
                                    <SelectItem key={carrera.value} value={carrera.value}>
                                        {carrera.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.carrera_id} className="mt-2" />
                    </div>

                    <div className="flex gap-2">
                        <Button type="submit" variant="outline" disabled={processing}>
                            <Search />
                            Buscar
                        </Button>
                        {filtrosActivos && (
                            <Button type="button" variant="ghost" onClick={limpiarFiltro}>
                                <RotateCcw />
                                Limpiar
                            </Button>
                        )}
                    </div>
                </form>

                <div className="overflow-hidden rounded-lg border">
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[1120px] text-sm">
                            <thead className="bg-muted/50 text-left">
                                <tr>
                                    <th className="px-4 py-3 font-medium">Postulante</th>
                                    <th className="px-4 py-3 font-medium">Inscripcion</th>
                                    <th className="px-4 py-3 font-medium">Carreras</th>
                                    <th className="px-4 py-3 font-medium">Pago</th>
                                    <th className="px-4 py-3 font-medium">Usuario</th>
                                    <th className="px-4 py-3 text-right font-medium">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                {inscripciones.data.map((inscripcion) => (
                                    <FilaInscripcion key={inscripcion.id_inscripcion} inscripcion={inscripcion} />
                                ))}

                                {inscripciones.data.length === 0 && (
                                    <tr>
                                        <td colSpan={6} className="px-4 py-8 text-center text-muted-foreground">
                                            No se encontraron inscripciones.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div className="flex flex-col gap-3 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                    <span>{inscripciones.total > 0 ? `Mostrando ${inscripciones.from} a ${inscripciones.to} de ${inscripciones.total}` : 'Sin registros'}</span>
                    <div className="flex flex-wrap gap-2">
                        {inscripciones.links.map((link, index) => (
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

function FilaInscripcion({ inscripcion }: { inscripcion: InscripcionResumen }) {
    return (
        <tr className="border-t">
            <td className="px-4 py-3">
                <div className="font-medium">{inscripcion.postulante?.nombre_completo ?? 'Sin postulante'}</div>
                <div className="text-muted-foreground">
                    CI {inscripcion.postulante?.ci ?? '-'} / {inscripcion.postulante?.correo ?? '-'}
                </div>
                <div className="text-muted-foreground">{inscripcion.postulante?.telefono ?? 'Sin telefono'}</div>
            </td>
            <td className="px-4 py-3">
                <div className="font-medium">{inscripcion.codigo_inscripcion}</div>
                <div className="text-muted-foreground">{inscripcion.gestion ? `${inscripcion.gestion.nombre_gestion} / ${inscripcion.gestion.convocatoria}` : 'Sin gestion'}</div>
                <div className="mt-1 flex items-center gap-2">
                    <Badge variant={estadoVariant(inscripcion.estado)}>{inscripcion.estado_label}</Badge>
                    {inscripcion.fecha_inscripcion && <span className="text-muted-foreground">{inscripcion.fecha_inscripcion}</span>}
                </div>
            </td>
            <td className="px-4 py-3">
                <div>{inscripcion.carrera_primera ?? 'Sin primera opcion'}</div>
                <div className="text-muted-foreground">{inscripcion.carrera_segunda ?? 'Sin segunda opcion'}</div>
            </td>
            <td className="px-4 py-3">
                {inscripcion.pago ? (
                    <>
                        <Badge variant={pagoVariant(inscripcion.pago.estado)}>{inscripcion.pago.estado_label}</Badge>
                        <div className="mt-1 text-muted-foreground">{inscripcion.pago.monto_label}</div>
                        <div className="text-muted-foreground">{inscripcion.pago.codigo_comprobante ?? 'Sin comprobante'}</div>
                    </>
                ) : (
                    <Badge variant="secondary">Sin pago</Badge>
                )}
            </td>
            <td className="px-4 py-3">
                {inscripcion.postulante?.usuario ? (
                    <>
                        <Badge variant={inscripcion.postulante.usuario.estado === 'activo' ? 'default' : 'secondary'}>
                            {inscripcion.postulante.usuario.estado_label}
                        </Badge>
                        <div className="mt-1 text-muted-foreground">Usuario #{inscripcion.postulante.usuario.id_usuario}</div>
                    </>
                ) : (
                    <Badge variant="outline">No generado</Badge>
                )}
            </td>
            <td className="px-4 py-3">
                <div className="flex justify-end gap-2">
                    <Button asChild size="icon" variant="outline" title="Ver detalle">
                        <Link href={route('cu07.postulantes-inscripciones.show', inscripcion.id_inscripcion)}>
                            <Eye />
                            <span className="sr-only">Ver detalle</span>
                        </Link>
                    </Button>
                </div>
            </td>
        </tr>
    );
}
