<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProyeccionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'id_nivel' => $this->id_nivel,
            'estado' => $this->estado,
            'n_expediente' => $this->n_expediente,
            'motivo' => $this->motivo,
            'orden' => $this->orden,
            'horar' => $this->horar,
            'cargos' => $this->cargos,
            'id_cargo' => $this->id_cargo,
            'id_funcion' => $this->id_funcion,
            'id_turno' => $this->id_turno,
            'fecha_desde' => $this->fecha_desde,
            'fecha_hasta' => $this->fecha_hasta,
            'id_institucion' => $this->id_institucion,
            'resolucion_ministerial' => $this->resolucion_ministerial,
            'resolucion_ministerial_ext' => $this->resolucion_ministerial_ext,
            'disposicion_sgnij' => $this->disposicion_sgnij,
            'rect_disposoco_sgnij' => $this->rect_disposoco_sgnij,
            // Including relationships if needed, but for now just the fields
            'nivel' => $this->whenLoaded('nivel'),
            'cargo' => $this->whenLoaded('cargo'),
            'funcion' => $this->whenLoaded('funcion'),
            'turno' => $this->whenLoaded('turno'),
            'institucion' => $this->whenLoaded('institucion'),
        ];
    }
}