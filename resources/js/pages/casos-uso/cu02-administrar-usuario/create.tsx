import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { LoaderCircle, Save } from 'lucide-react';
import { FormEventHandler } from 'react';

import { type OpcionesUsuario } from './types';

type CreateProps = {
    opciones: OpcionesUsuario;
    status?: string;
};

type CrearUsuarioForm = {
    nombre: string;
    ci: string;
    correo: string;
    rol: string;
    estado: string;
    password: string;
    password_confirmation: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Usuarios',
        href: '/usuarios',
    },
    {
        title: 'Nuevo usuario',
        href: '/usuarios/crear',
    },
];

export default function Create({ opciones, status }: CreateProps) {
    const { data, setData, post, processing, errors, reset } = useForm<CrearUsuarioForm>({
        nombre: '',
        ci: '',
        correo: '',
        rol: 'coordinador',
        estado: 'activo',
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        post(route('cu02.usuarios.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo usuario" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-xl font-semibold tracking-normal">Nuevo usuario</h1>
                    <p className="text-sm text-muted-foreground">Registro de cuenta interna</p>
                </div>

                {status && <div className="rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700">{status}</div>}

                <form onSubmit={submit} className="max-w-3xl rounded-lg border p-4">
                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="nombre">Nombre</Label>
                            <Input id="nombre" value={data.nombre} onChange={(event) => setData('nombre', event.target.value)} />
                            <InputError message={errors.nombre} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="ci">CI</Label>
                            <Input id="ci" value={data.ci} onChange={(event) => setData('ci', event.target.value)} />
                            <InputError message={errors.ci} />
                        </div>

                        <div className="grid gap-2 md:col-span-2">
                            <Label htmlFor="correo">Correo electronico</Label>
                            <Input id="correo" type="email" value={data.correo} onChange={(event) => setData('correo', event.target.value)} />
                            <InputError message={errors.correo} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="rol">Rol</Label>
                            <Select value={data.rol} onValueChange={(value) => setData('rol', value)}>
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
                            <InputError message={errors.rol} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="estado">Estado</Label>
                            <Select value={data.estado} onValueChange={(value) => setData('estado', value)}>
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
                            <InputError message={errors.estado} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">Contrasena inicial</Label>
                            <Input
                                id="password"
                                type="password"
                                placeholder="Generar automaticamente"
                                value={data.password}
                                onChange={(event) => setData('password', event.target.value)}
                                autoComplete="new-password"
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">Confirmar contrasena</Label>
                            <Input
                                id="password_confirmation"
                                type="password"
                                placeholder="Solo si escribes una contrasena"
                                value={data.password_confirmation}
                                onChange={(event) => setData('password_confirmation', event.target.value)}
                                autoComplete="new-password"
                            />
                            <InputError message={errors.password_confirmation} />
                        </div>
                    </div>

                    <div className="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <Button asChild variant="outline">
                            <Link href={route('cu02.usuarios.index')}>Cancelar</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? <LoaderCircle className="animate-spin" /> : <Save />}
                            Guardar
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
