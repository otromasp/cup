import { type FormDataConvertible } from '@inertiajs/core';

export type OpcionPlanificacion = {
    value: string;
    label: string;
};

export type DisponibilidadDocentePlanificacion = {
    dia_semana: string;
    turno: string;
    hora_inicio: string;
    hora_fin: string;
    modalidad: string;
};

export type DocentePlanificacion = OpcionPlanificacion & {
    materias: string[];
    disponibilidades: DisponibilidadDocentePlanificacion[];
};

export type GestionPlanificacion = {
    id_gestion: number;
    nombre_gestion: string;
    convocatoria: string;
    fecha_inicio: string | null;
    fecha_fin: string | null;
};

export type ResumenPlanificacion = {
    inscripciones_confirmadas: number;
    capacidad_maxima: number;
    grupos_necesarios: number;
    grupos_actuales: number;
    materias_activas: number;
    asignaciones_actuales: number;
    asignaciones_esperadas: number;
    planificacion_publicada: boolean;
};

export type AsignacionGrupoResumen = {
    id_asignacion_grupo: number;
    materia_cup_id: number;
    docente_id: number;
    materia: string | null;
    docente: string | null;
    dia_semana: string | null;
    turno: string | null;
    hora_inicio: string | null;
    hora_fin: string | null;
    modalidad: string | null;
    aula: string | null;
    enlace_clase: string | null;
    observacion: string | null;
};

export type GrupoPlanificacion = {
    id_grupo_cup: number;
    nombre_grupo: string;
    numero_grupo: number;
    capacidad_maxima: number;
    turno: string;
    estado: string;
    estado_label: string;
    publicado_en: string | null;
    inscripciones_count: number;
    asignaciones: AsignacionGrupoResumen[];
};

export type GenerarGruposForm = {
    [key: string]: FormDataConvertible;
    gestion_cup_id: string;
};

export type AsignacionGrupoForm = {
    [key: string]: FormDataConvertible;
    grupo_cup_id: string;
    materia_cup_id: string;
    docente_id: string;
    dia_semana: string;
    turno: string;
    hora_inicio: string;
    hora_fin: string;
    modalidad: string;
    aula: string;
    enlace_clase: string;
    observacion: string;
};
