<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\NivelResource;

final class InstitucionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'localidad' => $this->localidad,
            'cuise' => $this->cuise,
            'anexo' => $this->anexo,
            'nivel_id' => $this->nivel_id,
            'nivel' => $this->whenLoaded('nivel', fn () => new NivelResource($this->nivel)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
