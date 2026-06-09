import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Link } from '@inertiajs/react';
import { LoaderCircle, Plus, Save, Trash2 } from 'lucide-react';
import { FormEventHandler } from 'react';

import { type DisponibilidadDocenteForm, type DocenteForm, type OpcionesDocente } from './types';

type FormularioDocenteProps = {
    data: DocenteForm;
    opciones: OpcionesDocente;
    errors: Record<string, string | undefined>;
    processing: boolean;
    submitLabel: string;
    cancelHref: string;
    onSubmit: FormEventHandler;
    setData: <K extends keyof DocenteForm>(key: K, value: DocenteForm[K]) => void;
};

const nuevaDisponibilidad = (): DisponibilidadDocenteForm => ({
    id_disponibilidad_docente: null,
    dia_semana: 'lunes',
    turno: 'manana',
    hora_inicio: '08:00',
    hora_fin: '10:00',
    modalidad: 'presencial',
    observacion: '',
});

export function FormularioDocente({ data, opciones, errors, processing, submitLabel, cancelHref, onSubmit, setData }: FormularioDocenteProps) {
    const error = (key: string) => errors[key];

    const actualizarDisponibilidad = <K extends keyof DisponibilidadDocenteForm>(index: number, key: K, value: DisponibilidadDocenteForm[K]) => {
        setData(
            'disponibilidades',
            data.disponibilidades.map((disponibilidad, posicion) => (posicion === index ? { ...disponibilidad, [key]: value } : disponibilidad)),
        );
    };

    const alternarMateria = (materia: string, checked: boolean) => {
        setData('materias', checked ? [...data.materias, materia] : data.materias.filter((materiaSeleccionada) => materiaSeleccionada !== materia));
    };

    return (
        <form onSubmit={onSubmit} className="flex flex-col gap-4">
            <section className="rounded-lg border p-4">
                <div className="mb-3">
                    <h2 className="text-base font-semibold tracking-normal">Datos del docente</h2>
                    <p className="text-sm text-muted-foreground">Perfil academico y contacto</p>
                </div>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div className="grid gap-2">
                        <Label htmlFor="ci">CI</Label>
                        <Input id="ci" value={data.ci} onChange={(event) => setData('ci', event.target.value)} />
                        <InputError message={error('ci')} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="nombres">Nombres</Label>
                        <Input id="nombres" value={data.nombres} onChange={(event) => setData('nombres', event.target.value)} />
                        <InputError message={error('nombres')} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="apellidos">Apellidos</Label>
                        <Input id="apellidos" value={data.apellidos} onChange={(event) => setData('apellidos', event.target.value)} />
                        <InputError message={error('apellidos')} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="correo">Correo</Label>
                        <Input id="correo" type="email" value={data.correo} onChange={(event) => setData('correo', event.target.value)} />
                        <InputError message={error('correo')} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="telefono">Telefono</Label>
                        <Input id="telefono" value={data.telefono} onChange={(event) => setData('telefono', event.target.value)} />
                        <InputError message={error('telefono')} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="profesion">Profesion</Label>
                        <Input id="profesion" value={data.profesion} onChange={(event) => setData('profesion', event.target.value)} />
                        <InputError message={error('profesion')} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="area_especialidad">Area de especialidad</Label>
                        <Input
                            id="area_especialidad"
                            value={data.area_especialidad}
                            onChange={(event) => setData('area_especialidad', event.target.value)}
                        />
                        <InputError message={error('area_especialidad')} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="usuario_id">Cuenta asociada</Label>
                        <Select value={data.usuario_id || 'sin_usuario'} onValueChange={(value) => setData('usuario_id', value === 'sin_usuario' ? '' : value)}>
                            <SelectTrigger id="usuario_id">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="sin_usuario">Sin cuenta asociada</SelectItem>
                                {opciones.usuariosDocente.map((usuario) => (
                                    <SelectItem key={usuario.value} value={usuario.value}>
                                        {usuario.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={error('usuario_id')} />
                    </div>
                </div>
            </section>

            <section className="rounded-lg border p-4">
                <div className="mb-3">
                    <h2 className="text-base font-semibold tracking-normal">Habilitacion academica</h2>
                    <p className="text-sm text-muted-foreground">Condiciones minimas para dejar el docente habilitado</p>
                </div>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <label className="flex items-center gap-2 rounded-md border px-3 py-2 text-sm">
                        <Checkbox
                            checked={data.titulo_profesional_afin}
                            onCheckedChange={(checked) => setData('titulo_profesional_afin', checked === true)}
                        />
                        Titulo afin
                    </label>

                    <label className="flex items-center gap-2 rounded-md border px-3 py-2 text-sm">
                        <Checkbox checked={data.tiene_maestria} onCheckedChange={(checked) => setData('tiene_maestria', checked === true)} />
                        Maestria
                    </label>

                    <label className="flex items-center gap-2 rounded-md border px-3 py-2 text-sm">
                        <Checkbox
                            checked={data.tiene_diplomado_educacion_superior}
                            onCheckedChange={(checked) => setData('tiene_diplomado_educacion_superior', checked === true)}
                        />
                        Diplomado educacion superior
                    </label>

                    <div className="grid gap-2">
                        <Label htmlFor="maximo_grupos_asignables">Maximo grupos</Label>
                        <Input
                            id="maximo_grupos_asignables"
                            type="number"
                            min="1"
                            max="4"
                            value={data.maximo_grupos_asignables}
                            onChange={(event) => setData('maximo_grupos_asignables', event.target.value)}
                        />
                        <InputError message={error('maximo_grupos_asignables')} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="estado_contratacion">Estado</Label>
                        <Select value={data.estado_contratacion} onValueChange={(value) => setData('estado_contratacion', value)}>
                            <SelectTrigger id="estado_contratacion">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {opciones.estadosContratacion.map((estado) => (
                                    <SelectItem key={estado.value} value={estado.value}>
                                        {estado.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={error('estado_contratacion')} />
                    </div>
                </div>

                <div className="mt-2 grid gap-1">
                    <InputError message={error('titulo_profesional_afin')} />
                    <InputError message={error('tiene_maestria')} />
                    <InputError message={error('tiene_diplomado_educacion_superior')} />
                </div>
            </section>

            <section className="rounded-lg border p-4">
                <div className="mb-3">
                    <h2 className="text-base font-semibold tracking-normal">Materias habilitadas</h2>
                    <p className="text-sm text-muted-foreground">Materias configuradas previamente para la gestion CUP</p>
                </div>

                <InputError message={error('materias')} className="mb-2" />

                <div className="grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                    {opciones.materias.map((materia) => (
                        <label key={materia.value} className="flex items-center gap-2 rounded-md border px-3 py-2 text-sm">
                            <Checkbox
                                checked={data.materias.includes(materia.value)}
                                onCheckedChange={(checked) => alternarMateria(materia.value, checked === true)}
                            />
                            {materia.label}
                        </label>
                    ))}

                    {opciones.materias.length === 0 && (
                        <div className="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                            Primero configure materias activas en Gestion CUP.
                        </div>
                    )}
                </div>
            </section>

            <section className="rounded-lg border p-4">
                <div className="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 className="text-base font-semibold tracking-normal">Referencia horaria</h2>
                        <p className="text-sm text-muted-foreground">Informacion opcional para consulta administrativa</p>
                    </div>
                    <Button type="button" size="sm" variant="outline" onClick={() => setData('disponibilidades', [...data.disponibilidades, nuevaDisponibilidad()])}>
                        <Plus />
                        Bloque
                    </Button>
                </div>

                <InputError message={error('disponibilidades')} className="mb-2" />

                <div className="overflow-x-auto rounded-md border">
                    <table className="w-full min-w-[1080px] text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="w-40 px-3 py-2 font-medium">Dia</th>
                                <th className="w-36 px-3 py-2 font-medium">Turno</th>
                                <th className="w-32 px-3 py-2 font-medium">Inicio</th>
                                <th className="w-32 px-3 py-2 font-medium">Fin</th>
                                <th className="w-40 px-3 py-2 font-medium">Modalidad</th>
                                <th className="px-3 py-2 font-medium">Observacion</th>
                                <th className="w-14 px-3 py-2 text-right font-medium">Quitar</th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.disponibilidades.map((disponibilidad, index) => (
                                <tr key={`disponibilidad-${index}`} className="border-t">
                                    <td className="px-3 py-2">
                                        <Select value={disponibilidad.dia_semana} onValueChange={(value) => actualizarDisponibilidad(index, 'dia_semana', value)}>
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {opciones.diasSemana.map((dia) => (
                                                    <SelectItem key={dia.value} value={dia.value}>
                                                        {dia.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </td>
                                    <td className="px-3 py-2">
                                        <Select value={disponibilidad.turno} onValueChange={(value) => actualizarDisponibilidad(index, 'turno', value)}>
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {opciones.turnos.map((turno) => (
                                                    <SelectItem key={turno.value} value={turno.value}>
                                                        {turno.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </td>
                                    <td className="px-3 py-2">
                                        <Input
                                            type="time"
                                            value={disponibilidad.hora_inicio}
                                            onChange={(event) => actualizarDisponibilidad(index, 'hora_inicio', event.target.value)}
                                        />
                                        <InputError message={error(`disponibilidades.${index}.hora_inicio`)} className="mt-1" />
                                    </td>
                                    <td className="px-3 py-2">
                                        <Input
                                            type="time"
                                            value={disponibilidad.hora_fin}
                                            onChange={(event) => actualizarDisponibilidad(index, 'hora_fin', event.target.value)}
                                        />
                                        <InputError message={error(`disponibilidades.${index}.hora_fin`)} className="mt-1" />
                                    </td>
                                    <td className="px-3 py-2">
                                        <Select value={disponibilidad.modalidad} onValueChange={(value) => actualizarDisponibilidad(index, 'modalidad', value)}>
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {opciones.modalidades.map((modalidad) => (
                                                    <SelectItem key={modalidad.value} value={modalidad.value}>
                                                        {modalidad.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </td>
                                    <td className="px-3 py-2">
                                        <Input
                                            value={disponibilidad.observacion}
                                            onChange={(event) => actualizarDisponibilidad(index, 'observacion', event.target.value)}
                                        />
                                        <InputError message={error(`disponibilidades.${index}.observacion`)} className="mt-1" />
                                    </td>
                                    <td className="px-3 py-2 text-right">
                                        <Button
                                            type="button"
                                            size="icon"
                                            variant="ghost"
                                            title="Quitar bloque"
                                            onClick={() =>
                                                setData(
                                                    'disponibilidades',
                                                    data.disponibilidades.filter((_, posicion) => posicion !== index),
                                                )
                                            }
                                        >
                                            <Trash2 />
                                            <span className="sr-only">Quitar bloque</span>
                                        </Button>
                                    </td>
                                </tr>
                            ))}

                            {data.disponibilidades.length === 0 && (
                                <tr>
                                    <td colSpan={7} className="text-muted-foreground px-3 py-6 text-center">
                                        Sin referencias horarias registradas.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </section>

            <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <Button asChild variant="outline">
                    <Link href={cancelHref}>Cancelar</Link>
                </Button>
                <Button type="submit" disabled={processing}>
                    {processing ? <LoaderCircle className="animate-spin" /> : <Save />}
                    {submitLabel}
                </Button>
            </div>
        </form>
    );
}
