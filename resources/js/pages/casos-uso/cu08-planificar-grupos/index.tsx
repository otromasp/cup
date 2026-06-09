import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { CalendarCheck, CalendarDays, Clock3, MapPin, Monitor, Pencil, Play, Send, UsersRound, WandSparkles } from 'lucide-react';
import { type FormEventHandler, type ReactNode, useMemo } from 'react';

import {
    type AsignacionGrupoResumen,
    type AsignacionGrupoForm,
    type DocentePlanificacion,
    type GenerarGruposForm,
    type GestionPlanificacion,
    type GrupoPlanificacion,
    type OpcionPlanificacion,
    type ResumenPlanificacion,
} from './types';

type IndexProps = {
    gestion: GestionPlanificacion | null;
    resumen: ResumenPlanificacion | null;
    grupos: GrupoPlanificacion[];
    filtros: {
        gestion_id?: string;
    };
    opciones: {
        gestiones: OpcionPlanificacion[];
        materias: OpcionPlanificacion[];
        docentes: DocentePlanificacion[];
        diasSemana: OpcionPlanificacion[];
        turnos: OpcionPlanificacion[];
        modalidades: OpcionPlanificacion[];
    };
    status?: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Planificar grupos',
        href: '/planificacion-grupos',
    },
];

const etiqueta = (valor?: string | null) => {
    if (!valor) {
        return '-';
    }

    return valor.charAt(0).toUpperCase() + valor.slice(1).replace('_', ' ');
};

const estadoVariant = (estado: string): 'default' | 'secondary' | 'outline' => {
    if (estado === 'publicado') {
        return 'default';
    }

    return 'outline';
};

export default function Index({ gestion, resumen, grupos, filtros, opciones, status }: IndexProps) {
    const gestionId = filtros.gestion_id ?? '';
    const generarForm = useForm<GenerarGruposForm>({
        gestion_cup_id: gestionId,
    });
    const asignacionForm = useForm<AsignacionGrupoForm>({
        grupo_cup_id: grupos[0]?.id_grupo_cup ? String(grupos[0].id_grupo_cup) : '',
        materia_cup_id: opciones.materias[0]?.value ?? '',
        docente_id: opciones.docentes[0]?.value ?? '',
        dia_semana: opciones.diasSemana[0]?.value ?? 'lunes',
        turno: opciones.turnos[0]?.value ?? 'manana',
        hora_inicio: '08:00',
        hora_fin: '10:00',
        modalidad: opciones.modalidades[0]?.value ?? 'presencial',
        aula: '',
        enlace_clase: '',
        observacion: '',
    });

    const docentesFiltrados = useMemo(() => {
        if (!asignacionForm.data.materia_cup_id) {
            return opciones.docentes;
        }

        return opciones.docentes.filter((docente) => docente.materias.includes(asignacionForm.data.materia_cup_id));
    }, [asignacionForm.data.materia_cup_id, opciones.docentes]);

    const docenteSeleccionado = docentesFiltrados.find((docente) => docente.value === asignacionForm.data.docente_id);

    const cambiarGestion = (value: string) => {
        router.get(route('cu08.planificacion-grupos.index'), { gestion_id: value }, { preserveState: false, replace: true });
    };

    const generarGrupos: FormEventHandler = (event) => {
        event.preventDefault();

        generarForm.post(route('cu08.planificacion-grupos.generate'), {
            preserveScroll: true,
        });
    };

    const generarHorarios: FormEventHandler = (event) => {
        event.preventDefault();

        generarForm.post(route('cu08.planificacion-grupos.schedule.generate'), {
            preserveScroll: true,
        });
    };

    const guardarAsignacion: FormEventHandler = (event) => {
        event.preventDefault();

        asignacionForm.post(route('cu08.planificacion-grupos.assignments.store'), {
            preserveScroll: true,
        });
    };

    const editarAsignacion = (grupo: GrupoPlanificacion, asignacion: AsignacionGrupoResumen) => {
        asignacionForm.setData({
            grupo_cup_id: String(grupo.id_grupo_cup),
            materia_cup_id: String(asignacion.materia_cup_id),
            docente_id: String(asignacion.docente_id),
            dia_semana: asignacion.dia_semana ?? opciones.diasSemana[0]?.value ?? 'lunes',
            turno: asignacion.turno ?? opciones.turnos[0]?.value ?? 'manana',
            hora_inicio: asignacion.hora_inicio ?? '08:00',
            hora_fin: asignacion.hora_fin ?? '10:00',
            modalidad: asignacion.modalidad ?? opciones.modalidades[0]?.value ?? 'presencial',
            aula: asignacion.aula ?? '',
            enlace_clase: asignacion.enlace_clase ?? '',
            observacion: asignacion.observacion ?? '',
        });

        document.getElementById('formulario-asignacion')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    const publicar = () => {
        generarForm.post(route('cu08.planificacion-grupos.publish'), {
            preserveScroll: true,
        });
    };

    const puedePlanificar = Boolean(gestion);
    const tieneGrupos = grupos.length > 0;
    const publicado = Boolean(resumen?.planificacion_publicada);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Planificar grupos" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h1 className="text-xl font-semibold tracking-normal">Planificar grupos</h1>
                        <p className="text-sm text-muted-foreground">Distribucion de inscritos, horarios y docentes del CUP</p>
                    </div>

                    <div className="w-full lg:w-96">
                        <Select value={gestionId || 'sin-gestion'} onValueChange={cambiarGestion}>
                            <SelectTrigger>
                                <SelectValue placeholder="Seleccionar gestion CUP" />
                            </SelectTrigger>
                            <SelectContent>
                                {opciones.gestiones.length === 0 && <SelectItem value="sin-gestion">Sin gestiones registradas</SelectItem>}
                                {opciones.gestiones.map((opcion) => (
                                    <SelectItem key={opcion.value} value={opcion.value}>
                                        {opcion.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                {status && <div className="rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700">{status}</div>}

                {gestion ? (
                    <div className="rounded-lg border p-4">
                        <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                            <div>
                                <div className="text-sm text-muted-foreground">Gestion seleccionada</div>
                                <div className="text-lg font-semibold">
                                    {gestion.nombre_gestion} / {gestion.convocatoria}
                                </div>
                                <div className="text-sm text-muted-foreground">
                                    {gestion.fecha_inicio ?? '-'} a {gestion.fecha_fin ?? '-'}
                                </div>
                            </div>

                            <div className="flex flex-wrap gap-2">
                                <form onSubmit={generarGrupos}>
                                    <input type="hidden" value={generarForm.data.gestion_cup_id} name="gestion_cup_id" />
                                    <Button type="submit" variant="outline" disabled={!puedePlanificar || generarForm.processing || publicado}>
                                        <Play />
                                        Generar grupos
                                    </Button>
                                </form>
                                <form onSubmit={generarHorarios}>
                                    <input type="hidden" value={generarForm.data.gestion_cup_id} name="gestion_cup_id" />
                                    <Button type="submit" variant="outline" disabled={!tieneGrupos || publicado || generarForm.processing}>
                                        <WandSparkles />
                                        Generar horarios
                                    </Button>
                                </form>
                                <Button type="button" onClick={publicar} disabled={!tieneGrupos || publicado || generarForm.processing}>
                                    <Send />
                                    Publicar
                                </Button>
                            </div>
                        </div>

                        <InputError message={generarForm.errors.gestion_cup_id} className="mt-3" />
                    </div>
                ) : (
                    <div className="rounded-lg border p-6 text-sm text-muted-foreground">No existe una gestion CUP configurada para planificar.</div>
                )}

                {resumen && (
                    <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <ResumenItem icon={<UsersRound />} label="Inscripciones confirmadas" value={resumen.inscripciones_confirmadas} />
                        <ResumenItem icon={<CalendarCheck />} label="Grupos necesarios" value={resumen.grupos_necesarios} />
                        <ResumenItem label="Grupos actuales" value={resumen.grupos_actuales} detail={`Capacidad ${resumen.capacidad_maxima}`} />
                        <ResumenItem
                            label="Asignaciones"
                            value={`${resumen.asignaciones_actuales}/${resumen.asignaciones_esperadas}`}
                            detail={`${resumen.materias_activas} materias activas`}
                        />
                    </div>
                )}

                {tieneGrupos && (
                    <HorarioVisual
                        grupos={grupos}
                        diasSemana={opciones.diasSemana}
                        publicado={publicado}
                        onEdit={editarAsignacion}
                    />
                )}

                {tieneGrupos && !publicado && (
                    <form id="formulario-asignacion" onSubmit={guardarAsignacion} className="rounded-lg border p-4">
                        <div className="mb-4">
                            <h2 className="text-base font-semibold tracking-normal">Ajustar materia, docente y horario</h2>
                            <p className="text-sm text-muted-foreground">Puedes corregir la propuesta automatica antes de publicar la planificacion.</p>
                        </div>

                        <div className="grid gap-3 lg:grid-cols-4">
                            <CampoSelect
                                label="Grupo"
                                value={asignacionForm.data.grupo_cup_id}
                                options={grupos.map((grupo) => ({ value: String(grupo.id_grupo_cup), label: grupo.nombre_grupo }))}
                                onChange={(value) => asignacionForm.setData('grupo_cup_id', value)}
                                error={asignacionForm.errors.grupo_cup_id}
                            />
                            <CampoSelect
                                label="Materia"
                                value={asignacionForm.data.materia_cup_id}
                                options={opciones.materias}
                                onChange={(value) => {
                                    asignacionForm.setData('materia_cup_id', value);
                                    const primerDocente = opciones.docentes.find((docente) => docente.materias.includes(value));
                                    asignacionForm.setData('docente_id', primerDocente?.value ?? '');
                                }}
                                error={asignacionForm.errors.materia_cup_id}
                            />
                            <CampoSelect
                                label="Docente"
                                value={asignacionForm.data.docente_id || 'sin-docente'}
                                options={docentesFiltrados.length > 0 ? docentesFiltrados : [{ value: 'sin-docente', label: 'Sin docente disponible' }]}
                                onChange={(value) => asignacionForm.setData('docente_id', value === 'sin-docente' ? '' : value)}
                                error={asignacionForm.errors.docente_id}
                            />
                            <CampoSelect
                                label="Dia"
                                value={asignacionForm.data.dia_semana}
                                options={opciones.diasSemana}
                                onChange={(value) => asignacionForm.setData('dia_semana', value)}
                                error={asignacionForm.errors.dia_semana}
                            />
                            <CampoSelect
                                label="Turno"
                                value={asignacionForm.data.turno}
                                options={opciones.turnos}
                                onChange={(value) => asignacionForm.setData('turno', value)}
                                error={asignacionForm.errors.turno}
                            />
                            <CampoInput
                                label="Inicio"
                                type="time"
                                value={asignacionForm.data.hora_inicio}
                                onChange={(value) => asignacionForm.setData('hora_inicio', value)}
                                error={asignacionForm.errors.hora_inicio}
                            />
                            <CampoInput
                                label="Fin"
                                type="time"
                                value={asignacionForm.data.hora_fin}
                                onChange={(value) => asignacionForm.setData('hora_fin', value)}
                                error={asignacionForm.errors.hora_fin}
                            />
                            <CampoSelect
                                label="Modalidad"
                                value={asignacionForm.data.modalidad}
                                options={opciones.modalidades}
                                onChange={(value) => asignacionForm.setData('modalidad', value)}
                                error={asignacionForm.errors.modalidad}
                            />
                            <CampoInput label="Aula" value={asignacionForm.data.aula} onChange={(value) => asignacionForm.setData('aula', value)} error={asignacionForm.errors.aula} />
                            <CampoInput
                                label="Enlace virtual"
                                value={asignacionForm.data.enlace_clase}
                                onChange={(value) => asignacionForm.setData('enlace_clase', value)}
                                error={asignacionForm.errors.enlace_clase}
                            />
                            <CampoInput
                                label="Observacion"
                                value={asignacionForm.data.observacion}
                                onChange={(value) => asignacionForm.setData('observacion', value)}
                                error={asignacionForm.errors.observacion}
                            />
                            <div className="flex items-end">
                                <Button type="submit" disabled={asignacionForm.processing || !asignacionForm.data.docente_id}>
                                    <CalendarCheck />
                                    Guardar asignacion
                                </Button>
                            </div>
                        </div>

                        {docenteSeleccionado && (
                            <div className="mt-3 flex flex-wrap gap-2 text-xs text-muted-foreground">
                                {docenteSeleccionado.disponibilidades.map((disponibilidad, index) => (
                                    <Badge key={`${disponibilidad.dia_semana}-${disponibilidad.turno}-${index}`} variant="outline">
                                        {etiqueta(disponibilidad.dia_semana)} {disponibilidad.hora_inicio}-{disponibilidad.hora_fin} / {etiqueta(disponibilidad.modalidad)}
                                    </Badge>
                                ))}
                            </div>
                        )}
                    </form>
                )}

                <div className="overflow-hidden rounded-lg border">
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[1120px] text-sm">
                            <thead className="bg-muted/50 text-left">
                                <tr>
                                    <th className="px-4 py-3 font-medium">Grupo</th>
                                    <th className="px-4 py-3 font-medium">Postulantes</th>
                                    <th className="px-4 py-3 font-medium">Estado</th>
                                    <th className="px-4 py-3 font-medium">Asignaciones academicas</th>
                                </tr>
                            </thead>
                            <tbody>
                                {grupos.map((grupo) => (
                                    <tr key={grupo.id_grupo_cup} className="border-t align-top">
                                        <td className="px-4 py-3">
                                            <div className="font-medium">{grupo.nombre_grupo}</div>
                                            <div className="text-muted-foreground">Turno {etiqueta(grupo.turno)}</div>
                                        </td>
                                        <td className="px-4 py-3">
                                            {grupo.inscripciones_count}/{grupo.capacidad_maxima}
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge variant={estadoVariant(grupo.estado)}>{grupo.estado_label}</Badge>
                                            {grupo.publicado_en && <div className="mt-1 text-muted-foreground">{grupo.publicado_en}</div>}
                                        </td>
                                        <td className="px-4 py-3">
                                            {grupo.asignaciones.length > 0 ? (
                                                <div className="grid gap-2">
                                                    {grupo.asignaciones.map((asignacion) => (
                                                        <div key={asignacion.id_asignacion_grupo} className="rounded-md border px-3 py-2">
                                                            <div className="flex items-start justify-between gap-2">
                                                                <div className="font-medium">
                                                                    {asignacion.materia} / {asignacion.docente}
                                                                </div>
                                                                {!publicado && (
                                                                    <Button type="button" size="icon" variant="ghost" onClick={() => editarAsignacion(grupo, asignacion)}>
                                                                        <Pencil />
                                                                    </Button>
                                                                )}
                                                            </div>
                                                            <div className="text-muted-foreground">
                                                                {etiqueta(asignacion.dia_semana)} {asignacion.hora_inicio}-{asignacion.hora_fin} / {etiqueta(asignacion.modalidad)}
                                                            </div>
                                                            {(asignacion.aula || asignacion.enlace_clase) && (
                                                                <div className="text-muted-foreground">{asignacion.aula || asignacion.enlace_clase}</div>
                                                            )}
                                                        </div>
                                                    ))}
                                                </div>
                                            ) : (
                                                <span className="text-muted-foreground">Sin asignaciones</span>
                                            )}
                                        </td>
                                    </tr>
                                ))}

                                {grupos.length === 0 && (
                                    <tr>
                                        <td colSpan={4} className="px-4 py-8 text-center text-muted-foreground">
                                            Aun no se generaron grupos para la gestion seleccionada.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

function HorarioVisual({
    grupos,
    diasSemana,
    publicado,
    onEdit,
}: {
    grupos: GrupoPlanificacion[];
    diasSemana: OpcionPlanificacion[];
    publicado: boolean;
    onEdit: (grupo: GrupoPlanificacion, asignacion: AsignacionGrupoResumen) => void;
}) {
    const items = useMemo(
        () =>
            grupos.flatMap((grupo) =>
                grupo.asignaciones.map((asignacion) => ({
                    ...asignacion,
                    grupoNombre: grupo.nombre_grupo,
                    grupo,
                })),
            ),
        [grupos],
    );

    const pendientes = useMemo(
        () =>
            grupos.flatMap((grupo) =>
                grupo.asignaciones.length === 0
                    ? [
                          {
                              grupo,
                              texto: `${grupo.nombre_grupo} sin horarios`,
                          },
                      ]
                    : [],
            ),
        [grupos],
    );

    return (
        <div className="rounded-lg border p-4">
            <div className="mb-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <div className="flex items-center gap-2 text-base font-semibold tracking-normal">
                        <CalendarDays className="h-4 w-4" />
                        Horario visual
                    </div>
                    <p className="text-sm text-muted-foreground">Propuesta semanal por grupos, materias, docentes y modalidad.</p>
                </div>
                <Badge variant="outline">{items.length} bloques planificados</Badge>
            </div>

            <div className="overflow-x-auto pb-1">
                <div className="grid min-w-[1080px] grid-cols-7 gap-3">
                    {diasSemana.map((dia) => {
                        const bloquesDia = items
                            .filter((item) => item.dia_semana === dia.value)
                            .sort((a, b) => minutosHorario(a.hora_inicio) - minutosHorario(b.hora_inicio));

                        return (
                            <div key={dia.value} className="min-h-[220px] rounded-md border bg-muted/20 p-2">
                                <div className="sticky top-0 z-10 mb-2 rounded bg-background/95 px-2 py-1 text-sm font-medium">{dia.label}</div>

                                <div className="grid gap-2">
                                    {bloquesDia.map((item) => (
                                        <div
                                            key={item.id_asignacion_grupo}
                                            className={`rounded-md border px-2 py-2 shadow-sm ${modalidadClase(item.modalidad)}`}
                                        >
                                            <div className="flex items-start justify-between gap-2">
                                                <div>
                                                    <div className="text-xs font-semibold">{item.grupoNombre}</div>
                                                    <div className="text-sm font-medium leading-tight">{item.materia}</div>
                                                </div>
                                                {!publicado && (
                                                    <Button type="button" size="icon" variant="ghost" onClick={() => onEdit(item.grupo, item)}>
                                                        <Pencil />
                                                    </Button>
                                                )}
                                            </div>

                                            <div className="mt-2 grid gap-1 text-xs text-muted-foreground">
                                                <span className="flex items-center gap-1">
                                                    <Clock3 className="h-3 w-3" />
                                                    {item.hora_inicio}-{item.hora_fin}
                                                </span>
                                                <span className="line-clamp-2">{item.docente}</span>
                                                <span className="flex items-center gap-1">
                                                    {item.modalidad === 'virtual' ? <Monitor className="h-3 w-3" /> : <MapPin className="h-3 w-3" />}
                                                    {item.aula || item.enlace_clase || etiqueta(item.modalidad)}
                                                </span>
                                            </div>
                                        </div>
                                    ))}

                                    {bloquesDia.length === 0 && <div className="rounded-md border border-dashed px-2 py-6 text-center text-xs text-muted-foreground">Sin bloques</div>}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </div>

            {pendientes.length > 0 && (
                <div className="mt-3 flex flex-wrap gap-2">
                    {pendientes.map((pendiente) => (
                        <Badge key={pendiente.grupo.id_grupo_cup} variant="outline">
                            {pendiente.texto}
                        </Badge>
                    ))}
                </div>
            )}
        </div>
    );
}

function ResumenItem({ icon, label, value, detail }: { icon?: ReactNode; label: string; value: number | string; detail?: string }) {
    return (
        <div className="rounded-lg border p-4">
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                {icon}
                <span>{label}</span>
            </div>
            <div className="mt-2 text-2xl font-semibold tracking-normal">{value}</div>
            {detail && <div className="text-sm text-muted-foreground">{detail}</div>}
        </div>
    );
}

function minutosHorario(hora?: string | null): number {
    if (!hora) {
        return 0;
    }

    const [horas, minutos] = hora.split(':').map(Number);

    return horas * 60 + minutos;
}

function modalidadClase(modalidad?: string | null): string {
    if (modalidad === 'virtual') {
        return 'bg-sky-50 border-sky-200';
    }

    if (modalidad === 'mixta') {
        return 'bg-amber-50 border-amber-200';
    }

    return 'bg-emerald-50 border-emerald-200';
}

function CampoSelect({
    label,
    value,
    options,
    onChange,
    error,
}: {
    label: string;
    value: string;
    options: OpcionPlanificacion[];
    onChange: (value: string) => void;
    error?: string;
}) {
    return (
        <div>
            <Label>{label}</Label>
            <Select value={value} onValueChange={onChange}>
                <SelectTrigger className="mt-1">
                    <SelectValue placeholder={label} />
                </SelectTrigger>
                <SelectContent>
                    {options.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            <InputError message={error} className="mt-2" />
        </div>
    );
}

function CampoInput({
    label,
    value,
    onChange,
    error,
    type = 'text',
}: {
    label: string;
    value: string;
    onChange: (value: string) => void;
    error?: string;
    type?: string;
}) {
    return (
        <div>
            <Label>{label}</Label>
            <Input className="mt-1" type={type} value={value} onChange={(event) => onChange(event.target.value)} />
            <InputError message={error} className="mt-2" />
        </div>
    );
}
