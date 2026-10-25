<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CambiarPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\QrService;
use App\Services\TotpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private readonly TotpService $totp,
        private readonly QrService $qr,
    ) {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::with('role')->where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales no coinciden con nuestros registros.'],
            ]);
        }

        $this->asegurarSecreto($user);
        $user->tokens()->delete();

        return $this->respuestaAcceso($user, 'codigo', ['verificar-codigo']);
    }

    public function verificarCodigo(Request $request): JsonResponse
    {
        $this->exigirHabilidad($request, 'verificar-codigo');

        $datos = $request->validate([
            'codigo' => ['required', 'digits:6'],
        ], [
            'codigo.required' => 'El código es obligatorio.',
            'codigo.digits' => 'El código debe tener 6 dígitos.',
        ]);

        $user = $request->user()->load('role');

        if (! $user->totp_secret || ! $this->totp->verificar($user->totp_secret, $datos['codigo'])) {
            throw ValidationException::withMessages([
                'codigo' => ['El código no es válido.'],
            ]);
        }

        $user->forceFill(['totp_confirmado' => true])->save();
        $request->user()->currentAccessToken()->delete();

        if ($user->password_temporal) {
            return $this->respuestaAcceso($user, 'cambiar-password', ['cambiar-password']);
        }

        return $this->respuestaAcceso($user, 'inicio', ['*']);
    }

    public function enrolamiento(Request $request): JsonResponse
    {
        $this->exigirHabilidad($request, 'verificar-codigo');

        $user = $request->user();

        if ($user->totp_confirmado) {
            return response()->json(['enrolamiento' => null]);
        }

        $this->asegurarSecreto($user);

        return response()->json([
            'enrolamiento' => $this->enrolamientoDe($user),
        ]);
    }

    public function cambiarPassword(CambiarPasswordRequest $request): JsonResponse
    {
        $this->exigirHabilidad($request, 'cambiar-password');

        $user = $request->user()->load('role');

        if (! $user->password_temporal) {
            throw ValidationException::withMessages([
                'password' => ['La contraseña ya fue actualizada.'],
            ]);
        }

        if (! Hash::check($request->validated('password_actual'), $user->password)) {
            throw ValidationException::withMessages([
                'password_actual' => ['La contraseña actual no coincide.'],
            ]);
        }

        $user->forceFill([
            'password' => $request->validated('password'),
            'password_temporal' => false,
        ])->save();

        $request->user()->currentAccessToken()->delete();

        return $this->respuestaAcceso($user, 'inicio', ['*']);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user()->load('role'));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada.',
        ]);
    }

    private function asegurarSecreto(User $user): void
    {
        if ($user->totp_secret) {
            return;
        }

        $user->forceFill([
            'totp_secret' => $this->totp->secreto(),
            'totp_confirmado' => false,
        ])->save();
    }

    private function exigirHabilidad(Request $request, string $habilidad): void
    {
        if ($request->user()->tokenCan($habilidad)) {
            return;
        }

        abort(response()->json([
            'message' => 'No tiene permiso para esta acción.',
        ], 403));
    }

    private function respuestaAcceso(User $user, string $paso, array $habilidades): JsonResponse
    {
        $cuerpo = [
            'token' => $user->createToken('api', $habilidades)->plainTextToken,
            'token_type' => 'Bearer',
            'paso' => $paso,
            'user' => new UserResource($user),
        ];

        if ($paso === 'codigo' && ! $user->totp_confirmado) {
            $cuerpo['enrolamiento'] = $this->enrolamientoDe($user);
        }

        return response()->json($cuerpo);
    }

    /**
     * @return array{secreto: string, qr: string}
     */
    private function enrolamientoDe(User $user): array
    {
        $url = $this->totp->url($user->email, $user->totp_secret);

        return [
            'secreto' => $user->totp_secret,
            'qr' => 'data:image/png;base64,'.base64_encode($this->qr->pngTexto($url)),
        ];
    }
}
