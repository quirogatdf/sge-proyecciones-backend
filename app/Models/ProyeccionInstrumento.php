<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EstadoProyeccion;
use App\Enums\MotivoProyeccion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de una proyección para un año concreto.
 *
 * Un registro por (proyeccion, anio). Es la fuente de verdad de todos los
 * datos que varían por año: instrumento legal, motivo, estado, fechas,
 * cargo, función, turno, cantidades y destinos.
 *
 * `proyecciones` guarda solo la identidad base (nivel, institución, puesto);
 * el resto vive acá.
 */
class ProyeccionInstrumento extends Model
{
    use HasFactory;

    protected $table = 'proyeccion_instrumentos';

    protected $fillable = [
        'proyeccion_id',
        'anio',
        'estado',
        'motivo',
        'n_expediente',
        'fecha_desde',
        'fecha_hasta',
        'id_resolucion',
        'orden',
        'resolucion_ministerial',
        'resolucion_ministerial_ext',
        'disposicion_sgnij',
        'rect_disposoco_sgnij',
        'resolucion_ministerial_rect1',
        'resolucion_ministerial_rect2',
        'resolucion_previa_continuidad',
        'id_cargo',
        'id_funcion',
        'id_turno',
        'horar',
        'cargos',
        'destino_anterior',
        'destino_nuevo',
        'observaciones',
    ];

    protected $casts = [
        'estado' => EstadoProyeccion::class,
        'motivo' => MotivoProyeccion::class,
        'fecha_desde' => 'date',
        'fecha_hasta' => 'date',
        'orden' => 'integer',
        'horar' => 'integer',
        'cargos' => 'integer',
    ];

    public function proyeccion(): BelongsTo
    {
        return $this->belongsTo(Proyeccion::class, 'proyeccion_id');
    }

    public function resolucion(): BelongsTo
    {
        return $this->belongsTo(Resolucion::class, 'id_resolucion');
    }

    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class, 'id_cargo');
    }

    public function funcion(): BelongsTo
    {
        return $this->belongsTo(Funcion::class, 'id_funcion');
    }

    public function turno(): BelongsTo
    {
        return $this->belongsTo(Turno::class, 'id_turno');
    }
}
