<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TipoResolucion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Resolucion extends Model
{
    use HasFactory;

    protected $table = 'resoluciones';

    protected $fillable = [
        'nombre',
        'año',
        'tipo',
        'observacion',
        'url',
    ];

    protected function casts(): array
    {
        return [
            'año' => 'integer',
            'tipo' => TipoResolucion::class,
        ];
    }

    public function instrumentos(): HasMany
    {
        return $this->hasMany(ProyeccionInstrumento::class, 'id_resolucion');
    }
}
