<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Una proyección es la "plaza" (nivel + institución + puesto).
 *
 * Los datos que varían por año (estado, motivo, cargo, función, turno,
 * fechas, resoluciones, etc.) viven en `ProyeccionInstrumento`, un snapshot
 * por (proyeccion, anio).
 */
class Proyeccion extends Model
{
    use HasFactory;

    protected $table = 'proyecciones';

    protected $fillable = [
        'id_nivel',
        'id_institucion',
        'id_puesto',
    ];

    public function nivel(): BelongsTo
    {
        return $this->belongsTo(Nivel::class, 'id_nivel');
    }

    public function institucion(): BelongsTo
    {
        return $this->belongsTo(Institucion::class, 'id_institucion');
    }

    public function instrumentos(): HasMany
    {
        return $this->hasMany(ProyeccionInstrumento::class, 'proyeccion_id');
    }
}
