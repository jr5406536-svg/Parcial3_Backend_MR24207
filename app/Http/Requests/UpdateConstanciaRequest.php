<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateConstanciaRequest extends FormRequest
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
            'estado_solicitud' => ['required', 'boolean'],
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
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
            'estado_solicitud.required' => 'El estado de la solicitud es obligatorio.',
            'estado_solicitud.boolean' => 'El estado debe ser verdadero (activa) o falso (inactiva).',
        ];
    }
}
