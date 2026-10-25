<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\SolicitudConstancia;
use App\Models\User;
use App\Services\TotpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function __construct(private readonly TotpService $totp)
    {
    }

    public function index(): JsonResponse
    {
        $rol = Role::query()->where('nombre', 'usuario')->first();

        $usuarios = User::query()
            ->when($rol, fn ($consulta) => $consulta->where('role_id', $rol->id))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $correosConCuenta = User::query()->pluck('email');

        $disponibles = SolicitudConstancia::query()
            ->when($correosConCuenta->isNotEmpty(), function ($consulta) use ($correosConCuenta) {
                $consulta->whereNotIn('correo', $correosConCuenta);
            })
            ->orderBy('nombre_empleado')
            ->get(['nombre_empleado', 'correo'])
            ->unique(fn ($fila) => strtolower($fila->correo))
            ->values()
            ->map(fn ($fila) => [
                'name' => $fila->nombre_empleado,
                'email' => $fila->correo,
            ]);

        return response()->json([
            'data' => $usuarios,
            'disponibles' => $disponibles,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'name.min' => 'El nombre debe tener al menos 3 caracteres.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'El correo no es válido.',
            'email.unique' => 'Ese correo ya tiene una cuenta.',
            'password.required' => 'La contraseña temporal es obligatoria.',
            'password.confirmed' => 'La confirmación no coincide con la contraseña.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.letters' => 'La contraseña debe incluir letras.',
            'password.numbers' => 'La contraseña debe incluir números.',
        ]);

        $rol = Role::query()->where('nombre', 'usuario')->firstOrFail();

        $usuario = User::create([
            'name' => $datos['name'],
            'email' => $datos['email'],
            'password' => $datos['password'],
            'password_temporal' => true,
            'totp_secret' => $this->totp->secreto(),
            'totp_confirmado' => false,
            'role_id' => $rol->id,
        ]);

        $asignadas = SolicitudConstancia::query()
            ->whereRaw('lower(correo) = ?', [strtolower($usuario->email)])
            ->update(['user_id' => $usuario->id]);

        $mensaje = 'Usuario creado. En el primer ingreso debe cambiar la contraseña y registrar el código de la app.';
        if ($asignadas > 0) {
            $mensaje .= ' Se le asignaron '.$asignadas.' constancia(s) que ya tenían este correo.';
        }

        return response()->json([
            'message' => $mensaje,
            'data' => [
                'id' => $usuario->id,
                'name' => $usuario->name,
                'email' => $usuario->email,
            ],
        ], 201);
    }
}
