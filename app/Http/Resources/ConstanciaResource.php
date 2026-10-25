<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConstanciaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_constancias' => $this->id_constancias,
            'user_id' => $this->user_id,
            'usuario' => $this->user?->name,
            'nombre_empleado' => $this->nombre_empleado,
            'correo' => $this->correo,
            'movil' => $this->movil,
            'tipo_de_constancia' => $this->tipo_de_constancia,
            'descripcion' => $this->descripcion,
            'cantidad' => $this->cantidad,
            'fecha_de_solicitud' => $this->fecha_de_solicitud?->format('Y-m-d'),
            'hora_de_solicitud' => $this->hora_de_solicitud,
            'estado_solicitud' => $this->estado_solicitud,
            'estado' => $this->estado_texto,
        ];
    }
}
