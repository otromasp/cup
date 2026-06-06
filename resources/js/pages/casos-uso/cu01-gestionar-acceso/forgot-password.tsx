import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

type ForgotPasswordForm = {
    email: string;
};

export default function ForgotPassword({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm<ForgotPasswordForm>({
        email: '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        post(route('password.email'));
    };

    return (
        <AuthLayout title="Recuperar contrasena" description="Ingresa tu correo para recibir el enlace de recuperacion">
            <Head title="Recuperar contrasena" />

            {status && <div className="mb-4 text-center text-sm font-medium text-green-600">{status}</div>}

            <div className="space-y-6">
                <form onSubmit={submit}>
                    <div className="grid gap-2">
                        <Label htmlFor="email">Correo electronico</Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            required
                            autoComplete="email"
                            value={data.email}
                            autoFocus
                            onChange={(event) => setData('email', event.target.value)}
                            placeholder="correo@ejemplo.com"
                        />

                        <InputError message={errors.email} />
                    </div>

                    <div className="my-6 flex items-center justify-start">
                        <Button className="w-full" disabled={processing}>
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                            Enviar enlace
                        </Button>
                    </div>
                </form>

                <div className="text-muted-foreground space-x-1 text-center text-sm">
                    <span>Volver a</span>
                    <TextLink href={route('login')}>iniciar sesion</TextLink>
                </div>
            </div>
        </AuthLayout>
    );
}
