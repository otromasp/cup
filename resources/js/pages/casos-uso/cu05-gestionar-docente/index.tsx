import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type FormDataConvertible } from '@inertiajs/core';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, Power, RotateCcw, Search } from 'lucide-react';
import { FormEventHandler } from 'react';

import { type DocenteResumen, type DocentesPaginados, type OpcionDocente } from './types';

type IndexProps = {
    docentes: DocentesPaginados;
    filtros: {
        buscar?: string;
        estado?: string;
    };
    opciones: {
        estadosContratacion: OpcionDocente[];
    };
    status?: string;
};

type FiltrosForm = {
    [key: string]: FormDataConvertible;
    buscar: string;
    estado: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Docentes',
        href: '/docentes',
    },
];

const estadoVariant = (estado: string): 'default' | 'secondary' | 'outline' => {
    if (estado === 'habilitado') {
        return 'default';
    }

    if (estado === 'inactivo') {
        return 'secondary';
    }

    return 'outline';
};

const etiqueta = (valor: string) => valor.charAt(0).toUpperCase() + valor.slice(1);

export default function Index({ docentes, filtros, opciones, status }: IndexProps) {
    const { data, setData, get, processing, errors } = useForm<FiltrosForm>({
        buscar: filtros.buscar ?? '',
        estado: filtros.estado ?? '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        get(route('cu05.docentes.index'), {
            preserveState: true,
            replace: true,
        });
    };

    const limpiarFiltro = () => {
        setData('buscar', '');
        setData('estado', '');
        router.get(route('cu05.docentes.index'), {}, { preserveState: true, replace: true });
    };

    const cambiarEstado = (docente: DocenteResumen) => {
        const estado = docente.estado_contratacion === 'habilitado' ? 'inactivo' : 'habilitado';

        router.patch(
            route('cu05.docentes.status.update', docente.id_docente),
            { estado_contratacion: estado },
            {
                preserveScroll: true,
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Gestionar docente" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold tracking-normal">Gestionar docente</h1>
                        <p className="text-sm text-muted-foreground">Perfil academico, materias y disponibilidad</p>
                    </div>

                    <Button asChild>
                        <Link href={route('cu05.docentes.create')}>
                            <Plus />
                            Nuevo docente
                        </Link>
                    </Button>
                </div>

                {status && <div className="rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700">{status}</div>}

                <form onSubmit={submit} className="grid gap-2 lg:grid-cols-[1fr_240px_auto]">
                    <div className="min-w-0">
                        <Input
                            value={data.buscar}
                            onChange={(event) => setData('buscar', event.target.value)}
                            placeholder="Buscar por nombre, CI, correo, area o materia"
                        />
                        <InputError message={errors.buscar} className="mt-2" />
                    </div>

                    <div>
                        <Select value={data.estado || 'todos'} onValueChange={(value) => setData('estado', value === 'todos' ? '' : value)}>
                            <SelectTrigger>
                                <SelectValue placeholder="Estado" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="todos">Todos los estados</SelectItem>
                                {opciones.estadosContratacion.map((estado) => (
                                    <SelectItem key={estado.value} value={estado.value}>
                                        {estado.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.estado} className="mt-2" />
                    </div>

                    <div className="flex gap-2">
                        <Button type="submit" variant="outline" disabled={processing}>
                            <Search />
                            Buscar
                        </Button>
                        {(data.buscar || data.estado) && (
                            <Button type="button" variant="ghost" onClick={limpiarFiltro}>
                                <RotateCcw />
                                Limpiar
                            </Button>
                        )}
                    </div>
                </form>

                <div className="overflow-hidden rounded-lg border">
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[1080px] text-sm">
                            <thead className="bg-muted/50 text-left">
                                <tr>
                                    <th className="px-4 py-3 font-medium">Docente</th>
                                    <th className="px-4 py-3 font-medium">Contacto</th>
                                    <th className="px-4 py-3 font-medium">Materias</th>
                                    <th className="px-4 py-3 font-medium">Disponibilidad</th>
                                    <th className="px-4 py-3 font-medium">Estado</th>
                                    <th className="px-4 py-3 text-right font-medium">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                {docentes.data.map((docente) => (
                                    <tr key={docente.id_docente} className="border-t">
                                        <td className="px-4 py-3">
                                            <div className="font-medium">{docente.nombre_completo}</div>
                                            <div className="text-muted-foreground">
                                                CI {docente.ci} / {docente.profesion}
                                            </div>
                                            <div className="text-muted-foreground">{docente.area_especialidad}</div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <div>{docente.correo}</div>
                                            <div className="text-muted-foreground">{docente.telefono ?? 'Sin telefono'}</div>
                                            <div className="text-muted-foreground">{docente.usuario ? 'Cuenta vinculada' : 'Sin cuenta vinculada'}</div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex max-w-sm flex-wrap gap-1">
                                                {docente.materias.slice(0, 4).map((materia) => (
                                                    <Badge key={materia} variant="outline">
                                                        {materia}
                                                    </Badge>
                                                ))}
                                                {docente.materias.length > 4 && <Badge variant="secondary">+{docente.materias.length - 4}</Badge>}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <div>{docente.disponibilidades_count} bloques</div>
                                            <div className="mt-1 flex flex-wrap gap-1">
                                                {docente.modalidades.map((modalidad) => (
                                                    <Badge key={modalidad} variant="outline">
                                                        {etiqueta(modalidad)}
                                                    </Badge>
                                                ))}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge variant={estadoVariant(docente.estado_contratacion)}>{docente.estado_contratacion_label}</Badge>
                                            <div className="mt-1 text-muted-foreground">Max. {docente.maximo_grupos_asignables} grupos</div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex justify-end gap-2">
                                                <Button asChild size="icon" variant="outline" title="Editar docente">
                                                    <Link href={route('cu05.docentes.edit', docente.id_docente)}>
                                                        <Pencil />
                                                        <span className="sr-only">Editar docente</span>
                                                    </Link>
                                                </Button>
                                                <Button
                                                    type="button"
                                                    size="icon"
                                                    variant={docente.estado_contratacion === 'habilitado' ? 'outline' : 'secondary'}
                                                    title={docente.estado_contratacion === 'habilitado' ? 'Inactivar docente' : 'Habilitar docente'}
                                                    onClick={() => cambiarEstado(docente)}
                                                >
                                                    <Power />
                                                    <span className="sr-only">
                                                        {docente.estado_contratacion === 'habilitado' ? 'Inactivar docente' : 'Habilitar docente'}
                                                    </span>
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}

                                {docentes.data.length === 0 && (
                                    <tr>
                                        <td colSpan={6} className="px-4 py-8 text-center text-muted-foreground">
                                            No se encontraron docentes.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div className="flex flex-col gap-3 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                    <span>{docentes.total > 0 ? `Mostrando ${docentes.from} a ${docentes.to} de ${docentes.total}` : 'Sin registros'}</span>
                    <div className="flex flex-wrap gap-2">
                        {docentes.links.map((link, index) => (
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
