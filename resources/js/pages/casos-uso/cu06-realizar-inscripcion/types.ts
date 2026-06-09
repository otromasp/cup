import { type FormDataConvertible } from '@inertiajs/core';

export type GestionInscripcion = {
    id_gestion: number;
    nombre_gestion: string;
    convocatoria: string;
    fecha_inicio: string | null;
    fecha_fin: string | null;
};

export type CarreraInscripcion = {
    value: string;
    label: string;
    cupo_disponible: number;
};

export type TurnoInscripcion = {
    value: string;
    label: string;
    modalidad: string;
    cupo_disponible: number;
};

export type RequisitoInscripcion = {
    id_requisito: number;
    nombre_requisito: string;
    obligatorio: boolean;
    tipo_requisito: string;
    aplica_a: string;
};

export type MontoPago = {
    monto_centavos: number;
    moneda: string;
    label: string;
};

export type InscripcionForm = {
    [key: string]: FormDataConvertible;
    gestion_cup_id: string;
    ci: string;
    nombres: string;
    apellidos: string;
    correo: string;
    telefono: string;
    colegio_procedencia: string;
    anio_bachillerato: string;
    es_extranjero: boolean;
    turno_gestion_cup_id: string;
    carrera_primera_id: string;
    carrera_segunda_id: string;
    requisitos_cumplidos: string[];
};

export type DatosPreviosInscripcion = InscripcionForm;

export type InscripcionResultado = {
    id_inscripcion: number;
    codigo_inscripcion: string;
    estado: string;
    fecha_inscripcion: string | null;
    gestion: {
        nombre_gestion: string;
        convocatoria: string;
    } | null;
    postulante: {
        nombre_completo: string | null;
        ci: string | null;
        correo: string | null;
        usuario_id: number | null;
    };
    carrera_primera: string | null;
    carrera_segunda: string | null;
    turno: string | null;
    pago: {
        estado: string;
        codigo_comprobante: string | null;
        pagado_en: string | null;
    } | null;
    requisitos: {
        nombre_requisito: string | null;
        cumplido: boolean;
        origen: string;
    }[];
};
