<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\TipoResolucion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ResolucionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'año' => $this->año,
            'tipo' => $this->tipo?->value ?? TipoResolucion::SinDefinir->value,
            'observacion' => $this->observacion,
            'url' => $this->url,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
