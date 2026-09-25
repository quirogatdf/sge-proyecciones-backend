<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProyeccionInstrumentoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array|Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'proyeccion_id' => $this->proyeccion_id,
            'anio' => $this->anio,
            'estado' => $this->estado,
            'motivo' => $this->motivo,
            'n_expediente' => $this->n_expediente,
            'fecha_desde' => $this->fecha_desde,
            'fecha_hasta' => $this->fecha_hasta,
            'id_resolucion' => $this->id_resolucion,
            'orden' => $this->orden,
            'resolucion_ministerial' => $this->resolucion_ministerial,
            'resolucion_ministerial_ext' => $this->resolucion_ministerial_ext,
            'disposicion_sgnij' => $this->disposicion_sgnij,
            'rect_disposoco_sgnij' => $this->rect_disposoco_sgnij,
            'resolucion_ministerial_rect1' => $this->resolucion_ministerial_rect1,
            'resolucion_ministerial_rect2' => $this->resolucion_ministerial_rect2,
            'resolucion_previa_continuidad' => $this->resolucion_previa_continuidad,
            'id_cargo' => $this->id_cargo,
            'id_funcion' => $this->id_funcion,
            'id_turno' => $this->id_turno,
            'horar' => $this->horar,
            'cargos' => $this->cargos,
            'destino_anterior' => $this->destino_anterior,
            'destino_nuevo' => $this->destino_nuevo,
            'observaciones' => $this->observaciones,
            'resolucion' => $this->whenLoaded('resolucion'),
            'cargo' => $this->whenLoaded('cargo'),
            'funcion' => $this->whenLoaded('funcion'),
            'turno' => $this->whenLoaded('turno'),
        ];
    }
}
