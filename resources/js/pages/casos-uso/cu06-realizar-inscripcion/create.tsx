import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowRight, CircleAlert, Home, LoaderCircle, ReceiptText } from 'lucide-react';
import { FormEventHandler } from 'react';

import {
    type CarreraInscripcion,
    type DatosPreviosInscripcion,
    type GestionInscripcion,
    type InscripcionForm,
    type MontoPago,
    type RequisitoInscripcion,
    type TurnoInscripcion,
} from './types';

type CrearInscripcionProps = {
    gestion: GestionInscripcion | null;
    turnos: TurnoInscripcion[];
    carreras: CarreraInscripcion[];
    requisitos: RequisitoInscripcion[];
    montoPago: MontoPago;
    datosPrevios?: DatosPreviosInscripcion | null;
    status?: string;
};

const formularioInicial = (gestion: GestionInscripcion | null, datosPrevios?: DatosPreviosInscripcion | null): InscripcionForm => ({
    gestion_cup_id: gestion ? String(gestion.id_gestion) : '',
    ci: datosPrevios?.ci ?? '',
    nombres: datosPrevios?.nombres ?? '',
    apellidos: datosPrevios?.apellidos ?? '',
    correo: datosPrevios?.correo ?? '',
    telefono: datosPrevios?.telefono ?? '',
    colegio_procedencia: datosPrevios?.colegio_procedencia ?? '',
    anio_bachillerato: datosPrevios?.anio_bachillerato ?? '',
    es_extranjero: datosPrevios?.es_extranjero ?? false,
    turno_gestion_cup_id: datosPrevios?.turno_gestion_cup_id ?? '',
    carrera_primera_id: datosPrevios?.carrera_primera_id ?? '',
    carrera_segunda_id: datosPrevios?.carrera_segunda_id ?? '',
    requisitos_cumplidos: datosPrevios?.requisitos_cumplidos ?? [],
});

export default function CrearInscripcion({ gestion, turnos, carreras, requisitos, montoPago, datosPrevios, status }: CrearInscripcionProps) {
    const { data, setData, post, processing, errors } = useForm<InscripcionForm>(formularioInicial(gestion, datosPrevios));
    const requisitosVisibles = requisitos.filter((requisito) => requisito.aplica_a === 'todos' || data.es_extranjero);
    const requisitoPago = requisitosVisibles.find((requisito) => requisito.tipo_requisito === 'pago');
    const requisitosDeclarativos = requisitosVisibles.filter((requisito) => requisito.tipo_requisito !== 'pago');
    const carrerasSegundaOpcion = carreras.filter((carrera) => carrera.value !== data.carrera_primera_id);

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('cu06.inscripcion.store'));
    };

    const alternarRequisito = (id: string, checked: boolean) => {
        setData('requisitos_cumplidos', checked ? [...data.requisitos_cumplidos, id] : data.requisitos_cumplidos.filter((requisitoId) => requisitoId !== id));
    };

    return (
        <>
            <Head title="Inscripcion CUP" />

            <main className="min-h-screen bg-background px-4 py-6 text-foreground sm:px-6 lg:px-8">
                <div className="mx-auto flex max-w-6xl flex-col gap-5">
                    <header className="flex flex-col gap-3 border-b pb-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="text-sm text-muted-foreground">Sistema CUP</p>
                            <h1 className="text-2xl font-semibold tracking-normal">Inscripcion de postulante</h1>
                            {gestion && (
                                <p className="mt-1 text-sm text-muted-foreground">
                                    {gestion.nombre_gestion} / {gestion.convocatoria}
                                </p>
                            )}
                        </div>
                        <Button asChild variant="outline">
                            <Link href={route('home')}>
                                <Home />
                                Inicio
                            </Link>
                        </Button>
                    </header>

                    {status && (
                        <Alert>
                            <CircleAlert />
                            <AlertTitle>Estado</AlertTitle>
                            <AlertDescription>{status}</AlertDescription>
                        </Alert>
                    )}

                    {!gestion ? (
                        <Alert variant="destructive">
                            <CircleAlert />
                            <AlertTitle>Inscripcion no disponible</AlertTitle>
                            <AlertDescription>No existe una gestion CUP configurada para recibir postulantes.</AlertDescription>
                        </Alert>
                    ) : (
                        <form onSubmit={submit} className="flex flex-col gap-4">
                            <section className="rounded-lg border p-4">
                                <div className="mb-3">
                                    <h2 className="text-base font-semibold tracking-normal">Datos personales</h2>
                                    <p className="text-sm text-muted-foreground">Informacion minima para registrar al postulante en la gestion CUP</p>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="ci">CI</Label>
                                        <Input id="ci" value={data.ci} onChange={(event) => setData('ci', event.target.value)} />
                                        <InputError message={errors.ci} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="nombres">Nombres</Label>
                                        <Input id="nombres" value={data.nombres} onChange={(event) => setData('nombres', event.target.value)} />
                                        <InputError message={errors.nombres} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="apellidos">Apellidos</Label>
                                        <Input id="apellidos" value={data.apellidos} onChange={(event) => setData('apellidos', event.target.value)} />
                                        <InputError message={errors.apellidos} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="correo">Correo</Label>
                                        <Input id="correo" type="email" value={data.correo} onChange={(event) => setData('correo', event.target.value)} />
                                        <InputError message={errors.correo} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="telefono">Telefono</Label>
                                        <Input id="telefono" value={data.telefono} onChange={(event) => setData('telefono', event.target.value)} />
                                        <InputError message={errors.telefono} />
                                    </div>

                                    <div className="grid gap-2 xl:col-span-2">
                                        <Label htmlFor="colegio_procedencia">Colegio de procedencia</Label>
                                        <Input
                                            id="colegio_procedencia"
                                            value={data.colegio_procedencia}
                                            onChange={(event) => setData('colegio_procedencia', event.target.value)}
                                        />
                                        <InputError message={errors.colegio_procedencia} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="anio_bachillerato">Ano de bachillerato</Label>
                                        <Input
                                            id="anio_bachillerato"
                                            type="number"
                                            min="1950"
                                            value={data.anio_bachillerato}
                                            onChange={(event) => setData('anio_bachillerato', event.target.value)}
                                        />
                                        <InputError message={errors.anio_bachillerato} />
                                    </div>
                                </div>

                                <label className="mt-4 flex max-w-md items-center gap-2 rounded-md border px-3 py-2 text-sm">
                                    <Checkbox checked={data.es_extranjero} onCheckedChange={(checked) => setData('es_extranjero', checked === true)} />
                                    Postulante extranjero
                                </label>
                                <InputError message={errors.es_extranjero} className="mt-1" />
                            </section>

                            <section className="rounded-lg border p-4">
                                <div className="mb-3">
                                    <h2 className="text-base font-semibold tracking-normal">Turno disponible</h2>
                                    <p className="text-sm text-muted-foreground">Solo se muestran turnos con cupo para esta gestion</p>
                                </div>

                                <div className="grid gap-2 md:max-w-md">
                                    <Label htmlFor="turno_gestion_cup_id">Turno CUP</Label>
                                    <Select value={data.turno_gestion_cup_id} onValueChange={(value) => setData('turno_gestion_cup_id', value)}>
                                        <SelectTrigger id="turno_gestion_cup_id">
                                            <SelectValue placeholder="Seleccione un turno" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {turnos.map((turno) => (
                                                <SelectItem key={turno.value} value={turno.value}>
                                                    {turno.label} / {turno.modalidad} / cupos {turno.cupo_disponible}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.turno_gestion_cup_id} />
                                </div>
                            </section>

                            <section className="rounded-lg border p-4">
                                <div className="mb-3">
                                    <h2 className="text-base font-semibold tracking-normal">Opciones de carrera</h2>
                                    <p className="text-sm text-muted-foreground">La segunda opcion es opcional y debe ser distinta a la primera</p>
                                </div>

                                <div className="grid gap-4 md:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="carrera_primera_id">Primera opcion</Label>
                                        <Select value={data.carrera_primera_id} onValueChange={(value) => setData('carrera_primera_id', value)}>
                                            <SelectTrigger id="carrera_primera_id">
                                                <SelectValue placeholder="Seleccione una carrera" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {carreras.map((carrera) => (
                                                    <SelectItem key={carrera.value} value={carrera.value}>
                                                        {carrera.label} / cupos {carrera.cupo_disponible}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.carrera_primera_id} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="carrera_segunda_id">Segunda opcion</Label>
                                        <Select value={data.carrera_segunda_id || 'sin_segunda'} onValueChange={(value) => setData('carrera_segunda_id', value === 'sin_segunda' ? '' : value)}>
                                            <SelectTrigger id="carrera_segunda_id">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="sin_segunda">Sin segunda opcion</SelectItem>
                                                {carrerasSegundaOpcion.map((carrera) => (
                                                    <SelectItem key={carrera.value} value={carrera.value}>
                                                        {carrera.label} / cupos {carrera.cupo_disponible}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.carrera_segunda_id} />
                                    </div>
                                </div>
                            </section>

                            <section className="rounded-lg border p-4">
                                <div className="mb-3">
                                    <h2 className="text-base font-semibold tracking-normal">Requisitos de inscripcion</h2>
                                    <p className="text-sm text-muted-foreground">Los requisitos declarativos deben confirmarse antes de continuar</p>
                                </div>

                                <InputError message={errors.requisitos_cumplidos} className="mb-2" />

                                <div className="grid gap-2 md:grid-cols-2">
                                    {requisitosDeclarativos.map((requisito) => {
                                        const id = String(requisito.id_requisito);

                                        return (
                                            <label key={requisito.id_requisito} className="flex items-center gap-2 rounded-md border px-3 py-2 text-sm">
                                                <Checkbox
                                                    checked={data.requisitos_cumplidos.includes(id)}
                                                    onCheckedChange={(checked) => alternarRequisito(id, checked === true)}
                                                />
                                                <span>
                                                    {requisito.nombre_requisito}
                                                    {requisito.obligatorio ? ' *' : ''}
                                                </span>
                                            </label>
                                        );
                                    })}

                                    {requisitoPago && (
                                        <div className="flex items-center gap-2 rounded-md border px-3 py-2 text-sm">
                                            <ReceiptText className="h-4 w-4" />
                                            <span>
                                                {requisitoPago.nombre_requisito} / {montoPago.label}
                                            </span>
                                        </div>
                                    )}
                                </div>
                            </section>

                            <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                <Button asChild variant="outline">
                                    <Link href={route('home')}>Cancelar</Link>
                                </Button>
                                <Button type="submit" disabled={processing || carreras.length === 0 || turnos.length === 0}>
                                    {processing ? <LoaderCircle className="animate-spin" /> : <ArrowRight />}
                                    {requisitoPago ? 'Continuar a pago' : 'Confirmar inscripcion'}
                                </Button>
                            </div>
                        </form>
                    )}
                </div>
            </main>
        </>
    );
}
