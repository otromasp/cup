import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import { FormularioDocente } from './formulario';
import { type DocenteForm, type OpcionesDocente } from './types';

type CreateProps = {
    docente: DocenteForm;
    opciones: OpcionesDocente;
    status?: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Docentes',
        href: '/docentes',
    },
    {
        title: 'Nuevo docente',
        href: '/docentes/crear',
    },
];

export default function Create({ docente, opciones, status }: CreateProps) {
    const { data, setData, post, processing, errors } = useForm<DocenteForm>(docente);

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        post(route('cu05.docentes.store'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo docente" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-xl font-semibold tracking-normal">Nuevo docente</h1>
                    <p className="text-sm text-muted-foreground">Perfil academico y materias habilitadas</p>
                </div>

                {status && <div className="rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700">{status}</div>}

                <FormularioDocente
                    data={data}
                    opciones={opciones}
                    errors={errors as Record<string, string | undefined>}
                    processing={processing}
                    submitLabel="Guardar"
                    cancelHref={route('cu05.docentes.index')}
                    onSubmit={submit}
                    setData={setData}
                />
            </div>
        </AppLayout>
    );
}
