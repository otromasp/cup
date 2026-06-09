import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import { FormularioGestionCup } from './formulario';
import { type GestionCupForm, type OpcionesGestionCup } from './types';

type EditProps = {
    gestion: GestionCupForm;
    opciones: OpcionesGestionCup;
    status?: string;
};

export default function Edit({ gestion, opciones, status }: EditProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Gestion CUP',
            href: '/gestion-cup',
        },
        {
            title: gestion.nombre_gestion,
            href: `/gestion-cup/${gestion.id_gestion}/editar`,
        },
    ];

    const { data, setData, put, processing, errors } = useForm<GestionCupForm>(gestion);

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        put(route('cu03.gestion-cup.update', gestion.id_gestion), {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${gestion.nombre_gestion}`} />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-xl font-semibold tracking-normal">Editar gestion CUP</h1>
                    <p className="text-muted-foreground text-sm">
                        {gestion.responsable ? `Responsable: ${gestion.responsable}` : gestion.convocatoria}
                    </p>
                </div>

                {status && <div className="rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700">{status}</div>}

                <FormularioGestionCup
                    data={data}
                    opciones={opciones}
                    errors={errors as Record<string, string | undefined>}
                    processing={processing}
                    submitLabel="Guardar cambios"
                    cancelHref={route('cu03.gestion-cup.index')}
                    onSubmit={submit}
                    setData={setData}
                />
            </div>
        </AppLayout>
    );
}
