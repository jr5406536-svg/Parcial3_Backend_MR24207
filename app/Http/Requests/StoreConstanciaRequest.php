<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConstanciaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre_empleado' => ['required', 'string', 'min:3', 'max:150'],
            'correo' => ['required', 'email', 'max:150'],
            'movil' => ['required', 'string', 'regex:/^[0-9+\-\s]{8,20}$/'],
            'tipo_de_constancia' => ['required', 'string', 'min:3', 'max:100'],
            'descripcion' => ['required', 'string', 'min:10', 'max:500'],
            'cantidad' => ['required', 'integer', 'min:1', 'max:99'],
            'user_id' => ['required', 'integer', $this->usuarioExistente()],
            'estado_solicitud' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre_empleado.required' => 'El nombre del empleado es obligatorio.',
            'nombre_empleado.min' => 'El nombre del empleado debe tener al menos 3 caracteres.',
            'correo.required' => 'El correo es obligatorio.',
            'correo.email' => 'El correo no es válido.',
            'movil.required' => 'El móvil es obligatorio.',
            'movil.regex' => 'El móvil debe tener entre 8 y 20 dígitos.',
            'tipo_de_constancia.required' => 'El tipo de constancia es obligatorio.',
            'tipo_de_constancia.min' => 'El tipo de constancia debe tener al menos 3 caracteres.',
            'descripcion.required' => 'La descripción es obligatoria.',
            'descripcion.min' => 'La descripción debe tener al menos 10 caracteres.',
            'cantidad.required' => 'La cantidad es obligatoria.',
            'cantidad.integer' => 'La cantidad debe ser un número entero.',
            'cantidad.min' => 'La cantidad mínima es 1.',
            'cantidad.max' => 'La cantidad máxima es 99.',
            'user_id.required' => 'Elige el usuario dueño de la constancia.',
            'user_id.exists' => 'El usuario elegido no es válido.',
            'estado_solicitud.required' => 'Indica si la constancia queda activa o pendiente.',
            'estado_solicitud.boolean' => 'El estado no es válido.',
        ];
    }

    private function usuarioExistente(): \Illuminate\Validation\Rules\Exists
    {
        $rol = Role::query()->where('nombre', 'usuario')->value('id');

        return Rule::exists('users', 'id')->where('role_id', $rol);
    }
}
