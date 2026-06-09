import { type FormDataConvertible } from '@inertiajs/core';

export type OpcionEtapaGestionCup = {
    value: string;
    label: string;
};

export type OpcionesEtapasGestionCup = {
    estadosEtapa: OpcionEtapaGestionCup[];
};

export type EtapaGestionCupForm = {
    [key: string]: FormDataConvertible;
    id_etapa_gestion: number | null;
    nombre_etapa: string;
    orden: string;
    fecha_inicio: string;
    fecha_fin: string;
    estado_etapa: string;
    estado_etapa_label: string | null;
};

export type EtapasGestionCupForm = {
    [key: string]: FormDataConvertible;
    etapas: EtapaGestionCupForm[];
};

export type GestionEtapasDetalle = {
    id_gestion: number;
    nombre_gestion: string;
    convocatoria: string;
    fecha_inicio: string | null;
    fecha_fin: string | null;
    estado_configuracion: string;
    estado_configuracion_label: string;
};

export type GestionEtapasResumen = GestionEtapasDetalle & {
    etapas_count: number;
    etapa_activa: string | null;
};

export type LinkPaginacion = {
    url: string | null;
    label: string;
    active: boolean;
};

export type GestionesEtapasPaginadas = {
    data: GestionEtapasResumen[];
    links: LinkPaginacion[];
    from: number | null;
    to: number | null;
    total: number;
};
