<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Localidad;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Institucion extends Model
{
    use HasFactory;

    protected $table = 'instituciones';

    protected $fillable = [
        'localidad',
        'nivel_id',
        'cuise',
        'nombre',
        'anexo',
    ];

    protected $casts = [
        'localidad' => Localidad::class,
    ];

    public function nivel(): BelongsTo
    {
        return $this->belongsTo(Nivel::class);
    }
}
