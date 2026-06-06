import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { KeyRound, LoaderCircle, Save } from 'lucide-react';
import { FormEventHandler } from 'react';

import { type OpcionesUsuario, type UsuarioGestionado } from './types';

type EditProps = {
    usuarioGestionado: UsuarioGestionado;
    opciones: OpcionesUsuario;
    status?: string;
};

type EditarUsuarioForm = {
    nombre: string;
    ci: string;
    correo: string;
    rol: string;
    estado: string;
};

type ContrasenaUsuarioForm = {
    password: string;
    password_confirmation: string;
};

export default function Edit({ usuarioGestionado, opciones, status }: EditProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Usuarios',
            href: '/usuarios',
        },
        {
            title: usuarioGestionado.nombre,
            href: `/usuarios/${usuarioGestionado.id_usuario}/editar`,
        },
    ];

    const usuarioForm = useForm<EditarUsuarioForm>({
        nombre: usuarioGestionado.nombre,
        ci: usuarioGestionado.ci ?? '',
        correo: usuarioGestionado.correo,
        rol: usuarioGestionado.rol,
        estado: usuarioGestionado.estado,
    });

    const contrasenaForm = useForm<ContrasenaUsuarioForm>({
        password: '',
        password_confirmation: '',
    });

    const guardarUsuario: FormEventHandler = (event) => {
        event.preventDefault();

        usuarioForm.put(route('cu02.usuarios.update', usuarioGestionado.id_usuario), {
            preserveScroll: true,
        });
    };

    const restablecerContrasena: FormEventHandler = (event) => {
        event.preventDefault();

        contrasenaForm.put(route('cu02.usuarios.password.update', usuarioGestionado.id_usuario), {
            preserveScroll: true,
            onFinish: () => contrasenaForm.reset('password', 'password_confirmation'),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${usuarioGestionado.nombre}`} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-xl font-semibold tracking-normal">Editar usuario</h1>
                    <p className="text-sm text-muted-foreground">{usuarioGestionado.correo}</p>
                </div>

                {status && <div className="rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700">{status}</div>}

                <div className="grid gap-4 xl:grid-cols-[minmax(0,1fr)_360px]">
                    <form onSubmit={guardarUsuario} className="rounded-lg border p-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="nombre">Nombre</Label>
                                <Input
                                    id="nombre"
                                    value={usuarioForm.data.nombre}
                                    onChange={(event) => usuarioForm.setData('nombre', event.target.value)}
                                />
                                <InputError message={usuarioForm.errors.nombre} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="ci">CI</Label>
                                <Input id="ci" value={usuarioForm.data.ci} onChange={(event) => usuarioForm.setData('ci', event.target.value)} />
                                <InputError message={usuarioForm.errors.ci} />
                            </div>

                            <div className="grid gap-2 md:col-span-2">
                                <Label htmlFor="correo">Correo electronico</Label>
                                <Input
                                    id="correo"
                                    type="email"
                                    value={usuarioForm.data.correo}
                                    onChange={(event) => usuarioForm.setData('correo', event.target.value)}
                                />
                                <InputError message={usuarioForm.errors.correo} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="rol">Rol</Label>
                                <Select value={usuarioForm.data.rol} onValueChange={(value) => usuarioForm.setData('rol', value)}>
                                    <SelectTrigger id="rol">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {opciones.roles.map((rol) => (
                                            <SelectItem key={rol.value} value={rol.value}>
                                                {rol.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={usuarioForm.errors.rol} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="estado">Estado</Label>
                                <Select value={usuarioForm.data.estado} onValueChange={(value) => usuarioForm.setData('estado', value)}>
                                    <SelectTrigger id="estado">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {opciones.estados.map((estado) => (
                                            <SelectItem key={estado.value} value={estado.value}>
                                                {estado.label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={usuarioForm.errors.estado} />
                            </div>
                        </div>

                        <div className="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                            <Button asChild variant="outline">
                                <Link href={route('cu02.usuarios.index')}>Volver</Link>
                            </Button>
                            <Button type="submit" disabled={usuarioForm.processing}>
                                {usuarioForm.processing ? <LoaderCircle className="animate-spin" /> : <Save />}
                                Guardar
                            </Button>
                        </div>
                    </form>

                    <form onSubmit={restablecerContrasena} className="rounded-lg border p-4">
                        <div className="mb-4 flex items-center gap-2">
                            <KeyRound className="size-4" />
                            <h2 className="text-base font-semibold tracking-normal">Contrasena</h2>
                        </div>

                        <div className="grid gap-4">
                            <div className="grid gap-2">
                                <Label htmlFor="password">Nueva contrasena</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    value={contrasenaForm.data.password}
                                    onChange={(event) => contrasenaForm.setData('password', event.target.value)}
                                    autoComplete="new-password"
                                />
                                <InputError message={contrasenaForm.errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">Confirmar contrasena</Label>
                                <Input
                                    id="password_confirmation"
                                    type="password"
                                    value={contrasenaForm.data.password_confirmation}
                                    onChange={(event) => contrasenaForm.setData('password_confirmation', event.target.value)}
                                    autoComplete="new-password"
                                />
                                <InputError message={contrasenaForm.errors.password_confirmation} />
                            </div>
                        </div>

                        <Button type="submit" className="mt-6 w-full" disabled={contrasenaForm.processing}>
                            {contrasenaForm.processing ? <LoaderCircle className="animate-spin" /> : <KeyRound />}
                            Restablecer
                        </Button>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
