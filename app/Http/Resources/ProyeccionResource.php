<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ProyeccionInstrumento;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Proyección = la plaza (nivel, institución, puesto) más los datos de su
 * instrumento del año en foco.
 *
 * En el listado se carga un único instrumento (el del año pedido); en el
 * detalle se cargan todos. Los campos "planos" se toman del instrumento
 * más reciente de los cargados, y el historial completo viaja en
 * `instrumentos`.
 */
class ProyeccionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $inst = $this->instrumentoVigente();

        return [
            // Identidad de la plaza (vive en `proyecciones`)
            'id' => $this->id,
            'id_nivel' => $this->id_nivel,
            'id_institucion' => $this->id_institucion,
            'id_puesto' => $this->id_puesto,

            // Año en foco
            'instrumento_id' => $inst?->id,
            'anio' => $inst?->anio,
            'año' => $inst?->anio,

            // Datos del instrumento (varían por año)
            'estado' => $inst?->estado,
            'n_expediente' => $inst?->n_expediente,
            'motivo' => $inst?->motivo,
            'orden' => $inst?->orden,
            'horar' => $inst?->horar,
            'cargos' => $inst?->cargos,
            'id_cargo' => $inst?->id_cargo,
            'id_funcion' => $inst?->id_funcion,
            'id_turno' => $inst?->id_turno,
            'fecha_desde' => $inst?->fecha_desde,
            'fecha_hasta' => $inst?->fecha_hasta,
            'resolucion_ministerial' => $inst?->resolucion_ministerial,
            'resolucion_ministerial_ext' => $inst?->resolucion_ministerial_ext,
            'disposicion_sgnij' => $inst?->disposicion_sgnij,
            'rect_disposoco_sgnij' => $inst?->rect_disposoco_sgnij,
            'resolucion_ministerial_rect1' => $inst?->resolucion_ministerial_rect1,
            'resolucion_ministerial_rect2' => $inst?->resolucion_ministerial_rect2,
            'resolucion_previa_continuidad' => $inst?->resolucion_previa_continuidad,
            'destino_anterior' => $inst?->destino_anterior,
            'destino_nuevo' => $inst?->destino_nuevo,
            'id_resolucion' => $inst?->id_resolucion,

            // Relaciones de la plaza
            'nivel' => $this->whenLoaded('nivel'),
            'institucion' => $this->whenLoaded('institucion'),

            // Relaciones del instrumento en foco
            'cargo' => $inst?->relationLoaded('cargo') ? $inst->cargo : null,
            'funcion' => $inst?->relationLoaded('funcion') ? $inst->funcion : null,
            'turno' => $inst?->relationLoaded('turno') ? $inst->turno : null,
            'resolucion' => $inst?->relationLoaded('resolucion') ? $inst->resolucion : null,

            // Historial completo (solo en el detalle)
            'instrumentos' => $this->relationLoaded('instrumentos')
                ? ProyeccionInstrumentoResource::collection($this->instrumentos)
                : [],
        ];
    }

    /**
     * El instrumento más reciente de los que vienen cargados.
     * En el listado es el del año pedido; en el detalle, el último.
     */
    private function instrumentoVigente(): ?ProyeccionInstrumento
    {
        if (! $this->relationLoaded('instrumentos')) {
            return null;
        }

        return $this->instrumentos->sortByDesc('anio')->first();
    }
}
