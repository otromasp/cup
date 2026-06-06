import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

type ResetPasswordProps = {
    token: string;
    email: string;
};

type ResetPasswordForm = {
    token: string;
    email: string;
    password: string;
    password_confirmation: string;
};

export default function ResetPassword({ token, email }: ResetPasswordProps) {
    const { data, setData, post, processing, errors, reset } = useForm<ResetPasswordForm>({
        token,
        email,
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        post(route('password.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <AuthLayout title="Nueva contrasena" description="Define una nueva contrasena para recuperar tu acceso">
            <Head title="Nueva contrasena" />

            <form onSubmit={submit}>
                <div className="grid gap-6">
                    <div className="grid gap-2">
                        <Label htmlFor="email">Correo electronico</Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            autoComplete="email"
                            value={data.email}
                            className="mt-1 block w-full"
                            readOnly
                            onChange={(event) => setData('email', event.target.value)}
                        />
                        <InputError message={errors.email} className="mt-2" />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password">Nueva contrasena</Label>
                        <Input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autoComplete="new-password"
                            value={data.password}
                            className="mt-1 block w-full"
                            autoFocus
                            onChange={(event) => setData('password', event.target.value)}
                            placeholder="Nueva contrasena"
                        />
                        <InputError message={errors.password} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password_confirmation">Confirmar contrasena</Label>
                        <Input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            required
                            autoComplete="new-password"
                            value={data.password_confirmation}
                            className="mt-1 block w-full"
                            onChange={(event) => setData('password_confirmation', event.target.value)}
                            placeholder="Confirmar contrasena"
                        />
                        <InputError message={errors.password_confirmation} className="mt-2" />
                    </div>

                    <Button type="submit" className="mt-4 w-full" disabled={processing}>
                        {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                        Restablecer contrasena
                    </Button>
                </div>
            </form>
        </AuthLayout>
    );
}
