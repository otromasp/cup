import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import { FormularioDocente } from './formulario';
import { type DocenteForm, type OpcionesDocente } from './types';

type EditProps = {
    docente: DocenteForm;
    opciones: OpcionesDocente;
    status?: string;
};

export default function Edit({ docente, opciones, status }: EditProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Docentes',
            href: '/docentes',
        },
        {
            title: `${docente.nombres} ${docente.apellidos}`.trim(),
            href: `/docentes/${docente.id_docente}/editar`,
        },
    ];

    const { data, setData, put, processing, errors } = useForm<DocenteForm>(docente);

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        put(route('cu05.docentes.update', docente.id_docente), {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${docente.nombres} ${docente.apellidos}`} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-xl font-semibold tracking-normal">Editar docente</h1>
                    <p className="text-sm text-muted-foreground">{docente.correo}</p>
                </div>

                {status && <div className="rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700">{status}</div>}

                <FormularioDocente
                    data={data}
                    opciones={opciones}
                    errors={errors as Record<string, string | undefined>}
                    processing={processing}
                    submitLabel="Guardar cambios"
                    cancelHref={route('cu05.docentes.index')}
                    onSubmit={submit}
                    setData={setData}
                />
            </div>
        </AppLayout>
    );
}
