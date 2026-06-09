import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/react';
import { CheckCircle2, CircleAlert, Home, LogIn, RotateCcw } from 'lucide-react';

import { type InscripcionResultado } from './types';

type ResultadoInscripcionProps = {
    inscripcion: InscripcionResultado;
    status?: string;
};

export default function ResultadoInscripcion({ inscripcion, status }: ResultadoInscripcionProps) {
    const confirmada = inscripcion.estado === 'confirmada';

    return (
        <>
            <Head title="Resultado de inscripcion" />

            <main className="min-h-screen bg-background px-4 py-6 text-foreground sm:px-6 lg:px-8">
                <div className="mx-auto flex max-w-4xl flex-col gap-5">
                    <header className="flex flex-col gap-3 border-b pb-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p className="text-sm text-muted-foreground">Sistema CUP</p>
                            <h1 className="text-2xl font-semibold tracking-normal">Resultado de inscripcion</h1>
                        </div>
                        <Button asChild variant="outline">
                            <Link href={route('home')}>
                                <Home />
                                Inicio
                            </Link>
                        </Button>
                    </header>

                    <Alert variant={confirmada ? 'default' : 'destructive'}>
                        {confirmada ? <CheckCircle2 /> : <CircleAlert />}
                        <AlertTitle>{confirmada ? 'Inscripcion confirmada' : 'Inscripcion pendiente'}</AlertTitle>
                        <AlertDescription>{status ?? (confirmada ? 'El postulante quedo registrado en la gestion CUP.' : 'La inscripcion no fue confirmada.')}</AlertDescription>
                    </Alert>

                    <section className="rounded-lg border p-4">
                        <div className="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 className="text-base font-semibold tracking-normal">{inscripcion.postulante.nombre_completo}</h2>
                                <p className="text-sm text-muted-foreground">
                                    CI {inscripcion.postulante.ci} / {inscripcion.postulante.correo}
                                </p>
                            </div>
                            <Badge variant={confirmada ? 'default' : 'secondary'}>{inscripcion.estado}</Badge>
                        </div>

                        <dl className="grid gap-3 text-sm md:grid-cols-2">
                            <div className="rounded-md border px-3 py-2">
                                <dt className="text-muted-foreground">Codigo de inscripcion</dt>
                                <dd className="font-medium">{inscripcion.codigo_inscripcion}</dd>
                            </div>
                            <div className="rounded-md border px-3 py-2">
                                <dt className="text-muted-foreground">Gestion</dt>
                                <dd className="font-medium">
                                    {inscripcion.gestion?.nombre_gestion} / {inscripcion.gestion?.convocatoria}
                                </dd>
                            </div>
                            <div className="rounded-md border px-3 py-2">
                                <dt className="text-muted-foreground">Primera opcion</dt>
                                <dd className="font-medium">{inscripcion.carrera_primera}</dd>
                            </div>
                            <div className="rounded-md border px-3 py-2">
                                <dt className="text-muted-foreground">Segunda opcion</dt>
                                <dd className="font-medium">{inscripcion.carrera_segunda ?? 'No registrada'}</dd>
                            </div>
                            <div className="rounded-md border px-3 py-2">
                                <dt className="text-muted-foreground">Turno CUP</dt>
                                <dd className="font-medium">{inscripcion.turno ?? 'No registrado'}</dd>
                            </div>
                            <div className="rounded-md border px-3 py-2">
                                <dt className="text-muted-foreground">Usuario generado</dt>
                                <dd className="font-medium">{inscripcion.postulante.usuario_id ? `#${inscripcion.postulante.usuario_id}` : 'Pendiente'}</dd>
                            </div>
                            <div className="rounded-md border px-3 py-2">
                                <dt className="text-muted-foreground">Comprobante</dt>
                                <dd className="font-medium">{inscripcion.pago?.codigo_comprobante ?? 'No registrado'}</dd>
                            </div>
                        </dl>
                    </section>

                    <section className="rounded-lg border p-4">
                        <h2 className="mb-3 text-base font-semibold tracking-normal">Requisitos</h2>
                        <div className="grid gap-2 md:grid-cols-2">
                            {inscripcion.requisitos.map((requisito, index) => (
                                <div key={`${requisito.nombre_requisito}-${index}`} className="flex items-center justify-between gap-3 rounded-md border px-3 py-2 text-sm">
                                    <span>{requisito.nombre_requisito}</span>
                                    <Badge variant={requisito.cumplido ? 'default' : 'secondary'}>{requisito.cumplido ? 'Cumplido' : 'Pendiente'}</Badge>
                                </div>
                            ))}
                        </div>
                    </section>

                    <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        {!confirmada && (
                            <Button asChild variant="outline">
                                <Link href={route('cu06.inscripcion.create')}>
                                    <RotateCcw />
                                    Reintentar
                                </Link>
                            </Button>
                        )}
                        <Button asChild>
                            <Link href={route('login')}>
                                <LogIn />
                                Ingresar al sistema
                            </Link>
                        </Button>
                    </div>
                </div>
            </main>
        </>
    );
}
