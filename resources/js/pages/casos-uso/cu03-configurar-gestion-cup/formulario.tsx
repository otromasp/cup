import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Link } from '@inertiajs/react';
import { LoaderCircle, Plus, Save, Trash2 } from 'lucide-react';
import { FormEventHandler } from 'react';

import {
    type CarreraCupForm,
    type GestionCupForm,
    type MateriaCupForm,
    type OpcionesGestionCup,
    type RequisitoInscripcionForm,
    type TurnoGestionCupForm,
} from './types';

type FormularioGestionCupProps = {
    data: GestionCupForm;
    opciones: OpcionesGestionCup;
    errors: Record<string, string | undefined>;
    processing: boolean;
    submitLabel: string;
    cancelHref: string;
    onSubmit: FormEventHandler;
    setData: <K extends keyof GestionCupForm>(key: K, value: GestionCupForm[K]) => void;
};

const nuevaCarrera = (): CarreraCupForm => ({
    id_carrera_cup: null,
    nombre_carrera: '',
    cupo_disponible: '1',
    estado: 'activo',
});

const nuevoTurno = (): TurnoGestionCupForm => ({
    id_turno_gestion: null,
    turno: 'manana',
    orden: '1',
    capacidad_maxima: '70',
    modalidad: 'presencial',
    estado: 'activo',
});

const nuevaMateria = (): MateriaCupForm => ({
    id_materia_cup: null,
    nombre_materia: '',
    ponderacion_nota1: '30.00',
    ponderacion_nota2: '30.00',
    ponderacion_nota3: '40.00',
    nota_minima: '60.00',
    estado: 'activo',
});

const nuevoRequisito = (): RequisitoInscripcionForm => ({
    id_requisito: null,
    nombre_requisito: '',
    obligatorio: true,
    tipo_requisito: 'declarativo',
    aplica_a: 'todos',
    estado: 'activo',
});

export function FormularioGestionCup({ data, opciones, errors, processing, submitLabel, cancelHref, onSubmit, setData }: FormularioGestionCupProps) {
    const error = (key: string) => errors[key];

    const actualizarFechaInicio = (fechaInicio: string) => {
        setData('fecha_inicio', fechaInicio);

        if (!data.fecha_fin || data.fecha_fin < fechaInicio) {
            setData('fecha_fin', fechaInicio);
        }
    };

    const actualizarCarrera = <K extends keyof CarreraCupForm>(index: number, key: K, value: CarreraCupForm[K]) => {
        setData(
            'carreras',
            data.carreras.map((carrera, posicion) => (posicion === index ? { ...carrera, [key]: value } : carrera)),
        );
    };

    const actualizarTurno = <K extends keyof TurnoGestionCupForm>(index: number, key: K, value: TurnoGestionCupForm[K]) => {
        setData(
            'turnos',
            data.turnos.map((turno, posicion) => (posicion === index ? { ...turno, [key]: value } : turno)),
        );
    };

    const actualizarMateria = <K extends keyof MateriaCupForm>(index: number, key: K, value: MateriaCupForm[K]) => {
        setData(
            'materias',
            data.materias.map((materia, posicion) => (posicion === index ? { ...materia, [key]: value } : materia)),
        );
    };

    const actualizarRequisito = <K extends keyof RequisitoInscripcionForm>(index: number, key: K, value: RequisitoInscripcionForm[K]) => {
        setData(
            'requisitos',
            data.requisitos.map((requisito, posicion) => (posicion === index ? { ...requisito, [key]: value } : requisito)),
        );
    };

    return (
        <form onSubmit={onSubmit} className="flex flex-col gap-4">
            <section className="rounded-lg border p-4">
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div className="grid gap-2 md:col-span-2">
                        <Label htmlFor="nombre_gestion">Gestion</Label>
                        <Input id="nombre_gestion" value={data.nombre_gestion} onChange={(event) => setData('nombre_gestion', event.target.value)} />
                        <InputError message={error('nombre_gestion')} />
                    </div>

                    <div className="grid gap-2 md:col-span-2">
                        <Label htmlFor="convocatoria">Convocatoria</Label>
                        <Input id="convocatoria" value={data.convocatoria} onChange={(event) => setData('convocatoria', event.target.value)} />
                        <InputError message={error('convocatoria')} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="fecha_inicio">Inicio</Label>
                        <Input
                            id="fecha_inicio"
                            type="date"
                            value={data.fecha_inicio}
                            onChange={(event) => actualizarFechaInicio(event.target.value)}
                        />
                        <InputError message={error('fecha_inicio')} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="fecha_fin">Fin</Label>
                        <Input
                            id="fecha_fin"
                            type="date"
                            min={data.fecha_inicio || undefined}
                            value={data.fecha_fin}
                            onChange={(event) => setData('fecha_fin', event.target.value)}
                        />
                        <InputError message={error('fecha_fin')} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="nota_minima_aprobacion">Nota minima</Label>
                        <Input
                            id="nota_minima_aprobacion"
                            type="number"
                            min="0"
                            max="100"
                            step="0.01"
                            value={data.nota_minima_aprobacion}
                            onChange={(event) => setData('nota_minima_aprobacion', event.target.value)}
                        />
                        <InputError message={error('nota_minima_aprobacion')} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="costo_inscripcion">Costo inscripcion</Label>
                        <Input
                            id="costo_inscripcion"
                            type="number"
                            min="0"
                            step="0.01"
                            value={data.costo_inscripcion}
                            onChange={(event) => setData('costo_inscripcion', event.target.value)}
                        />
                        <InputError message={error('costo_inscripcion')} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="moneda_inscripcion">Moneda</Label>
                        <Select value={data.moneda_inscripcion} onValueChange={(value) => setData('moneda_inscripcion', value)}>
                            <SelectTrigger id="moneda_inscripcion">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {opciones.monedasInscripcion.map((moneda) => (
                                    <SelectItem key={moneda.value} value={moneda.value}>
                                        {moneda.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={error('moneda_inscripcion')} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="estado_configuracion">Estado</Label>
                        <Select value={data.estado_configuracion} onValueChange={(value) => setData('estado_configuracion', value)}>
                            <SelectTrigger id="estado_configuracion">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {opciones.estadosConfiguracion.map((estado) => (
                                    <SelectItem key={estado.value} value={estado.value}>
                                        {estado.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={error('estado_configuracion')} />
                    </div>
                </div>
            </section>

            <section className="rounded-lg border p-4">
                <div className="mb-3 flex items-center justify-between gap-2">
                    <h2 className="text-base font-semibold tracking-normal">Turnos y modalidad CUP</h2>
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        onClick={() => setData('turnos', [...data.turnos, { ...nuevoTurno(), orden: String(data.turnos.length + 1) }])}
                    >
                        <Plus />
                        Turno
                    </Button>
                </div>
                <InputError message={error('turnos')} className="mb-2" />

                <div className="overflow-x-auto rounded-md border">
                    <table className="w-full min-w-[860px] text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="w-28 px-3 py-2 font-medium">Orden</th>
                                <th className="px-3 py-2 font-medium">Turno</th>
                                <th className="w-36 px-3 py-2 font-medium">Cupos</th>
                                <th className="w-44 px-3 py-2 font-medium">Modalidad</th>
                                <th className="w-44 px-3 py-2 font-medium">Estado</th>
                                <th className="w-14 px-3 py-2 text-right font-medium">Quitar</th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.turnos.map((turno, index) => (
                                <tr key={`turno-${index}`} className="border-t">
                                    <td className="px-3 py-2">
                                        <Input
                                            type="number"
                                            min="1"
                                            value={turno.orden}
                                            onChange={(event) => actualizarTurno(index, 'orden', event.target.value)}
                                        />
                                    </td>
                                    <td className="px-3 py-2">
                                        <Select value={turno.turno} onValueChange={(value) => actualizarTurno(index, 'turno', value)}>
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {opciones.turnos.map((opcionTurno) => (
                                                    <SelectItem key={opcionTurno.value} value={opcionTurno.value}>
                                                        {opcionTurno.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={error(`turnos.${index}.turno`)} className="mt-1" />
                                    </td>
                                    <td className="px-3 py-2">
                                        <Input
                                            type="number"
                                            min="1"
                                            value={turno.capacidad_maxima}
                                            onChange={(event) => actualizarTurno(index, 'capacidad_maxima', event.target.value)}
                                        />
                                        <InputError message={error(`turnos.${index}.capacidad_maxima`)} className="mt-1" />
                                    </td>
                                    <td className="px-3 py-2">
                                        <Select value={turno.modalidad} onValueChange={(value) => actualizarTurno(index, 'modalidad', value)}>
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
                                        <Select value={turno.estado} onValueChange={(value) => actualizarTurno(index, 'estado', value)}>
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {opciones.estados.map((estado) => (
                                                    <SelectItem key={estado.value} value={estado.value}>
                                                        {estado.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </td>
                                    <td className="px-3 py-2 text-right">
                                        <Button
                                            type="button"
                                            size="icon"
                                            variant="ghost"
                                            title="Quitar turno"
                                            disabled={data.turnos.length === 1}
                                            onClick={() =>
                                                setData(
                                                    'turnos',
                                                    data.turnos.filter((_, posicion) => posicion !== index),
                                                )
                                            }
                                        >
                                            <Trash2 />
                                            <span className="sr-only">Quitar turno</span>
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </section>

            <section className="rounded-lg border p-4">
                <div className="mb-3 flex items-center justify-between gap-2">
                    <h2 className="text-base font-semibold tracking-normal">Carreras y cupos</h2>
                    <Button type="button" size="sm" variant="outline" onClick={() => setData('carreras', [...data.carreras, nuevaCarrera()])}>
                        <Plus />
                        Carrera
                    </Button>
                </div>
                <InputError message={error('carreras')} className="mb-2" />

                <div className="overflow-x-auto rounded-md border">
                    <table className="w-full min-w-[760px] text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-3 py-2 font-medium">Carrera</th>
                                <th className="w-36 px-3 py-2 font-medium">Cupos</th>
                                <th className="w-44 px-3 py-2 font-medium">Estado</th>
                                <th className="w-14 px-3 py-2 text-right font-medium">Quitar</th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.carreras.map((carrera, index) => (
                                <tr key={`carrera-${index}`} className="border-t">
                                    <td className="px-3 py-2">
                                        <Input
                                            value={carrera.nombre_carrera}
                                            onChange={(event) => actualizarCarrera(index, 'nombre_carrera', event.target.value)}
                                        />
                                        <InputError message={error(`carreras.${index}.nombre_carrera`)} className="mt-1" />
                                    </td>
                                    <td className="px-3 py-2">
                                        <Input
                                            type="number"
                                            min="1"
                                            value={carrera.cupo_disponible}
                                            onChange={(event) => actualizarCarrera(index, 'cupo_disponible', event.target.value)}
                                        />
                                        <InputError message={error(`carreras.${index}.cupo_disponible`)} className="mt-1" />
                                    </td>
                                    <td className="px-3 py-2">
                                        <Select value={carrera.estado} onValueChange={(value) => actualizarCarrera(index, 'estado', value)}>
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {opciones.estados.map((estado) => (
                                                    <SelectItem key={estado.value} value={estado.value}>
                                                        {estado.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </td>
                                    <td className="px-3 py-2 text-right">
                                        <Button
                                            type="button"
                                            size="icon"
                                            variant="ghost"
                                            title="Quitar carrera"
                                            disabled={data.carreras.length === 1}
                                            onClick={() =>
                                                setData(
                                                    'carreras',
                                                    data.carreras.filter((_, posicion) => posicion !== index),
                                                )
                                            }
                                        >
                                            <Trash2 />
                                            <span className="sr-only">Quitar carrera</span>
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </section>

            <section className="rounded-lg border p-4">
                <div className="mb-3 flex items-center justify-between gap-2">
                    <h2 className="text-base font-semibold tracking-normal">Materias y ponderaciones</h2>
                    <Button type="button" size="sm" variant="outline" onClick={() => setData('materias', [...data.materias, nuevaMateria()])}>
                        <Plus />
                        Materia
                    </Button>
                </div>
                <InputError message={error('materias')} className="mb-2" />

                <div className="overflow-x-auto rounded-md border">
                    <table className="w-full min-w-[980px] text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-3 py-2 font-medium">Materia</th>
                                <th className="w-28 px-3 py-2 font-medium">Nota 1 %</th>
                                <th className="w-28 px-3 py-2 font-medium">Nota 2 %</th>
                                <th className="w-28 px-3 py-2 font-medium">Nota 3 %</th>
                                <th className="w-32 px-3 py-2 font-medium">Minima</th>
                                <th className="w-44 px-3 py-2 font-medium">Estado</th>
                                <th className="w-14 px-3 py-2 text-right font-medium">Quitar</th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.materias.map((materia, index) => (
                                <tr key={`materia-${index}`} className="border-t">
                                    <td className="px-3 py-2">
                                        <Input
                                            value={materia.nombre_materia}
                                            onChange={(event) => actualizarMateria(index, 'nombre_materia', event.target.value)}
                                        />
                                        <InputError message={error(`materias.${index}.nombre_materia`)} className="mt-1" />
                                    </td>
                                    {(['ponderacion_nota1', 'ponderacion_nota2', 'ponderacion_nota3'] as const).map((campo) => (
                                        <td key={campo} className="px-3 py-2">
                                            <Input
                                                type="number"
                                                min="0"
                                                max="100"
                                                step="0.01"
                                                value={materia[campo]}
                                                onChange={(event) => actualizarMateria(index, campo, event.target.value)}
                                            />
                                            <InputError message={error(`materias.${index}.${campo}`)} className="mt-1" />
                                        </td>
                                    ))}
                                    <td className="px-3 py-2">
                                        <Input
                                            type="number"
                                            min="0"
                                            max="100"
                                            step="0.01"
                                            value={materia.nota_minima}
                                            onChange={(event) => actualizarMateria(index, 'nota_minima', event.target.value)}
                                        />
                                        <InputError message={error(`materias.${index}.nota_minima`)} className="mt-1" />
                                    </td>
                                    <td className="px-3 py-2">
                                        <Select value={materia.estado} onValueChange={(value) => actualizarMateria(index, 'estado', value)}>
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {opciones.estados.map((estado) => (
                                                    <SelectItem key={estado.value} value={estado.value}>
                                                        {estado.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </td>
                                    <td className="px-3 py-2 text-right">
                                        <Button
                                            type="button"
                                            size="icon"
                                            variant="ghost"
                                            title="Quitar materia"
                                            disabled={data.materias.length === 1}
                                            onClick={() =>
                                                setData(
                                                    'materias',
                                                    data.materias.filter((_, posicion) => posicion !== index),
                                                )
                                            }
                                        >
                                            <Trash2 />
                                            <span className="sr-only">Quitar materia</span>
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </section>

            <section className="rounded-lg border p-4">
                <div className="mb-3 flex items-center justify-between gap-2">
                    <h2 className="text-base font-semibold tracking-normal">Requisitos de inscripcion</h2>
                    <Button type="button" size="sm" variant="outline" onClick={() => setData('requisitos', [...data.requisitos, nuevoRequisito()])}>
                        <Plus />
                        Requisito
                    </Button>
                </div>
                <InputError message={error('requisitos')} className="mb-2" />

                <div className="overflow-x-auto rounded-md border">
                    <table className="w-full min-w-[1080px] text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-3 py-2 font-medium">Requisito</th>
                                <th className="w-32 px-3 py-2 text-center font-medium">Obligatorio</th>
                                <th className="w-44 px-3 py-2 font-medium">Tipo</th>
                                <th className="w-56 px-3 py-2 font-medium">Aplica a</th>
                                <th className="w-44 px-3 py-2 font-medium">Estado</th>
                                <th className="w-14 px-3 py-2 text-right font-medium">Quitar</th>
                            </tr>
                        </thead>
                        <tbody>
                            {data.requisitos.map((requisito, index) => (
                                <tr key={`requisito-${index}`} className="border-t">
                                    <td className="px-3 py-2">
                                        <Input
                                            value={requisito.nombre_requisito}
                                            onChange={(event) => actualizarRequisito(index, 'nombre_requisito', event.target.value)}
                                        />
                                        <InputError message={error(`requisitos.${index}.nombre_requisito`)} className="mt-1" />
                                    </td>
                                    <td className="px-3 py-2 text-center">
                                        <Checkbox
                                            checked={requisito.obligatorio}
                                            onCheckedChange={(checked) => actualizarRequisito(index, 'obligatorio', checked === true)}
                                            aria-label="Obligatorio"
                                        />
                                    </td>
                                    <td className="px-3 py-2">
                                        <Select
                                            value={requisito.tipo_requisito}
                                            onValueChange={(value) => actualizarRequisito(index, 'tipo_requisito', value)}
                                        >
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {opciones.tiposRequisito.map((tipo) => (
                                                    <SelectItem key={tipo.value} value={tipo.value}>
                                                        {tipo.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={error(`requisitos.${index}.tipo_requisito`)} className="mt-1" />
                                    </td>
                                    <td className="px-3 py-2">
                                        <Select value={requisito.aplica_a} onValueChange={(value) => actualizarRequisito(index, 'aplica_a', value)}>
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {opciones.ambitosAplicacion.map((ambito) => (
                                                    <SelectItem key={ambito.value} value={ambito.value}>
                                                        {ambito.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={error(`requisitos.${index}.aplica_a`)} className="mt-1" />
                                    </td>
                                    <td className="px-3 py-2">
                                        <Select value={requisito.estado} onValueChange={(value) => actualizarRequisito(index, 'estado', value)}>
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {opciones.estados.map((estado) => (
                                                    <SelectItem key={estado.value} value={estado.value}>
                                                        {estado.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </td>
                                    <td className="px-3 py-2 text-right">
                                        <Button
                                            type="button"
                                            size="icon"
                                            variant="ghost"
                                            title="Quitar requisito"
                                            disabled={data.requisitos.length === 1}
                                            onClick={() =>
                                                setData(
                                                    'requisitos',
                                                    data.requisitos.filter((_, posicion) => posicion !== index),
                                                )
                                            }
                                        >
                                            <Trash2 />
                                            <span className="sr-only">Quitar requisito</span>
                                        </Button>
                                    </td>
                                </tr>
                            ))}
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
