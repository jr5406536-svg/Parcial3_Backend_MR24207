<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $rol = $this->role?->nombre;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'rol' => $rol,
            'password_temporal' => (bool) $this->password_temporal,
            'totp_confirmado' => (bool) $this->totp_confirmado,
            'permisos' => $rol === 'administrador'
                ? [
                    'constancias.ver',
                    'constancias.crear',
                    'constancias.editar',
                    'constancias.eliminar',
                    'constancias.correo',
                    'reportes.pdf',
                    'reportes.csv',
                    'reportes.grafico',
                    'usuarios.crear',
                ]
                : [
                    'constancias.propias',
                    'constancias.solicitar',
                ],
        ];
    }
}
