export type OpcionUsuario = {
    value: string;
    label: string;
};

export type OpcionesUsuario = {
    roles: OpcionUsuario[];
    estados: OpcionUsuario[];
};

export type UsuarioGestionado = {
    id_usuario: number;
    nombre: string;
    ci: string | null;
    correo: string;
    estado: string;
    estado_label: string;
    rol: string;
    rol_label: string;
    created_at: string | null;
    updated_at: string | null;
};

export type LinkPaginacion = {
    url: string | null;
    label: string;
    active: boolean;
};

export type UsuariosPaginados = {
    data: UsuarioGestionado[];
    links: LinkPaginacion[];
    from: number | null;
    to: number | null;
    total: number;
};
