import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import { FormularioGestionCup } from './formulario';
import { type GestionCupForm, type OpcionesGestionCup } from './types';

type CreateProps = {
    gestion: GestionCupForm;
    opciones: OpcionesGestionCup;
    status?: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Gestion CUP',
        href: '/gestion-cup',
    },
    {
        title: 'Nueva gestion',
        href: '/gestion-cup/crear',
    },
];

export default function Create({ gestion, opciones, status }: CreateProps) {
    const { data, setData, post, processing, errors } = useForm<GestionCupForm>(gestion);

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        post(route('cu03.gestion-cup.store'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva gestion CUP" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-xl font-semibold tracking-normal">Nueva gestion CUP</h1>
                    <p className="text-muted-foreground text-sm">Configuracion base del curso preuniversitario</p>
                </div>

                {status && <div className="rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700">{status}</div>}

                <FormularioGestionCup
                    data={data}
                    opciones={opciones}
                    errors={errors as Record<string, string | undefined>}
                    processing={processing}
                    submitLabel="Guardar"
                    cancelHref={route('cu03.gestion-cup.index')}
                    onSubmit={submit}
                    setData={setData}
                />
            </div>
        </AppLayout>
    );
}
