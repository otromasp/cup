import { type FormDataConvertible } from '@inertiajs/core';

export type OpcionDocente = {
    value: string;
    label: string;
};

export type OpcionesDocente = {
    estadosContratacion: OpcionDocente[];
    diasSemana: OpcionDocente[];
    turnos: OpcionDocente[];
    modalidades: OpcionDocente[];
    materias: OpcionDocente[];
    usuariosDocente: OpcionDocente[];
};

export type DisponibilidadDocenteForm = {
    [key: string]: FormDataConvertible;
    id_disponibilidad_docente: number | null;
    dia_semana: string;
    turno: string;
    hora_inicio: string;
    hora_fin: string;
    modalidad: string;
    observacion: string;
};

export type DocenteForm = {
    [key: string]: FormDataConvertible;
    id_docente: number | null;
    usuario_id: string;
    ci: string;
    nombres: string;
    apellidos: string;
    correo: string;
    telefono: string;
    profesion: string;
    area_especialidad: string;
    titulo_profesional_afin: boolean;
    tiene_maestria: boolean;
    tiene_diplomado_educacion_superior: boolean;
    maximo_grupos_asignables: string;
    estado_contratacion: string;
    materias: string[];
    disponibilidades: DisponibilidadDocenteForm[];
};

export type DocenteResumen = {
    id_docente: number;
    nombre_completo: string;
    ci: string;
    correo: string;
    telefono: string | null;
    profesion: string;
    area_especialidad: string;
    estado_contratacion: string;
    estado_contratacion_label: string;
    maximo_grupos_asignables: number;
    materias_count: number;
    disponibilidades_count: number;
    materias: string[];
    modalidades: string[];
    usuario: {
        id_usuario: number;
        nombre: string;
        correo: string;
    } | null;
};

export type LinkPaginacion = {
    url: string | null;
    label: string;
    active: boolean;
};

export type DocentesPaginados = {
    data: DocenteResumen[];
    links: LinkPaginacion[];
    from: number | null;
    to: number | null;
    total: number;
};
