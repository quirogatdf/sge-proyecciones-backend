<?php declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\EstadoProyeccion;
use App\Enums\MotivoProyeccion;

class Proyeccion extends Model {
    protected $table = 'proyecciones';
    protected $fillable = [
        'id_nivel', 'estado', 'n_expediente', 'motivo', 'orden',
        'horar', 'cargos', 'id_cargo', 'id_funcion', 'id_turno',
        'fecha_desde', 'fecha_hasta', 'id_institucion',
        'resolucion_ministerial', 'resolucion_ministerial_ext',
        'disposicion_sgnij', 'rect_disposoco_sgnij',
        'año', 'id_puesto', 'resolucion_ministerial_rect1', 'resolucion_ministerial_rect2',
        'resolucion_previa_continuidad'
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
    public function nivel(): BelongsTo { return $this->belongsTo(Nivel::class, 'id_nivel'); }
    public function cargo(): BelongsTo { return $this->belongsTo(Cargo::class, 'id_cargo'); }
    public function funcion(): BelongsTo { return $this->belongsTo(Funcion::class, 'id_funcion'); }
    public function turno(): BelongsTo { return $this->belongsTo(Turno::class, 'id_turno'); }
    public function institucion(): BelongsTo { return $this->belongsTo(Institucion::class, 'id_institucion'); }
}
