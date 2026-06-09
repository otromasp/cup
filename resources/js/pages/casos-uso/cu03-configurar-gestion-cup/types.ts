import { type FormDataConvertible } from '@inertiajs/core';

export type OpcionGestionCup = {
    value: string;
    label: string;
};

export type OpcionesGestionCup = {
    estados: OpcionGestionCup[];
    estadosConfiguracion: OpcionGestionCup[];
    monedasInscripcion: OpcionGestionCup[];
    turnos: OpcionGestionCup[];
    modalidades: OpcionGestionCup[];
    tiposRequisito: OpcionGestionCup[];
    ambitosAplicacion: OpcionGestionCup[];
};

export type CarreraCupForm = {
    [key: string]: FormDataConvertible;
    id_carrera_cup: number | null;
    nombre_carrera: string;
    cupo_disponible: string;
    estado: string;
};

export type MateriaCupForm = {
    [key: string]: FormDataConvertible;
    id_materia_cup: number | null;
    nombre_materia: string;
    ponderacion_nota1: string;
    ponderacion_nota2: string;
    ponderacion_nota3: string;
    nota_minima: string;
    estado: string;
};

export type TurnoGestionCupForm = {
    [key: string]: FormDataConvertible;
    id_turno_gestion: number | null;
    turno: string;
    orden: string;
    capacidad_maxima: string;
    modalidad: string;
    estado: string;
};

export type RequisitoInscripcionForm = {
    [key: string]: FormDataConvertible;
    id_requisito: number | null;
    nombre_requisito: string;
    obligatorio: boolean;
    tipo_requisito: string;
    aplica_a: string;
    estado: string;
};

export type GestionCupForm = {
    [key: string]: FormDataConvertible;
    id_gestion: number | null;
    nombre_gestion: string;
    convocatoria: string;
    fecha_inicio: string;
    fecha_fin: string;
    nota_minima_aprobacion: string;
    costo_inscripcion: string;
    moneda_inscripcion: string;
    estado_configuracion: string;
    responsable: string | null;
    turnos: TurnoGestionCupForm[];
    carreras: CarreraCupForm[];
    materias: MateriaCupForm[];
    requisitos: RequisitoInscripcionForm[];
};

export type GestionCupResumen = {
    id_gestion: number;
    nombre_gestion: string;
    convocatoria: string;
    fecha_inicio: string | null;
    fecha_fin: string | null;
    nota_minima_aprobacion: string;
    costo_inscripcion: string;
    estado_configuracion: string;
    estado_configuracion_label: string;
    responsable: string | null;
    turnos_count: number;
    carreras_count: number;
    materias_count: number;
    requisitos_count: number;
};

export type LinkPaginacion = {
    url: string | null;
    label: string;
    active: boolean;
};

export type GestionesPaginadas = {
    data: GestionCupResumen[];
    links: LinkPaginacion[];
    from: number | null;
    to: number | null;
    total: number;
};
