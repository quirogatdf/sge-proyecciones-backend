<?php

declare(strict_types=1);

namespace App\Models;

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
        'observacion',
        'url',
    ];

    protected function casts(): array
    {
        return [
            'año' => 'integer',
        ];
    }

    public function proyecciones(): HasMany
    {
        return $this->hasMany(Proyeccion::class, 'id_resolucion');
    }
}
