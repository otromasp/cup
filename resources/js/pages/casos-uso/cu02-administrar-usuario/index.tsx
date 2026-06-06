import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Copy, Pencil, Plus, Power, RotateCcw, Search } from 'lucide-react';
import { FormEventHandler } from 'react';

import { type OpcionesUsuario, type UsuarioGestionado, type UsuariosPaginados } from './types';

type IndexProps = {
    usuarios: UsuariosPaginados;
    filtros: {
        buscar?: string;
    };
    opciones: OpcionesUsuario;
    status?: string;
    contrasenaInicial?: string;
};

type FiltrosForm = {
    buscar: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Usuarios',
        href: '/usuarios',
    },
];

export default function Index({ usuarios, filtros, status, contrasenaInicial }: IndexProps) {
    const { data, setData, get, processing, errors } = useForm<FiltrosForm>({
        buscar: filtros.buscar ?? '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        get(route('cu02.usuarios.index'), {
            preserveState: true,
            replace: true,
        });
    };

    const cambiarEstado = (usuario: UsuarioGestionado) => {
        const estado = usuario.estado === 'activo' ? 'inactivo' : 'activo';

        router.patch(
            route('cu02.usuarios.status.update', usuario.id_usuario),
            { estado },
            {
                preserveScroll: true,
            },
        );
    };

    const limpiarFiltro = () => {
        setData('buscar', '');
        router.get(route('cu02.usuarios.index'), {}, { preserveState: true, replace: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Administrar usuario" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold tracking-normal">Administrar usuario</h1>
                        <p className="text-sm text-muted-foreground">Cuentas, roles y estados de acceso</p>
                    </div>

                    <Button asChild>
                        <Link href={route('cu02.usuarios.create')}>
                            <Plus />
                            Nuevo usuario
                        </Link>
                    </Button>
                </div>

                {status && <div className="rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700">{status}</div>}

                {contrasenaInicial && (
                    <div className="flex flex-col gap-2 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800 sm:flex-row sm:items-center sm:justify-between">
                        <span>
                            Contrasena inicial generada: <code className="font-mono font-semibold">{contrasenaInicial}</code>
                        </span>
                        <Button type="button" size="sm" variant="outline" onClick={() => navigator.clipboard?.writeText(contrasenaInicial)}>
                            <Copy />
                            Copiar
                        </Button>
                    </div>
                )}

                <form onSubmit={submit} className="flex flex-col gap-2 sm:flex-row">
                    <div className="min-w-0 flex-1">
                        <Input
                            value={data.buscar}
                            onChange={(event) => setData('buscar', event.target.value)}
                            placeholder="Buscar por nombre, correo o CI"
                        />
                        <InputError message={errors.buscar} className="mt-2" />
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" variant="outline" disabled={processing}>
                            <Search />
                            Buscar
                        </Button>
                        {data.buscar && (
                            <Button type="button" variant="ghost" onClick={limpiarFiltro}>
                                <RotateCcw />
                                Limpiar
                            </Button>
                        )}
                    </div>
                </form>

                <div className="overflow-hidden rounded-lg border">
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[780px] text-sm">
                            <thead className="bg-muted/50 text-left">
                                <tr>
                                    <th className="px-4 py-3 font-medium">Nombre</th>
                                    <th className="px-4 py-3 font-medium">Correo</th>
                                    <th className="px-4 py-3 font-medium">CI</th>
                                    <th className="px-4 py-3 font-medium">Rol</th>
                                    <th className="px-4 py-3 font-medium">Estado</th>
                                    <th className="px-4 py-3 text-right font-medium">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                {usuarios.data.map((usuario) => (
                                    <tr key={usuario.id_usuario} className="border-t">
                                        <td className="px-4 py-3 font-medium">{usuario.nombre}</td>
                                        <td className="px-4 py-3 text-muted-foreground">{usuario.correo}</td>
                                        <td className="px-4 py-3 text-muted-foreground">{usuario.ci ?? 'Sin CI'}</td>
                                        <td className="px-4 py-3">{usuario.rol_label}</td>
                                        <td className="px-4 py-3">
                                            <Badge variant={usuario.estado === 'activo' ? 'default' : 'outline'}>{usuario.estado_label}</Badge>
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex justify-end gap-2">
                                                <Button asChild size="icon" variant="outline" title="Editar usuario">
                                                    <Link href={route('cu02.usuarios.edit', usuario.id_usuario)}>
                                                        <Pencil />
                                                        <span className="sr-only">Editar usuario</span>
                                                    </Link>
                                                </Button>
                                                <Button
                                                    type="button"
                                                    size="icon"
                                                    variant={usuario.estado === 'activo' ? 'outline' : 'secondary'}
                                                    title={usuario.estado === 'activo' ? 'Desactivar usuario' : 'Activar usuario'}
                                                    onClick={() => cambiarEstado(usuario)}
                                                >
                                                    <Power />
                                                    <span className="sr-only">
                                                        {usuario.estado === 'activo' ? 'Desactivar usuario' : 'Activar usuario'}
                                                    </span>
                                                </Button>
                                            </div>
                                        </td>
                                    </tr>
                                ))}

                                {usuarios.data.length === 0 && (
                                    <tr>
                                        <td colSpan={6} className="px-4 py-8 text-center text-muted-foreground">
                                            No se encontraron usuarios.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div className="flex flex-col gap-3 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                    <span>
                        {usuarios.total > 0
                            ? `Mostrando ${usuarios.from} a ${usuarios.to} de ${usuarios.total}`
                            : 'Sin registros'}
                    </span>
                    <div className="flex flex-wrap gap-2">
                        {usuarios.links.map((link, index) => (
                            <Button key={`${link.label}-${index}`} asChild={Boolean(link.url)} size="sm" variant={link.active ? 'default' : 'outline'} disabled={!link.url}>
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
