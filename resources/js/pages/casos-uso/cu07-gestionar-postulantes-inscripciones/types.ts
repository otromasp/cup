export type OpcionInscripcion = {
    value: string;
    label: string;
};

export type PagoInscripcionResumen = {
    id_pago_inscripcion: number;
    proveedor: string;
    monto_centavos: number;
    moneda: string;
    monto_label: string;
    estado: string;
    estado_label: string;
    codigo_comprobante: string | null;
    pagado_en: string | null;
};

export type PostulanteInscripcionResumen = {
    id_postulante: number;
    nombre_completo: string;
    ci: string;
    correo: string;
    telefono: string | null;
    colegio_procedencia: string | null;
    anio_bachillerato: number | null;
    es_extranjero: boolean;
    estado: string;
    estado_label: string;
    usuario: {
        id_usuario: number;
        estado: string;
        estado_label: string;
        rol: string;
    } | null;
};

export type InscripcionResumen = {
    id_inscripcion: number;
    codigo_inscripcion: string;
    estado: string;
    estado_label: string;
    fecha_inscripcion: string | null;
    gestion: {
        nombre_gestion: string;
        convocatoria: string;
    } | null;
    postulante: PostulanteInscripcionResumen | null;
    carrera_primera: string | null;
    carrera_segunda: string | null;
    pago: PagoInscripcionResumen | null;
    observacion: string | null;
};

export type InscripcionDetalle = InscripcionResumen & {
    requisitos: {
        id_inscripcion_requisito: number;
        nombre_requisito: string | null;
        cumplido: boolean;
        origen: string;
        cumplido_en: string | null;
    }[];
};

export type InscripcionesPaginadas = {
    data: InscripcionResumen[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
    from: number | null;
    to: number | null;
    total: number;
};
